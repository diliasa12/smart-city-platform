const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const morgan = require("morgan");
const {
  createProxyMiddleware,
  fixRequestBody,
} = require("http-proxy-middleware");

const {
  globalLimiter,
  authLimiter,
  iotLimiter,
} = require("./middleware/rateLimit");
const { verifyJWT } = require("./middleware/jwt");
const { requestLogger } = require("./middleware/logger");
const { errorHandler } = require("./middleware/errorHandler");
const { apiResponse } = require("./utils/response");
const healthRouter = require("./routes/health");
const metricsRouter = require("./routes/metrics");
const { metricsMiddleware } = require("./routes/metrics");

const {
  PORT = 3000,
  NODE_ENV = "development",
  CITIZEN_URL = "http://smartcity-citizen:8000",
  TRAFFIC_URL = "http://traffic-service:8001",
  ENV_URL = "http://env-service:8002",
  ML_URL = "http://python-ml:5000",
  OAUTH_URL = "http://oauth-server:3002",
} = process.env;

const app = express();

app.set("trust proxy", 1);
app.use(helmet());
app.use(
  cors({
    origin: process.env.CORS_ORIGINS?.split(",") || "*",
    credentials: true,
  }),
);

app.use(morgan(NODE_ENV === "production" ? "combined" : "dev"));
app.use(requestLogger);
app.use(metricsMiddleware);

app.use(globalLimiter);

app.use("/health", healthRouter);
app.use("/metrics", metricsRouter);
app.use(
  "/oauth",
  createProxyMiddleware({
    target: OAUTH_URL,
    changeOrigin: true,
    on: { error: (err, req, res) => upstreamError(res, "oauth-server", err) },
  }),
);

app.use(verifyJWT);
app.use(authLimiter);

// Citizen Service (PHP :8000)
const citizenProxy = createProxyMiddleware({
  target: CITIZEN_URL,
  changeOrigin: true,
  on: {
    proxyReq: fixRequestBody,
    error: (err, req, res) => upstreamError(res, "citizen-service", err),
  },
});

app.use("/api/citizens", (req, res, next) => {
  req.url = req.originalUrl;
  citizenProxy(req, res, next);
});

app.use("/api/reports", (req, res, next) => {
  req.url = req.originalUrl;
  citizenProxy(req, res, next);
});

app.use("/api/notifications", (req, res, next) => {
  req.url = req.originalUrl;
  citizenProxy(req, res, next);
});

// Traffic Service (PHP :8001)
app.use(
  "/api/traffic",
  createProxyMiddleware({
    target: TRAFFIC_URL,
    changeOrigin: true,
    pathRewrite: { "^/api/traffic": "" },
    on: {
      proxyReq: fixRequestBody,
      error: (err, req, res) => upstreamError(res, "traffic-service", err),
    },
  }),
);

// Environment Service (PHP :8002)
app.use(
  "/api/environment",
  createProxyMiddleware({
    target: ENV_URL,
    changeOrigin: true,
    pathRewrite: { "^/api/environment": "" },
    on: {
      proxyReq: fixRequestBody,
      error: (err, req, res) => upstreamError(res, "env-service", err),
    },
  }),
);

// IoT ingest (POST /iot/traffic → Traffic PHP, POST /iot/air → Env PHP)
app.use(
  "/iot",
  iotLimiter,
  createProxyMiddleware({
    target: TRAFFIC_URL,
    changeOrigin: true,
    router: (req) => (req.path.startsWith("/air") ? ENV_URL : TRAFFIC_URL),
    pathRewrite: { "^/iot": "/api" },
    on: {
      proxyReq: fixRequestBody,
      error: (err, req, res) => upstreamError(res, "iot-upstream", err),
    },
  }),
);

// Python ML Service (:5000)
app.use(
  ["/predict", "/detect", "/model"],
  createProxyMiddleware({
    target: ML_URL,
    changeOrigin: true,
    on: { error: (err, req, res) => upstreamError(res, "python-ml", err) },
  }),
);

app.use((req, res) => {
  res
    .status(404)
    .json(
      apiResponse(
        404,
        null,
        `Endpoint '${req.method} ${req.originalUrl}' tidak ditemukan`,
        "error",
      ),
    );
});

app.use(errorHandler);

function upstreamError(res, serviceName, err) {
  console.error(`[Gateway] Upstream error (${serviceName}):`, err.message);
  if (res.headersSent) return;
  res
    .status(502)
    .json(
      apiResponse(
        502,
        null,
        `Service '${serviceName}' tidak dapat dijangkau`,
        "error",
      ),
    );
}

const server = app.listen(PORT, () => {
  console.log("\n══════════════════════════════════════════════");
  console.log(`  Smart City API Gateway  |  Port ${PORT}  (${NODE_ENV})`);
  console.log(`  Citizen  → ${CITIZEN_URL}`);
  console.log(`  Traffic  → ${TRAFFIC_URL}`);
  console.log(`  Env      → ${ENV_URL}`);
  console.log(`  ML       → ${ML_URL}`);
  console.log(`  OAuth    → ${OAUTH_URL}`);
  console.log("══════════════════════════════════════════════\n");
});

process.on("SIGTERM", () => server.close(() => process.exit(0)));
process.on("SIGINT", () => server.close(() => process.exit(0)));

module.exports = app;

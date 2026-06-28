const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const morgan = require("morgan");
const {
  createProxyMiddleware,
  fixRequestBody,
} = require("http-proxy-middleware");

const { globalLimiter, authLimiter } = require("./middleware/rateLimit");
const { verifyJWT } = require("./middleware/jwt");
const { requestLogger } = require("./middleware/logger");
const { errorHandler } = require("./middleware/errorHandler");
const { apiResponse } = require("./utils/response");

const {
  PORT = 3000,
  NODE_ENV = "development",
  ML_URL = "http://python-ml-service:5000",
  OAUTH_URL = "http://oauth-server:3002",
  PHP_URL = "http://php-service:8000",
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

app.use(globalLimiter);

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

// PHP service (8000)
app.use(
  "/php",
  createProxyMiddleware({
    changeOrigin: true,
    target: PHP_URL,
    pathRewrite: { "^/php": "" },
    // error handler logging PHP
    on: { error: (err, req, res) => upstreamError(res, "php-service", err) },
  }),
);

// Python ML Service (:5000)
app.use(
  "/ml",
  createProxyMiddleware({
    target: ML_URL,
    changeOrigin: true,
    pathRewrite: { "^/ml": "" },
    on: {
      error: (err, req, res) => upstreamError(res, "python-ml", err),
    },
  }),
);

app.use(
  "/iot",
  createProxyMiddleware({
    target: "http://iot-service:3000",
    changeOrigin: true,
    pathRewrite: { "^/iot": "" },
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
  console.log(`  Smart City API Gateway  |  Port ${PORT}  (${NODE_ENV})`);

  console.log(`  ML       → ${ML_URL}`);
  console.log(`  OAuth    → ${OAUTH_URL}`);
});

process.on("SIGTERM", () => server.close(() => process.exit(0)));
process.on("SIGINT", () => server.close(() => process.exit(0)));

module.exports = app;

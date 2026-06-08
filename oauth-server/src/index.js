const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const morgan = require("morgan");
const oauthRoutes = require("./routes/oauthRoutes");
const errorHandler = require("./middleware/errorHandler");
const { PORT = 3002, NODE_ENV = "development" } = process.env;

const app = express();

// ── Middleware ────────────────────────────────────────────────
app.use(helmet());
app.use(cors());
app.use(morgan(NODE_ENV === "production" ? "combined" : "dev"));

// OAuth token endpoint butuh application/x-www-form-urlencoded
app.use(express.urlencoded({ extended: false }));
app.use(express.json());

// ── Routes ────────────────────────────────────────────────────
app.use("/oauth", oauthRoutes);
app.use("/health", (req, res) => res.redirect("/oauth/health"));

// ── 404 ───────────────────────────────────────────────────────
app.use((req, res) => {
  res.status(404).json({
    status: "error",
    code: 404,
    data: null,
    message: `Endpoint '${req.method} ${req.originalUrl}' tidak ditemukan`,
    timestamp: new Date().toISOString(),
    service: "oauth-server",
  });
});

// ── Error handler ─────────────────────────────────────────────
app.use(errorHandler);

// ── Start ─────────────────────────────────────────────────────
const server = app.listen(PORT, () => {
  console.log("\n══════════════════════════════════════════════");
  console.log(`  Smart City OAuth Server  |  Port ${PORT}  (${NODE_ENV})`);
  console.log("  Endpoints:");
  console.log("    POST /oauth/token");
  console.log("    POST /oauth/introspect");
  console.log("    POST /oauth/revoke");
  console.log("    GET  /health");
  console.log("══════════════════════════════════════════════\n");
});

process.on("SIGTERM", () => server.close(() => process.exit(0)));
process.on("SIGINT", () => server.close(() => process.exit(0)));

export default app;

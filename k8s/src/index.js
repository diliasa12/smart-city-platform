const express = require("express");

const morgan = require("morgan");
const oauthRoutes = require("./routes/oauthRoute");
const errorHandler = require("./middleware/errorHandler");
const { PORT = 3002, NODE_ENV = "development" } = process.env;

const app = express();


app.use(morgan(NODE_ENV === "production" ? "combined" : "dev"));


app.use(express.urlencoded({ extended: false }));
app.use(express.json());


app.use("/", oauthRoutes);
app.use("/health", (req, res) => res.redirect("/oauth/health"));


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


app.use(errorHandler);


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

module.exports = app;

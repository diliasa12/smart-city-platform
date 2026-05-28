const express = require("express");
const client = require("prom-client");

const router = express.Router();

const register = new client.Registry();

client.collectDefaultMetrics({ register, prefix: "gateway_" });

// Counter: total HTTP requests masuk ke gateway
const httpRequestsTotal = new client.Counter({
  name: "gateway_http_requests_total",
  help: "Total HTTP requests diterima gateway",
  labelNames: ["method", "route", "status_code", "service"],
  registers: [register],
});

// Histogram: durasi request (latency)
const httpRequestDuration = new client.Histogram({
  name: "gateway_http_request_duration_ms",
  help: "Durasi request HTTP dalam milidetik",
  labelNames: ["method", "route", "status_code"],
  buckets: [5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000],
  registers: [register],
});

// Counter: upstream errors (502/503)
const upstreamErrors = new client.Counter({
  name: "gateway_upstream_errors_total",
  help: "Total error dari upstream service",
  labelNames: ["service", "error_code"],
  registers: [register],
});

// Counter: rate limit hits
const rateLimitHits = new client.Counter({
  name: "gateway_rate_limit_hits_total",
  help: "Total request yang terkena rate limiting",
  registers: [register],
});

// Gauge: uptime gateway
const gatewayUptime = new client.Gauge({
  name: "gateway_uptime_seconds",
  help: "Uptime gateway dalam detik",
  registers: [register],
  collect() {
    this.set(Math.floor(process.uptime()));
  },
});

function metricsMiddleware(req, res, next) {
  const startTime = Date.now();

  res.on("finish", () => {
    const duration = Date.now() - startTime;
    const route = normalizeRoute(req.path);
    const service = resolveService(req.path);
    const status = String(res.statusCode);

    httpRequestsTotal.inc({
      method: req.method,
      route,
      status_code: status,
      service,
    });
    httpRequestDuration.observe(
      { method: req.method, route, status_code: status },
      duration,
    );

    if (res.statusCode === 429) rateLimitHits.inc();
    if (res.statusCode === 502 || res.statusCode === 503) {
      upstreamErrors.inc({ service, error_code: status });
    }
  });

  next();
}

router.get("/", async (req, res) => {
  // Hanya izinkan akses dari internal / Prometheus (opsional: cek IP)
  res.set("Content-Type", register.contentType);
  res.end(await register.metrics());
});

function normalizeRoute(path) {
  // Normalise agar tidak terlalu banyak label unik
  return path
    .replace(/\/\d+/g, "/:id") // /citizens/123 → /citizens/:id
    .replace(/\?.*$/, ""); // hapus query string
}

function resolveService(path) {
  if (
    path.startsWith("/api/citizens") ||
    path.startsWith("/api/reports") ||
    path.startsWith("/api/notifications")
  )
    return "citizen-service";
  if (path.startsWith("/api/traffic")) return "traffic-service";
  if (path.startsWith("/api/environment")) return "env-service";
  if (
    path.startsWith("/predict") ||
    path.startsWith("/detect") ||
    path.startsWith("/model")
  )
    return "python-ml";
  if (path.startsWith("/iot")) return "iot-upstream";
  if (path.startsWith("/oauth")) return "oauth-server";
  return "gateway";
}

module.exports = router;
module.exports.metricsMiddleware = metricsMiddleware;
module.exports.upstreamErrors = upstreamErrors;
module.exports.rateLimitHits = rateLimitHits;

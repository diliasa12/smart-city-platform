const express = require("express");
const axios = require("axios");
const { apiResponse } = require("../utils/response");

const router = express.Router();

const {
  CITIZEN_URL = "http://citizen-service:8000",
  TRAFFIC_URL = "http://traffic-service:8001",
  ENV_URL = "http://env-service:8002",
  ML_URL = "http://python-ml:5000",
  OAUTH_URL = "http://oauth-server:3002",
} = process.env;

const UPSTREAMS = [
  { name: "citizen-service", url: `${CITIZEN_URL}/health` },
  { name: "traffic-service", url: `${TRAFFIC_URL}/health` },
  { name: "env-service", url: `${ENV_URL}/health` },
  { name: "python-ml", url: `${PYTHON_ML_URL}/health` },
  { name: "oauth-server", url: `${OAUTH_URL}/health` },
];

router.get("/", async (req, res) => {
  const startTime = Date.now();

  const checks = await Promise.all(UPSTREAMS.map((svc) => checkUpstream(svc)));

  const allHealthy = checks.every((c) => c.status === "healthy");
  const someUnhealthy = checks.some((c) => c.status === "unhealthy");

  const overallStatus = allHealthy
    ? "healthy"
    : someUnhealthy
      ? "degraded"
      : "unhealthy";

  const httpCode = allHealthy ? 200 : someUnhealthy ? 200 : 503;

  return res.status(httpCode).json(
    apiResponse(
      httpCode,
      {
        gateway: "healthy",
        uptime_seconds: Math.floor(process.uptime()),
        checked_in_ms: Date.now() - startTime,
        services: checks,
      },
      `Gateway ${overallStatus}`,
      allHealthy ? "success" : "error",
    ),
  );
});

router.get("/gateway", (req, res) => {
  res.json(
    apiResponse(
      200,
      {
        gateway: "healthy",
        uptime_seconds: Math.floor(process.uptime()),
        memory_mb: Math.round(process.memoryUsage().rss / 1024 / 1024),
        node_version: process.version,
        env: process.env.NODE_ENV || "development",
      },
      "Gateway sehat",
    ),
  );
});

async function checkUpstream({ name, url }) {
  const start = Date.now();
  try {
    const resp = await axios.get(url, { timeout: 3000 });
    return {
      name,
      status: "healthy",
      http_code: resp.status,
      latency_ms: Date.now() - start,
      url,
    };
  } catch (err) {
    return {
      name,
      status: "unhealthy",
      http_code: err.response?.status || null,
      latency_ms: Date.now() - start,
      error: err.message,
      url,
    };
  }
}

module.exports = router;

const rateLimit = require("express-rate-limit");
const { apiResponse } = require("../utils/response");

// Global – per IP
const globalLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.ip,
  skip: (req) => req.path === "/health",
  handler: (req, res) => {
    res
      .status(429)
      .json(
        apiResponse(
          429,
          null,
          `Terlalu banyak request dari IP ${req.ip}. Coba lagi dalam 15 menit.`,
          "error",
        ),
      );
  },
});

// Per-token – authenticated users
const authLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 500,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => {
    const auth = req.headers.authorization || "";
    const token = auth.startsWith("Bearer ") ? auth.slice(7) : null;
    return token ? `token:${token.slice(-32)}` : `ip:${req.ip}`;
  },
  handler: (req, res) => {
    res
      .status(429)
      .json(
        apiResponse(
          429,
          null,
          "Rate limit token terlampaui (500 req/jam). Coba lagi nanti.",
          "error",
        ),
      );
  },
});

// IoT-specific – per device ID
const iotLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 60,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.headers["x-device-id"] || req.ip,
  handler: (req, res) => {
    res
      .status(429)
      .json(
        apiResponse(
          429,
          null,
          "IoT device throttled. Kurangi frekuensi pengiriman data.",
          "error",
        ),
      );
  },
});

module.exports = { globalLimiter, authLimiter, iotLimiter };

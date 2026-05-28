const fs = require("fs");
const path = require("path");

const LOG_DIR = process.env.LOG_DIR || "logs";
const LOG_FILE = path.join(LOG_DIR, "gateway.log");

if (!fs.existsSync(LOG_DIR)) {
  fs.mkdirSync(LOG_DIR, { recursive: true });
}

function requestLogger(req, res, next) {
  const start = Date.now();
  const requestId = generateRequestId();

  req.requestId = requestId;
  req.headers["x-request-id"] = requestId;
  res.setHeader("x-request-id", requestId);

  res.on("finish", () => {
    const duration = Date.now() - start;
    const logEntry = {
      timestamp: new Date().toISOString(),
      requestId,
      method: req.method,
      path: req.originalUrl,
      status: res.statusCode,
      duration: `${duration}ms`,
      ip: req.ip,
      userAgent: req.headers["user-agent"] || "-",
      userId: req.userId || "-",
      role: req.role || "-",
      service: "api-gateway",
    };

    const line = JSON.stringify(logEntry);

    const color = statusColor(res.statusCode);
    console.log(
      `${color}[${logEntry.timestamp}] ${req.method} ${req.originalUrl} ` +
        `→ ${res.statusCode} (${duration}ms)\x1b[0m`,
    );

    fs.appendFile(LOG_FILE, line + "\n", (err) => {
      if (err) console.error("[Logger] Failed to write log:", err.message);
    });
  });

  next();
}

function generateRequestId() {
  return `gw-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function statusColor(status) {
  if (status >= 500) return "\x1b[31m"; // merah
  if (status >= 400) return "\x1b[33m"; // kuning
  if (status >= 300) return "\x1b[36m"; // cyan
  return "\x1b[32m"; // hijau
}

module.exports = { requestLogger };

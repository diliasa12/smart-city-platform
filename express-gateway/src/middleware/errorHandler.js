const { apiResponse } = require("../utils/response");

function errorHandler(err, req, res, next) {
  if (res.headersSent) return next(err);

  console.error(
    `[Gateway] Unhandled error on ${req.method} ${req.originalUrl}:`,
    err,
  );

  if (err.status || err.statusCode) {
    const code = err.status || err.statusCode;
    return res
      .status(code)
      .json(
        apiResponse(code, null, err.message || "Terjadi kesalahan", "error"),
      );
  }

  if (err.code === "ECONNREFUSED" || err.code === "ENOTFOUND") {
    return res
      .status(503)
      .json(
        apiResponse(
          503,
          null,
          "Service upstream tidak dapat dijangkau",
          "error",
        ),
      );
  }

  if (err.code === "ETIMEDOUT" || err.code === "ECONNABORTED") {
    return res
      .status(504)
      .json(apiResponse(504, null, "Request ke upstream timeout", "error"));
  }

  res
    .status(500)
    .json(
      apiResponse(
        500,
        null,
        process.env.NODE_ENV === "production"
          ? "Internal server error"
          : err.message,
        "error",
      ),
    );
}

module.exports = { errorHandler };

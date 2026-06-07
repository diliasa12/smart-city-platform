const apiResponse = require("../utils/response");

const errorHandler = (err, req, res, next) => {
  console.error(`[OAuthServer] ${req.method} ${req.url}`, err.message || err);

  if (res.headersSent) return next(err);

  // OAuth2Server specific errors
  if (err.code && err.message) {
    const status =
      err.code === "invalid_client"
        ? 401
        : err.code === "invalid_grant"
          ? 400
          : err.code === "invalid_request"
            ? 400
            : err.code === "unauthorized_client"
              ? 401
              : err.code === "unsupported_grant_type"
                ? 400
                : err.code === "invalid_scope"
                  ? 400
                  : err.code === "access_denied"
                    ? 403
                    : err.code === "server_error"
                      ? 500
                      : 400;

    return res
      .status(status)
      .json(apiResponse(status, null, err.message, "error"));
  }

  if (err.name === "JsonWebTokenError") {
    return res
      .status(401)
      .json(apiResponse(401, null, "Token tidak valid", "error"));
  }

  if (err.name === "TokenExpiredError") {
    return res
      .status(401)
      .json(apiResponse(401, null, "Token sudah kadaluarsa", "error"));
  }

  if (err.status || err.statusCode) {
    const code = err.status || err.statusCode;
    return res.status(code).json(apiResponse(code, null, err.message, "error"));
  }

  return res
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
};

module.exports = errorHandler;

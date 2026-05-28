const jwt = require("jsonwebtoken");
const axios = require("axios");
const { apiResponse } = require("../utils/response");

const {
  JWT_SECRET = "smartcity-super-secret-key-change-in-production",
  OAUTH_URL = "http://oauth-server:3002",
  JWT_VERIFY_MODE = "local", // 'local' | 'introspect'
} = process.env;
// PUBLIC paths — skip JWT chec
const PUBLIC_PATHS = [
  "/health",
  "/metrics",
  "/oauth/token",
  "/oauth/introspect",
  "/oauth/revoke",
];

async function verifyJWT(req, res, next) {
  if (PUBLIC_PATHS.some((p) => req.path.startsWith(p))) {
    return next();
  }

  const authHeader = req.headers["authorization"] || "";
  if (!authHeader.startsWith("Bearer ")) {
    return res
      .status(401)
      .json(
        apiResponse(
          401,
          null,
          "Authorization header tidak ditemukan atau format salah (Bearer <token>)",
          "error",
        ),
      );
  }

  const token = authHeader.slice(7);

  try {
    let decoded;

    if (JWT_VERIFY_MODE === "introspect") {
      decoded = await introspectToken(token);
    } else {
      decoded = jwt.verify(token, JWT_SECRET, {
        algorithms: ["HS256", "RS256"],
      });
    }
    req.user = decoded;
    req.userId = decoded.sub || decoded.user_id || decoded.id;
    req.role = decoded.role || "citizen";

    req.headers["x-user-id"] = String(req.userId || "");
    req.headers["x-user-role"] = String(req.role);
    req.headers["x-token-iat"] = String(decoded.iat || "");

    return next();
  } catch (err) {
    if (err.name === "TokenExpiredError") {
      return res
        .status(401)
        .json(
          apiResponse(
            401,
            null,
            "Token sudah kadaluarsa. Silakan refresh token.",
            "error",
          ),
        );
    }
    if (err.name === "JsonWebTokenError") {
      return res
        .status(401)
        .json(apiResponse(401, null, "Token tidak valid.", "error"));
    }
    console.error("[JWT] Verification error:", err.message);
    return res
      .status(401)
      .json(apiResponse(401, null, "Autentikasi gagal.", "error"));
  }
}

function requireRole(...roles) {
  return (req, res, next) => {
    if (!roles.includes(req.role)) {
      return res
        .status(403)
        .json(
          apiResponse(
            403,
            null,
            `Akses ditolak. Role '${req.role}' tidak diizinkan.`,
            "error",
          ),
        );
    }
    next();
  };
}
// OAuth 2.0 Token Introspection (RFC 7662)
async function introspectToken(token) {
  const response = await axios.post(
    `${OAUTH_URL}/oauth/introspect`,
    new URLSearchParams({ token }),
    {
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Authorization: `Basic ${Buffer.from(
          `${process.env.GATEWAY_CLIENT_ID}:${process.env.GATEWAY_CLIENT_SECRET}`,
        ).toString("base64")}`,
      },
      timeout: 3000,
    },
  );

  const data = response.data;
  if (!data.active) {
    const err = new Error("Token tidak aktif");
    err.name = "JsonWebTokenError";
    throw err;
  }

  return data;
}

module.exports = { verifyJWT, requireRole };

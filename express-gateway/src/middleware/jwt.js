const jwt = require("jsonwebtoken");
const axios = require("axios");
const { apiResponse } = require("../utils/response");

const {
  JWT_SECRET = "kelompok3",
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
  const isPublic = PUBLIC_PATHS.some((p) => req.originalUrl.startsWith(p));

  if (isPublic) {
    return next();
  }
  console.log(req.headers["authorization"]);
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
  console.log(token);
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
    const extractedId =
      decoded.sub || decoded.user_id || decoded.id || decoded.userid;

    // 2. JIKA ID-nya null atau kosong, dan ini adalah token 'service', kasih ID palsu untuk testing
    if (
      (extractedId === null ||
        extractedId === undefined ||
        extractedId === "") &&
      decoded.role === "service"
    ) {
      req.userId = "999"; // Berikan ID tiruan (misal 999) agar lolos dari Laravel
      req.role = "admin"; // Berikan role admin agar bisa GET/POST semua data
    } else {
      req.userId = extractedId;
      req.role = decoded.role || "citizen";
    }
    // Contoh jika properti token Anda ternyata adalah 'uid' atau 'user.id'

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

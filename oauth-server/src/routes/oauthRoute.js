const OAuth2Server = require("@node-oauth/oauth2-server");
const oauth = require("../config/oauthService");
const { Request, Response } = OAuth2Server;
const { getAccessToken, revokeToken } = require("../models/model");
const catchAsync = require("../utils/catchAsync");
const apiResponse = require("../utils/response");
const db = require("../config/database");
const Router = require("express");
const router = Router();

// POST /oauth/token
// Grant types:
//   grant_type=password            → citizen login (email + password)
//   grant_type=client_credentials  → service/IoT (client_id + client_secret)
//   grant_type=refresh_token       → perpanjang sesi

router.post(
  "/token",
  catchAsync(async (req, res) => {
    const oauthRequest = new Request(req);
    const oauthResponse = new Response(res);

    await oauth.token(oauthRequest, oauthResponse);

    let finalizedScope = oauthResponse.body.scope;
    if (finalizedScope && Array.isArray(finalizedScope)) {
      finalizedScope = finalizedScope.join(" ");
    } else if (!finalizedScope) {
      finalizedScope = null;
    }

    return res.status(200).json({
      status: "success",
      code: 200,
      data: {
        access_token: oauthResponse.body.access_token,
        token_type: oauthResponse.body.token_type,
        expires_in: oauthResponse.body.expires_in,
        scope: finalizedScope,
      },
      message: "Token berhasil diterbitkan",
      service: "oauth-server",
    });
  }),
);

// POST /oauth/introspect
// Digunakan oleh API Gateway untuk validasi token
// Body: token=<access_token>

router.post(
  "/introspect",
  catchAsync(async (req, res) => {
    const tokenValue = req.body?.token || req.query?.token;

    if (!tokenValue) {
      return res
        .status(400)
        .json(apiResponse(400, null, "Parameter token wajib diisi", "error"));
    }

    const tokenData = await getAccessToken(tokenValue);

    if (!tokenData) {
      return res
        .status(200)
        .json(
          apiResponse(
            200,
            { active: false },
            "Token tidak aktif atau sudah kadaluarsa",
          ),
        );
    }

    const now = Math.floor(Date.now() / 1000);
    const exp = Math.floor(tokenData.accessTokenExpiresAt.getTime() / 1000);

    return res.status(200).json(
      apiResponse(
        200,
        {
          active: true,
          client_id: tokenData.client.id,
          user_id: tokenData.user?.id || null,
          email: tokenData.user?.email || null,
          role: tokenData.user?.role || "service",
          zone_id: tokenData.user?.zone_id || null,
          scope: tokenData.scope,
          exp,
          iat: now,
          token_type: "Bearer",
        },
        "Token aktif",
      ),
    );
  }),
);

// POST /oauth/revoke
// Cabut access token atau refresh token
// Body: token=<token_value>  &  token_type_hint=access_token|refresh_token

router.post(
  "/revoke",
  catchAsync(async (req, res) => {
    const tokenValue = req.body?.token || req.query?.token;
    const hint = req.body?.token_type_hint || "access_token";

    if (!tokenValue) {
      return res
        .status(400)
        .json(apiResponse(400, null, "Parameter token wajib diisi", "error"));
    }

    if (hint === "refresh_token") {
      // Revoke via model
      await revokeToken({ refreshToken: tokenValue });
    } else {
      // Revoke access token — soft delete di DB
      await db.execute(
        `UPDATE shared_oauth_tokens SET revoked_at = NOW()
       WHERE access_token = ?`,
        [tokenValue],
      );
    }

    return res
      .status(200)
      .json(apiResponse(200, null, "Token berhasil dicabut"));
  }),
);

// GET /health
router.get(
  "/health",
  catchAsync(async (req, res) => {
    // Cek koneksi DB
    let dbStatus = "healthy";
    try {
      await db.execute("SELECT 1");
    } catch {
      dbStatus = "unhealthy";
    }

    const httpCode = dbStatus === "healthy" ? 200 : 503;

    return res.status(httpCode).json(
      apiResponse(
        httpCode,
        {
          service: "oauth-server",
          database: dbStatus,
          uptime_seconds: Math.floor(process.uptime()),
          grant_types: ["password", "client_credentials", "refresh_token"],
        },
        dbStatus === "healthy"
          ? "OAuth server sehat"
          : "Database tidak dapat dijangkau",
        dbStatus === "healthy" ? "success" : "error",
      ),
    );
  }),
);

module.exports = router;

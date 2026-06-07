import { Router } from "express";
import OAuth2Server from "@node-oauth/oauth2-server";
import oauth from "../services/oauthService.js";
import { getAccessToken, revokeToken } from "../models/oauthModel.js";
import catchAsync from "../utils/catchAsync.js";
import { apiResponse } from "../utils/response.js";
import db from "../config/database.js";
const Router = require("express");
const OAuth2Server = require("@node-oauth/oauth2-server");
const { Request, Response } = OAuth2Server;
const router = Router();

// ─────────────────────────────────────────────────────────────
// POST /oauth/token
// ─────────────────────────────────────────────────────────────
// Grant types:
//   grant_type=password            → citizen login (email + password)
//   grant_type=client_credentials  → service/IoT (client_id + client_secret)
//   grant_type=refresh_token       → perpanjang sesi
// ─────────────────────────────────────────────────────────────
router.post(
  "/token",
  catchAsync(async (req, res) => {
    const oauthRequest = new Request(req);
    const oauthResponse = new Response(res);

    const token = await oauth.token(oauthRequest, oauthResponse, {
      requireClientAuthentication: {
        password: false,
        refresh_token: false,
        client_credentials: true,
      },
    });

    return res.status(200).json(
      apiResponse(
        200,
        {
          access_token: token.accessToken,
          token_type: "Bearer",
          expires_in: Math.floor(
            (token.accessTokenExpiresAt - Date.now()) / 1000,
          ),
          refresh_token: token.refreshToken || undefined,
          refresh_token_expires_in: token.refreshToken
            ? parseInt(process.env.REFRESH_TOKEN_TTL || "604800")
            : undefined,
          scope: token.scope,
        },
        "Token berhasil diterbitkan",
      ),
    );
  }),
);

// ─────────────────────────────────────────────────────────────
// POST /oauth/introspect
// Digunakan oleh API Gateway untuk validasi token
// Body: token=<access_token>
// ─────────────────────────────────────────────────────────────
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
      // RFC 7662: token tidak aktif → kembalikan { active: false }
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

// ─────────────────────────────────────────────────────────────
// POST /oauth/revoke
// Cabut access token atau refresh token
// Body: token=<token_value>  &  token_type_hint=access_token|refresh_token
// ─────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────
// GET /health
// ─────────────────────────────────────────────────────────────
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

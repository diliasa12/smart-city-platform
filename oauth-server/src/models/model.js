/**
 * Grant types yang didukung:
 *   - password           : login user (username + password → token)
 *   - client_credentials : komunikasi antar service / IoT device
 *   - refresh_token      : perpanjang sesi tanpa login ulang
 */

const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const pool = require("../config/database");
const crypto = require("crypto");

const {
  JWT_SECRET = "kelompok3",
  ACCESS_TOKEN_TTL = "3600", // detik
  REFRESH_TOKEN_TTL = "604800", // 7 hari
} = process.env;

// ─────────────────────────────────────────────────────────────
// 1. CLIENT METHODS
// ─────────────────────────────────────────────────────────────

async function getClient(clientId, clientSecret) {
  try {
    const [rows] = await pool.execute(
      `SELECT client_id, client_secret, grant_types, redirect_uris, is_active
       FROM shared_oauth_clients
       WHERE client_id = ?`,
      [clientId],
    );

    if (rows.length === 0) return null;

    const client = rows[0];

    if (!client.is_active) return null;

    if (clientSecret && client.client_secret !== clientSecret) {
      return null;
    }

    const cleanedGrants = client.grant_types.split(",").map((g) => g.trim());
    const cleanedRedirects = client.redirect_uris
      ? client.redirect_uris.split(",").map((r) => r.trim())
      : [];

    return {
      id: client.client_id,
      clientId: client.client_id,
      grants: cleanedGrants,
      grantTypes: cleanedGrants,
      redirectUris: cleanedRedirects,
    };
  } catch (error) {
    console.error("[OAuthModel] Error pada getClient:", error.message);
    throw error;
  }
}

async function getUserFromClient(client) {
  try {
    // Machine-to-Machine: tidak ada user manusia yang login
    return { id: null, username: client.id, is_client: true };
  } catch (error) {
    console.error("[OAuthModel] Error pada getUserFromClient:", error.message);
    throw error;
  }
}

// ─────────────────────────────────────────────────────────────
// 2. USER METHODS (password grant)
// ─────────────────────────────────────────────────────────────

/**
 * getUser — validasi email + password menggunakan tabel `users`
 *
 * PERBAIKAN:
 *  - Sebelumnya query ke `admin_accounts` yang tidak ada di schema.sql
 *  - Sekarang query ke `users` sesuai schema.sql
 *  - Ambil `password` dan `role` dalam satu query (efisien, tidak dua round-trip)
 *  - Kembalikan `role` agar JWT payload dapat menyertakannya
 */
async function getUser(username, password) {
  try {
    const [rows] = await pool.execute(
      `SELECT id, name, email, phone, role, password
       FROM users
       WHERE email = ?
       LIMIT 1`,
      [username],
    );

    if (!rows.length) {
      return null;
    }

    const user = rows[0];

    const isValid = await bcrypt.compare(password, user.password);
    if (!isValid) {
      return null;
    }

    // Jangan kembalikan kolom password ke lapisan atas
    return {
      id: user.id,
      email: user.email,
      name: user.name,
      phone: user.phone,
      role: user.role, // 'admin' | 'user' — dibutuhkan di JWT payload
    };
  } catch (error) {
    console.error("[OAuthModel] Error pada getUser:", error.message);
    throw error;
  }
}

async function getAuthorizationCode(code) {
  return null;
}

async function revokeAuthorizationCode(code) {
  return true;
}

// ─────────────────────────────────────────────────────────────
// 3. TOKEN METHODS
// ─────────────────────────────────────────────────────────────

/**
 * generateAccessToken — buat JWT sebagai access token
 *
 * PERBAIKAN:
 *  - `role` sekarang benar diambil dari user.role (bukan hardcode 'service')
 *    karena getUser sudah mengembalikan kolom role dari tabel users
 */
async function generateAccessToken(client, user, scope) {
  const payload = {
    sub: user?.id || client.id,
    user_id: user?.id || null,
    email: user?.email || null,
    // role dari DB: 'admin' atau 'user' untuk password grant,
    // 'service' untuk client_credentials (user.is_client === true)
    role: user?.is_client ? "service" : user?.role || "user",
    client_id: client.id,
    scope: scope || "read",
    type: "access_token",
    jti: crypto.randomBytes(16).toString("hex"),
  };

  return jwt.sign(payload, JWT_SECRET, {
    expiresIn: parseInt(ACCESS_TOKEN_TTL),
    algorithm: "HS256",
  });
}

async function generateRefreshToken(client, user, scope) {
  return crypto.randomBytes(32).toString("hex");
}

/**
 * saveToken — simpan access + refresh token ke shared_oauth_tokens
 */
async function saveToken(token, client, user) {
  try {
    await pool.execute(
      `INSERT INTO shared_oauth_tokens
         (client_id, user_id, access_token, refresh_token, scope, expires_at)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [
        client.id,
        user?.id || null,
        token.access_token || token.accessToken,
        token.refresh_token || token.refreshToken || null,
        token.scope || null,
        token.access_token_expires_at || token.accessTokenExpiresAt,
      ],
    );

    return {
      accessToken: token.access_token || token.accessToken,
      accessTokenExpiresAt:
        token.access_token_expires_at || token.accessTokenExpiresAt,
      refreshToken: token.refresh_token || token.refreshToken || null,
      scope:
        typeof token.scope === "string"
          ? token.scope.split(" ")
          : token.scope || [],
      client: { id: client.id },
      user: user || {},
    };
  } catch (error) {
    console.error("[OAuthModel] Error pada saveToken:", error.message);
    throw error;
  }
}

/**
 * getAccessToken — validasi access token (untuk introspection)
 */
async function getAccessToken(accessToken) {
  try {
    const decoded = jwt.verify(accessToken, JWT_SECRET);

    const [rows] = await pool.execute(
      `SELECT * FROM shared_oauth_tokens
       WHERE access_token = ?
         AND revoked_at IS NULL
         AND expires_at > NOW()`,
      [accessToken],
    );

    if (!rows.length) return null;

    const row = rows[0];
    return {
      accessToken,
      accessTokenExpiresAt: new Date(row.expires_at),
      scope:
        typeof row.scope === "string" ? row.scope.split(" ") : row.scope || [],
      client: { id: row.client_id },
      user: {
        id: decoded.user_id || row.user_id,
        email: decoded.email,
        role: decoded.role,
      },
    };
  } catch {
    return null;
  }
}

/**
 * getRefreshToken — ambil refresh token dari shared_oauth_tokens
 */
async function getRefreshToken(refreshToken) {
  const [rows] = await pool.execute(
    `SELECT t.*, c.grant_types
     FROM shared_oauth_tokens t
     JOIN shared_oauth_clients c ON c.client_id = t.client_id
     WHERE t.refresh_token = ?
       AND t.revoked_at IS NULL`,
    [refreshToken],
  );

  if (!rows.length) return null;

  const row = rows[0];
  return {
    refreshToken: row.refresh_token,
    refreshTokenExpiresAt: new Date(
      Date.now() + parseInt(REFRESH_TOKEN_TTL) * 1000,
    ),
    scope: row.scope,
    client: {
      id: row.client_id,
      grants: row.grant_types.split(",").map((g) => g.trim()),
    },
    user: row.user_id ? { id: row.user_id } : { id: row.client_id },
  };
}

/**
 * revokeToken — soft delete refresh token
 */
async function revokeToken(token) {
  await pool.execute(
    `UPDATE shared_oauth_tokens
     SET revoked_at = NOW()
     WHERE refresh_token = ?`,
    [token.refreshToken],
  );
  return true;
}

// ─────────────────────────────────────────────────────────────
// 4. SCOPE METHODS
// ─────────────────────────────────────────────────────────────

function verifyScope(token, scope) {
  if (!scope) return true;
  const allowedScopes = ["read", "write", "admin", "iot"];
  return scope.split(" ").every((s) => allowedScopes.includes(s));
}

function validateScope(user, client, scope) {
  const allowedScopes = ["read", "write", "admin", "iot"];
  if (!scope) return "read";
  const valid = scope.split(" ").filter((s) => allowedScopes.includes(s));
  return valid.length ? valid.join(" ") : false;
}

// ─────────────────────────────────────────────────────────────
// 5. Export model object untuk oauth2-server
// ─────────────────────────────────────────────────────────────

const OAuthModel = {
  getClient,
  getUser,
  generateAccessToken,
  generateRefreshToken,
  saveToken,
  getAccessToken,
  getRefreshToken,
  revokeToken,
  verifyScope,
  validateScope,
  getUserFromClient,
  getAuthorizationCode,
  revokeAuthorizationCode,
};

module.exports = OAuthModel;

/**
 * src/models/oauthModel.js
 * OAuth 2.0 Model — implementasi semua method yang dibutuhkan
 * oleh @node-oauth/oauth2-server.
 *
 * Grant types yang didukung (sesuai spek PDF Per. 6):
 *   - password           : login citizen (username + password → token)
 *   - client_credentials : komunikasi antar service / IoT device
 *   - refresh_token      : perpanjang sesi tanpa login ulang
 */

const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const { v4 } = require("uuid");
const db = require("../config/database");
const {
  JWT_SECRET = "smartcity-super-secret-change-in-production",
  ACCESS_TOKEN_TTL = "3600", // detik
  REFRESH_TOKEN_TTL = "604800", // 7 hari
} = process.env;

// ─────────────────────────────────────────────────────────────
// 1. CLIENT METHODS
// ─────────────────────────────────────────────────────────────

/**
 * getClient — dipanggil pertama kali untuk validasi client_id + client_secret
 */
async function getClient(clientId, clientSecret) {
  const [rows] = await db.execute(
    `SELECT * FROM shared_oauth_clients
     WHERE client_id = ? AND is_active = 1`,
    [clientId],
  );

  if (!rows.length) return null;

  const client = rows[0];

  // Jika client_secret diberikan, validasi (untuk client_credentials grant)
  if (clientSecret && client.client_secret !== clientSecret) return null;

  return {
    id: client.client_id,
    clientId: client.client_id,
    clientSecret: client.client_secret,
    grants: client.grant_types.split(",").map((g) => g.trim()),
    redirectUris: client.redirect_uris ? client.redirect_uris.split(",") : [],
  };
}

// ─────────────────────────────────────────────────────────────
// 2. USER METHODS (password grant)
// ─────────────────────────────────────────────────────────────

/**
 * getUser — validasi username (email) + password untuk password grant
 */
async function getUser(username, password) {
  const [rows] = await db.execute(
    `SELECT id, nik, name, email, role, zone_id, is_active
     FROM citizen_citizens
     WHERE email = ? AND is_active = 1`,
    [username],
  );

  if (!rows.length) return null;

  const citizen = rows[0];

  // Ambil password hash secara terpisah
  const [passRows] = await db.execute(
    `SELECT password FROM citizen_citizens WHERE id = ?`,
    [citizen.id],
  );

  const isValid = await bcrypt.compare(password, passRows[0].password);
  if (!isValid) return null;

  return {
    id: citizen.id,
    email: citizen.email,
    name: citizen.name,
    role: citizen.role,
    zone_id: citizen.zone_id,
    nik: citizen.nik,
  };
}

// ─────────────────────────────────────────────────────────────
// 3. TOKEN METHODS
// ─────────────────────────────────────────────────────────────

/**
 * generateAccessToken — buat JWT sebagai access token
 */
async function generateAccessToken(client, user, scope) {
  const payload = {
    sub: user?.id || client.clientId,
    user_id: user?.id || null,
    email: user?.email || null,
    role: user?.role || "service",
    zone_id: user?.zone_id || null,
    client_id: client.clientId,
    scope: scope || "read",
    type: "access_token",
    jti: v4(),
  };

  return jwt.sign(payload, JWT_SECRET, {
    expiresIn: parseInt(ACCESS_TOKEN_TTL),
    algorithm: "HS256",
  });
}

/**
 * generateRefreshToken — random UUID sebagai refresh token
 */
async function generateRefreshToken(client, user, scope) {
  return v4();
}

/**
 * saveToken — simpan access + refresh token ke DB
 */
async function saveToken(token, client, user) {
  const accessExpiresAt =
    token.accessTokenExpiresAt ||
    new Date(Date.now() + parseInt(ACCESS_TOKEN_TTL) * 1000);
  const refreshExpiresAt =
    token.refreshTokenExpiresAt ||
    new Date(Date.now() + parseInt(REFRESH_TOKEN_TTL) * 1000);

  await db.execute(
    `INSERT INTO shared_oauth_tokens
     (client_id, user_id, access_token, refresh_token, scope, expires_at)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [
      client.clientId,
      user?.id || null,
      token.accessToken,
      token.refreshToken || null,
      token.scope || "read",
      accessExpiresAt,
    ],
  );

  return {
    accessToken: token.accessToken,
    accessTokenExpiresAt: accessExpiresAt,
    refreshToken: token.refreshToken || null,
    refreshTokenExpiresAt: refreshExpiresAt,
    scope: token.scope || "read",
    client: { id: client.clientId },
    user: user
      ? { id: user.id, email: user.email, role: user.role }
      : { id: client.clientId },
  };
}

/**
 * getAccessToken — validasi access token (untuk introspection)
 */
async function getAccessToken(accessToken) {
  // Coba verifikasi JWT dulu
  try {
    const decoded = jwt.verify(accessToken, JWT_SECRET);

    // Cek di DB apakah token sudah direvoke
    const [rows] = await db.execute(
      `SELECT * FROM shared_oauth_tokens
       WHERE access_token = ? AND revoked_at IS NULL AND expires_at > NOW()`,
      [accessToken],
    );

    if (!rows.length) return null;

    const row = rows[0];
    return {
      accessToken: accessToken,
      accessTokenExpiresAt: new Date(row.expires_at),
      scope: row.scope,
      client: { id: row.client_id },
      user: {
        id: decoded.user_id || row.user_id,
        email: decoded.email,
        role: decoded.role,
        zone_id: decoded.zone_id,
      },
    };
  } catch {
    return null;
  }
}

/**
 * getRefreshToken — ambil refresh token dari DB
 */
async function getRefreshToken(refreshToken) {
  const [rows] = await db.execute(
    `SELECT t.*, c.grant_types
     FROM shared_oauth_tokens t
     JOIN shared_oauth_clients c ON c.client_id = t.client_id
     WHERE t.refresh_token = ? AND t.revoked_at IS NULL`,
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
  await db.execute(
    `UPDATE shared_oauth_tokens SET revoked_at = NOW()
     WHERE refresh_token = ?`,
    [token.refreshToken],
  );
  return true;
}

// ─────────────────────────────────────────────────────────────
// 4. SCOPE METHODS
// ─────────────────────────────────────────────────────────────

/**
 * verifyScope — validasi scope yang diminta
 */
function verifyScope(token, scope) {
  if (!scope) return true;

  const allowedScopes = ["read", "write", "admin", "iot"];
  const requestedScopes = scope.split(" ");

  return requestedScopes.every((s) => allowedScopes.includes(s));
}

/**
 * validateScope — validasi scope saat request token
 */
function validateScope(user, client, scope) {
  const allowedScopes = ["read", "write", "admin", "iot"];
  if (!scope) return "read";

  const requestedScopes = scope.split(" ");
  const validScopes = requestedScopes.filter((s) => allowedScopes.includes(s));

  return validScopes.length ? validScopes.join(" ") : false;
}

// ─────────────────────────────────────────────────────────────
// 5.  model object untuk oauth2-server
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
};

module.s = OAuthModel;

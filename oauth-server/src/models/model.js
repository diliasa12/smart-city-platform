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
const pool = require("../config/database");
const {
  JWT_SECRET = "kelompok3",
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
  try {
    const [rows] = await pool.execute(
      "SELECT client_id, client_secret, grant_types, redirect_uris, is_active FROM shared_oauth_clients WHERE client_id = ?",
      [clientId],
    );

    if (rows.length === 0) return null;

    const client = rows[0];

    // Proteksi jika client dinonaktifkan
    if (!client.is_active) return null;

    // Validasi secret jika disertakan dalam request
    if (clientSecret && client.client_secret !== clientSecret) {
      return null;
    }

    // Pembersihan total dari spasi tak terlihat yang merusak validasi library
    const cleanedGrants = client.grant_types.split(",").map((g) => g.trim());
    const cleanedRedirects = client.redirect_uris
      ? client.redirect_uris.split(",").map((r) => r.trim())
      : [];

    // Format data yang super-aman untuk semua jenis grant type di library
    return {
      id: client.client_id, // Dibutuhkan oleh handler token
      clientId: client.client_id, // DIwajibkan oleh beberapa internal handler password grant
      grants: cleanedGrants, // Array bersih tanpa spasi: ["password", "client_credentials"]
      grantTypes: cleanedGrants, // Fallback versi library lama/baru
      redirectUris: cleanedRedirects,
    };
  } catch (error) {
    console.error("Error pada getClient Model:", error.message);
    throw error;
  }
}
async function getUserFromClient(client) {
  try {
    // Karena ini Machine-to-Machine, tidak ada user (manusia) yang login.
    // Kita kembalikan objek dengan id: null agar lolos validasi library.
    return { id: null, username: client.id, is_client: true };
  } catch (error) {
    console.error("Error pada getUserFromClient Model:", error.message);
    throw error;
  }
}
// ─────────────────────────────────────────────────────────────
// 2. USER METHODS (password grant)
// ─────────────────────────────────────────────────────────────

/**
 * getUser — validasi username (email) + password untuk password grant
 */
async function getUser(username, password) {
  console.log("=== DEBUG OAUTH LOGIN ===");
  console.log("1. Username dari Postman:", username);
  console.log("2. Password plaintext dari Postman:", password);
  const [rows] = await pool.execute(
    `SELECT id, nik, name, email, role, zone_id, is_active
     FROM citizen_citizens
     WHERE email = ? AND is_active = 1`,
    [username],
  );
  console.log("3. Hasil Query User (rows):", rows);

  if (!rows.length) {
    console.log(
      "❌ Error: User tidak ditemukan di database dengan email tersebut atau is_active != 1",
    );
    return null;
  }
  const citizen = rows[0];

  // Ambil password hash secara terpisah
  const [passRows] = await pool.execute(
    `SELECT password FROM citizen_citizens WHERE id = ?`,
    [citizen.id],
  );
  console.log("4. Password Hash dari DB:", passRows[0]?.password);
  const isValid = await bcrypt.compare(password, passRows[0].password);
  console.log("5. Apakah Bcrypt Match?:", isValid);
  if (!isValid) {
    console.log("❌ Error: Password tidak cocok menurut Bcrypt!");
    return null;
  }
  console.log("✅ Sukses: Kredensial valid!");
  return {
    id: citizen.id,
    email: citizen.email,
    name: citizen.name,
    role: citizen.role,
    zone_id: citizen.zone_id,
    nik: citizen.nik,
  };
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
 */
async function generateAccessToken(client, user, scope) {
  const payload = {
    sub: user?.id || client.id,
    user_id: user?.id || null,
    email: user?.email || null,
    role: user?.role || "service",
    zone_id: user?.zone_id || null,
    client_id: client.id,
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
 * saveToken — simpan access + refresh token ke pool
 */
async function saveToken(token, client, user) {
  try {
    // Menggunakan nama kolom yang sesuai skema Anda: expires_at, access_token, refresh_token
    await pool.execute(
      "INSERT INTO shared_oauth_tokens (client_id, user_id, access_token, refresh_token, scope, expires_at) VALUES (?, ?, ?, ?, ?, ?)",
      [
        client.id, // client_id (berasal dari mapped id di getClient)
        user ? user.id : null, // user_id (NULL jika grant-type client_credentials)
        token.access_token || token.accessToken,
        token.refresh_token || token.refreshToken || null,
        token.scope || null,
        token.access_token_expires_at || token.accessTokenExpiresAt, // mysql2 otomatis mengonversi JS Date ke DATETIME
      ],
    );

    // Kembalikan objek token yang terstruktur untuk dibaca oleh Router Express Anda
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
    console.error("Error pada saveToken Model:", error.message);
    throw error;
  }
}

/**
 * getAccessToken — validasi access token (untuk introspection)
 */
async function getAccessToken(accessToken) {
  // Coba verifikasi JWT dulu
  try {
    const decoded = jwt.verify(accessToken, JWT_SECRET);

    // Cek di pool apakah token sudah direvoke
    const [rows] = await pool.execute(
      `SELECT * FROM shared_oauth_tokens
       WHERE access_token = ? AND revoked_at IS NULL AND expires_at > NOW()`,
      [accessToken],
    );

    if (!rows.length) return null;

    const row = rows[0];
    return {
      accessToken: accessToken,
      accessTokenExpiresAt: new Date(row.expires_at),
      scope:
        typeof row.scope === "string" ? row.scope.split(" ") : row.scope || [],
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
 * getRefreshToken — ambil refresh token dari pool
 */
async function getRefreshToken(refreshToken) {
  const [rows] = await pool.execute(
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
  await pool.execute(
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
// 5. export model object untuk oauth2-server
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

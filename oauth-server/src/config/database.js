/**
 * src/config/database.js
 * Koneksi MySQL menggunakan mysql2/promise
 * Tabel yang digunakan: shared_oauth_clients, shared_oauth_tokens, citizen_citizens
 */

const mysql = require("mysql2/promise");

const {
  DB_HOST = "127.0.0.1",
  DB_PORT = "3306",
  DB_NAME = "smartcity",
  DB_USER = "root",
  DB_PASS = "rootpass",
} = process.env;

const pool = mysql.createPool({
  host: DB_HOST,
  port: parseInt(DB_PORT),
  database: DB_NAME,
  user: DB_USER,
  password: DB_PASS,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  timezone: "Z",
});

// Test koneksi saat startup
pool
  .getConnection()
  .then((conn) => {
    console.log("[DB] MySQL connected successfully");
    conn.release();
  })
  .catch((err) => {
    console.error("[DB] MySQL connection failed:", err.message);
  });

module.exports = pool;

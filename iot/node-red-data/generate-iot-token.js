#!/usr/bin/env node
/**
 * generate-iot-token.js
 *
 * Jalankan sekali untuk generate IOT_TOKEN yang dibutuhkan Node-RED:
 *   node generate-iot-token.js
 *
 * Lalu salin output ke .env sebagai nilai IOT_TOKEN=...
 *
 * Requires: npm install jsonwebtoken  (atau pakai node_modules gateway)
 */

const jwt = require("jsonwebtoken");

const JWT_SECRET = process.env.JWT_SECRET || "kelompok3";

const token = jwt.sign(
  {
    role: "iot",
    client: "node-red-bridge",
    iat: Math.floor(Date.now() / 1000),
  },
  JWT_SECRET,
  { expiresIn: "365d" },
);

console.log("\n=== IOT TOKEN UNTUK NODE-RED ===");
console.log("\nSalin baris berikut ke file .env kamu:\n");
console.log(`IOT_TOKEN=${token}`);
console.log("\n================================\n");
console.log("Token ini berlaku 1 tahun. Generate ulang setelah expire.");

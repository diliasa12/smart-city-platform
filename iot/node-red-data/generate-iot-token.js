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

const mysql = require("mysql2/promise");
const bcrypt = require("bcrypt");

async function runSeeder() {
  const pool = mysql.createPool({
    host: "localhost",
    user: "root",
    password: "rootpass",
    database: "smartcity",
    port: 3306,
  });

  // 1. Generate hash menggunakan library Bcrypt yang sama dengan internal app
  const hashedPassword = await bcrypt.hash("Password123!", 10);

  console.log("⏳ Memulai seeding data warga...");

  // 2. Insert data menggunakan hash yang sudah jadi
  const citizens = [
    [
      1,
      "3171010101010001",
      "Andi Saputra",
      "andi.saputra@email.com",
      "08111000001",
      1,
      "citizen",
      hashedPassword,
    ],
    [
      2,
      "3171010101010002",
      "Budi Santoso",
      "budi.santoso@email.com",
      "08111000002",
      1,
      "citizen",
      hashedPassword,
    ],
    [
      3,
      "3171010101010003",
      "Citra Dewi",
      "citra.dewi@email.com",
      "08111000003",
      1,
      "citizen",
      hashedPassword,
    ],
  ];

  for (const citizen of citizens) {
    await pool.execute(
      `INSERT INTO citizen_citizens (id, nik, name, email, phone, zone_id, role, password) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE password = VALUES(password)`,
      citizen,
    );
  }

  console.log(
    "✅ Seeding selesai! Semua password warga sekarang otomatis match dengan Bcrypt Node.js.",
  );
  await pool.end();
}

runSeeder().catch(console.error);

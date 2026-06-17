const mysql = require("mysql2/promise");
const bcrypt = require("bcryptjs");

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

  console.log("Memulai seeding data warga...");

  // 2. Insert data menggunakan hash yang sudah jadi
  const admin = [
    [
      1,
      "Andi Saputra",
      "andi.saputra@email.com",
      "08111000001",
      hashedPassword,
    ],
    [
      2,
      "Budi Santoso",
      "budi.santoso@email.com",
      "08111000002",
      hashedPassword,
    ],
    [3, "Citra Dewi", "citra.dewi@email.com", "08111000003", hashedPassword],
  ];

  for (const citizen of admin) {
    await pool.execute(
      `INSERT INTO admin_accounts (id,  name, email, phone,password) 
       VALUES (?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE password = VALUES(password)`,
      citizen,
    );
  }

  console.log(
    "Seeding selesai! Semua password warga sekarang otomatis match dengan Bcrypt Node.js.",
  );
  await pool.end();
}

runSeeder().catch(console.error);

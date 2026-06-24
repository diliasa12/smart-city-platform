

const mysql = require("mysql2/promise");
const bcrypt = require("bcryptjs");

async function runSeeder() {
  const pool = mysql.createPool({
    host: process.env.DB_HOST || "localhost",
    user: process.env.DB_USER || "root",
    password: process.env.DB_PASS || "rootpass",
    database: process.env.DB_NAME || "smartcity",
    port: parseInt(process.env.DB_PORT || "3306"),
  });

  const hashedPassword = await bcrypt.hash("Password123!", 10);

  console.log("[Seeder] Memulai seeding data users...");

  
  const users = [
    {
      id: 1,
      name: "Super Admin SmartCity",
      email: "admin@smartcity.id",
      password: hashedPassword,
      phone: "081234567890",
      role: "admin",
    },
    {
      id: 2,
      name: "Budi Santoso",
      email: "budi@gmail.com",
      password: hashedPassword,
      phone: "081299998888",
      role: "user",
    },
    {
      id: 3,
      name: "Siti Aminah",
      email: "siti@gmail.com",
      password: hashedPassword,
      phone: "081277776666",
      role: "user",
    },
  ];

  for (const u of users) {
    await pool.execute(
      `INSERT INTO users (id, name, email, password, phone, role)
       VALUES (?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE
         password = VALUES(password),
         phone    = VALUES(phone),
         role     = VALUES(role)`,
      [u.id, u.name, u.email, u.password, u.phone, u.role],
    );
    console.log(`[Seeder] Upserted user: ${u.email} (${u.role})`);
  }

  console.log(
    "[Seeder] Selesai! Semua password match dengan bcryptjs Node.js.",
  );
  await pool.end();
}

runSeeder().catch((err) => {
  console.error("[Seeder] Error:", err.message);
  process.exit(1);
});

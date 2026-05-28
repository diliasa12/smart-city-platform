const request = require("supertest");
const jwt = require("jsonwebtoken");

process.env.JWT_SECRET = "test-secret";
process.env.CITIZEN_URL = "http://localhost:19999";
process.env.TRAFFIC_URL = "http://localhost:19999";
process.env.ENV_URL = "http://localhost:19999";
process.env.ML_URL = "http://localhost:19999";
process.env.OAUTH_URL = "http://localhost:19999";

const app = require("../src/index");

// Helper: buat valid JWT
function makeToken(payload = {}) {
  return jwt.sign(
    { sub: 1, user_id: 1, role: "citizen", ...payload },
    process.env.JWT_SECRET,
    { expiresIn: "1h" },
  );
}

// ──────────────────────────────────────────────────────────
describe("GET /health/gateway", () => {
  it("returns 200 dengan status healthy", async () => {
    const res = await request(app).get("/health/gateway");
    expect(res.status).toBe(200);
    expect(res.body.status).toBe("success");
    expect(res.body.data.gateway).toBe("healthy");
  });
});

// ──────────────────────────────────────────────────────────
describe("JWT Middleware", () => {
  it("menolak request tanpa Authorization header → 401", async () => {
    const res = await request(app).get("/api/citizens");
    expect(res.status).toBe(401);
    expect(res.body.status).toBe("error");
    expect(res.body.code).toBe(401);
  });

  it("menolak token malformed → 401", async () => {
    const res = await request(app)
      .get("/api/citizens")
      .set("Authorization", "Bearer token-salah-bukan-jwt");
    expect(res.status).toBe(401);
  });

  it("menolak token expired → 401", async () => {
    const expiredToken = jwt.sign(
      { sub: 1, role: "citizen" },
      process.env.JWT_SECRET,
      { expiresIn: "-1s" },
    );
    const res = await request(app)
      .get("/api/citizens")
      .set("Authorization", `Bearer ${expiredToken}`);
    expect(res.status).toBe(401);
    expect(res.body.message).toMatch(/kadaluarsa/i);
  });

  it("menerima request dengan token valid (502 karena upstream mock mati)", async () => {
    const token = makeToken();
    const res = await request(app)
      .get("/api/citizens")
      .set("Authorization", `Bearer ${token}`);
    // Upstream tidak ada → 502 (bukan 401/403)
    expect([502, 504]).toContain(res.status);
  });
});

// ──────────────────────────────────────────────────────────
describe("404 handler", () => {
  it("mengembalikan 404 untuk endpoint tidak dikenal", async () => {
    const token = makeToken();
    const res = await request(app)
      .get("/rute-tidak-ada")
      .set("Authorization", `Bearer ${token}`);
    expect(res.status).toBe(404);
    expect(res.body.status).toBe("error");
    expect(res.body.code).toBe(404);
  });
});

// ──────────────────────────────────────────────────────────
describe("Standard response format", () => {
  it("setiap response mengandung field: status, code, data, message, timestamp, service", async () => {
    const res = await request(app).get("/health/gateway");
    const { status, code, data, message, timestamp, service } = res.body;
    expect(status).toBeDefined();
    expect(code).toBeDefined();
    expect(data).toBeDefined();
    expect(message).toBeDefined();
    expect(timestamp).toBeDefined();
    expect(service).toBe("api-gateway");
  });
});

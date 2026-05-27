import rateLimit, { ipKeyGenerator } from "express-rate-limit";
const globalLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
  message: {
    status: 429,
    error: "Too Many Requests",
    message:
      "Terlalu banyak request dari IP Anda. Silakan coba lagi setelah 15 menit.",
  },
});

const tokenLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 500,
  standardHeaders: true,
  legacyHeaders: false,

  keyGenerator: (req, res) => {
    const authHeader = req.headers.authorization || req.headers.Authorization;
    if (authHeader && authHeader.startsWith("Bearer ")) {
      const token = authHeader.split(" ")[1];
      return token;
    }
    return ipKeyGenerator(req.ip);
  },
  message: {
    status: 429,
    error: "Too Many Requests",
    message: "Reached limit (500/hour max). Try again later.",
  },
});

export { globalLimiter, tokenLimiter };

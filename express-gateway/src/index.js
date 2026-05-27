import express from "express";
import "dotenv/config";
import { createProxyMiddleware } from "http-proxy-middleware";
import { globalLimiter, tokenLimiter } from "./utils/rateLimit.js";
const app = express();
const port = process.env.PORT_GATEWAY || 3000;
app.use(globalLimiter);
app.get("/", (req, res) => {
  res.send("Hello World!");
});
app.use(
  "/auth",
  tokenLimiter,
  createProxyMiddleware({
    changeOrigin: true,
    target: process.env.OAUTH_URL,
  }),
);

app.listen(port, () => {
  console.log(`Example app listening on port ${port}`);
});

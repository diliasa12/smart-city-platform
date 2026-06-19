# ── Stage 1: dependencies ──────────────────────────────────
FROM node:20-alpine AS deps

WORKDIR /app

COPY package*.json ./
RUN npm ci --omit=dev

# ── Stage 2: production image ──────────────────────────────
FROM node:20-alpine AS runner

WORKDIR /app

# Buat user non-root untuk keamanan
RUN addgroup -S appgroup && adduser -S appuser -G appgroup

# Copy deps dari stage sebelumnya
COPY --from=deps /app/node_modules ./node_modules

# Copy source
COPY src/ ./src/
COPY package.json ./

# Buat folder logs
RUN mkdir -p logs && chown -R appuser:appgroup /app

USER appuser

EXPOSE 3000


CMD ["node", "src/index.js"]
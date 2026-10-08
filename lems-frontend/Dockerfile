# ── Stage 1: Install dependencies ─────────────────────────
FROM node:22-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --legacy-peer-deps --no-audit --no-fund 2>&1 || \
    (echo "npm ci failed, falling back to npm install..." && \
     npm install --legacy-peer-deps --no-audit --no-fund 2>&1)

# ── Stage 2: Build ────────────────────────────────────────
FROM node:22-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Accept NEXT_PUBLIC_* build args (inlined at build time)
ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_APP_NAME=LEAN ENTERPRISE

# Generate Prisma client
RUN npx prisma generate

# Build Next.js (standalone output for minimal image)
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

# ── Stage 3: Production runner ────────────────────────────
FROM node:22-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs

# Copy public assets
COPY --from=builder /app/public ./public

# Copy standalone server
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

# Copy Prisma files needed at runtime (schema + CLI for db push on startup)
COPY --from=builder /app/prisma ./prisma
COPY --from=builder /app/node_modules/.prisma ./node_modules/.prisma
COPY --from=builder /app/node_modules/@prisma ./node_modules/@prisma
COPY --from=builder /app/node_modules/prisma ./node_modules/prisma

# Startup script: push schema then start Next.js
COPY <<'EOF' /app/start.sh
#!/bin/sh
set -e
echo "⏳ Running Prisma db push..."
npx prisma db push --accept-data-loss || echo "⚠️  Prisma db push failed (may already exist)"
echo "🚀 Starting Next.js server..."
exec node server.js
EOF
RUN chmod +x /app/start.sh

USER nextjs
EXPOSE 3000
ENV PORT=3000
ENV HOSTNAME="0.0.0.0"

CMD ["/app/start.sh"]
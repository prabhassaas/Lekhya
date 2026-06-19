FROM node:20-alpine AS base
RUN npm install -g pnpm

FROM base AS deps
WORKDIR /app
COPY package.json pnpm-workspace.yaml ./
COPY packages/shared/package.json ./packages/shared/
COPY apps/api/package.json ./apps/api/
RUN pnpm install --frozen-lockfile

FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY --from=deps /app/packages/shared/node_modules ./packages/shared/node_modules
COPY --from=deps /app/apps/api/node_modules ./apps/api/node_modules
COPY . .
RUN pnpm --filter @lekhya/shared build
RUN pnpm --filter @lekhya/api build

FROM node:20-alpine AS runner
RUN npm install -g pnpm
WORKDIR /app
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/packages/shared ./packages/shared
COPY --from=builder /app/apps/api ./apps/api
COPY package.json pnpm-workspace.yaml ./

COPY apps/api/src/db/migrations /app/apps/api/dist/db/migrations
COPY apps/api/entrypoint.sh /app/apps/api/entrypoint.sh
RUN chmod +x /app/apps/api/entrypoint.sh

WORKDIR /app/apps/api
CMD ["/bin/sh", "entrypoint.sh"]

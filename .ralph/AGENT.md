# Ralph Agent Configuration

## Prerequisites
- Node.js v22.22.0 (see `.nvmrc`)
- npm (lockfile: `package-lock.json`)

## Build Instructions

```bash
# Install dependencies (from repo root)
npm install

# Build the backend
npx nx build backend

# Build all apps
npx nx run-many -t build
```

## Test Instructions

```bash
# Run backend unit tests
npx nx test backend

# Run backend e2e tests
npx nx e2e backend-e2e

# Run all tests
npx nx run-many -t test
```

## Run Instructions

```bash
# Start backend in dev mode (with hot reload)
npx nx serve backend

# Start in production mode
npx nx serve backend --configuration=production
```

## Lint Instructions

```bash
# Lint backend
npx nx lint backend
```

## Project Structure

```
apps/
  backend/           # NestJS 11 orchestration API (migration target)
    src/
      app/            # App module, controller, service
      main.ts         # Bootstrap entry point
  backend-e2e/       # Jest e2e tests for backend
  wally/             # WordPress plugin (React + PHP)
  frontend/          # Next.js marketing site (not yet created)

backend/             # OLD Express backend (migration source — to be removed)
  src/
    routes/          # Express route handlers
    services/        # Business logic (LLM, prompts, tools, knowledge)
    middleware/      # Auth + rate limiting
    knowledge/       # ~60 WordPress knowledge .md files
    utils/           # Logger
    config.js        # Environment config
```

## NestJS Conventions

- Controllers handle HTTP routing (`@Controller`, `@Post`, `@Get`)
- Services contain business logic (`@Injectable`)
- Guards replace Express middleware for auth (`@UseGuards`)
- Interceptors for cross-cutting concerns (logging, rate limiting)
- ConfigModule for environment variables (replaces `dotenv/config.js`)
- All code in strict TypeScript (no plain JS)
- Global prefix `/api` is set in `main.ts`

## Environment Variables

Required (create `.env` in repo root or `apps/backend/`):
```
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
PORT=3000
SKIP_LICENSE_VALIDATION=true
RATE_LIMIT_PER_SITE_PER_MINUTE=30
RATE_LIMIT_PER_SITE_PER_DAY=1000
```

## Notes
- All commands run from the repo root using Nx
- The backend serves on port 3000 with `/api` global prefix
- SSE streaming is used for chat responses (not WebSockets)
- Update this file when build process changes

# NX Monorepo Setup Guide

Migrate from the WordPress installation repo to a clean NX monorepo at `~/Projects/goodtime-platform/`.

---

## Target Structure

```
~/Projects/goodtime-platform/
├── apps/
│   ├── frontend/              # Next.js marketing landing page (new)
│   ├── backend/               # Node.js API server (moved from backend/)
│   └── wp-plugin/             # WP plugin (moved from wp-content/plugins/wp-ai-assistant/)
├── nx.json
├── package.json               # Root: NX + shared tooling only
├── .nvmrc                     # Node 20
├── .gitignore
├── .ralphrc
├── CLAUDE.md
├── README.md
├── pencil-halo.pen
└── prd/
```

The WordPress installation (Local by Flywheel) stays as the runtime. The plugin is connected via symlink.

---

## Step 1 — Create the NX Workspace

```bash
mkdir -p ~/Projects
cd ~/Projects
npx create-nx-workspace@latest goodtime-platform \
  --preset=empty \
  --pm=npm \
  --nxCloud=skip \
  --ci=skip
```

---

## Step 2 — Add Next.js and Generate Frontend App

```bash
cd ~/Projects/goodtime-platform
npm install -D @nx/next
npx nx generate @nx/next:app apps/frontend \
  --style=css \
  --appDir=true \
  --no-interactive
```

---

## Step 3 — Move the Backend

```bash
cp -r "/Users/andrewoplas/Local Sites/goodtime/app/public/backend" \
      ~/Projects/goodtime-platform/apps/backend
```

Create `apps/backend/project.json`:

```json
{
  "name": "backend",
  "$schema": "../../node_modules/nx/schemas/project-schema.json",
  "sourceRoot": "apps/backend/src",
  "projectType": "application",
  "targets": {
    "serve": {
      "executor": "nx:run-commands",
      "options": {
        "command": "node --watch src/index.js",
        "cwd": "apps/backend"
      }
    },
    "test": {
      "executor": "nx:run-commands",
      "options": {
        "command": "node --experimental-vm-modules node_modules/.bin/jest",
        "cwd": "apps/backend"
      }
    },
    "lint": {
      "executor": "nx:run-commands",
      "options": {
        "command": "eslint src/",
        "cwd": "apps/backend"
      }
    }
  }
}
```

---

## Step 4 — Move the WordPress Plugin

```bash
cp -r "/Users/andrewoplas/Local Sites/goodtime/app/public/wp-content/plugins/wp-ai-assistant" \
      ~/Projects/goodtime-platform/apps/wp-plugin
```

Create `apps/wp-plugin/project.json`:

```json
{
  "name": "wp-plugin",
  "$schema": "../../node_modules/nx/schemas/project-schema.json",
  "sourceRoot": "apps/wp-plugin/src",
  "projectType": "application",
  "targets": {
    "build": {
      "executor": "nx:run-commands",
      "options": {
        "commands": [
          "npm run build",
          "npm run build:css"
        ],
        "cwd": "apps/wp-plugin",
        "parallel": false
      }
    },
    "serve": {
      "executor": "nx:run-commands",
      "options": {
        "commands": [
          "npm run start",
          "npm run watch:css"
        ],
        "cwd": "apps/wp-plugin",
        "parallel": true
      }
    }
  }
}
```

---

## Step 5 — Copy Root Files

```bash
WP_ROOT="/Users/andrewoplas/Local Sites/goodtime/app/public"
NX_ROOT=~/Projects/goodtime-platform

cp "$WP_ROOT/CLAUDE.md"        "$NX_ROOT/"
cp "$WP_ROOT/README.md"        "$NX_ROOT/"
cp "$WP_ROOT/.nvmrc"           "$NX_ROOT/"
cp "$WP_ROOT/.ralphrc"         "$NX_ROOT/"
cp "$WP_ROOT/pencil-halo.pen"  "$NX_ROOT/"
cp -r "$WP_ROOT/prd"           "$NX_ROOT/"
```

---

## Step 6 — Set Up .gitignore

Replace the generated `.gitignore` at `~/Projects/goodtime-platform/.gitignore` with:

```
# NX
.nx/cache
.nx/workspace-data

# Dependencies
node_modules/
apps/backend/node_modules/
apps/wp-plugin/node_modules/

# Build outputs
dist/
apps/frontend/.next/
apps/frontend/out/

# PHP
apps/wp-plugin/vendor/

# Environment & secrets
.env
apps/backend/.env

# Logs & OS
*.log
.DS_Store
```

---

## Step 7 — Symlink Plugin to WordPress

This connects the NX repo's plugin source directly to Local by Flywheel's WordPress installation.

```bash
# Remove the original plugin directory (already copied to NX repo in Step 4)
rm -rf "/Users/andrewoplas/Local Sites/goodtime/app/public/wp-content/plugins/wp-ai-assistant"

# Create the symlink
ln -s ~/Projects/goodtime-platform/apps/wp-plugin \
      "/Users/andrewoplas/Local Sites/goodtime/app/public/wp-content/plugins/wp-ai-assistant"
```

Verify the symlink works:

```bash
ls -la "/Users/andrewoplas/Local Sites/goodtime/app/public/wp-content/plugins/" | grep wp-ai-assistant
# Should show: wp-ai-assistant -> ~/Projects/goodtime-platform/apps/wp-plugin
```

---

## Step 8 — Initialize Git

```bash
cd ~/Projects/goodtime-platform
git init
git add .
git commit -m "chore: initialize NX monorepo with backend, wp-plugin, and frontend scaffold"
```

Then create a new repo on GitHub/GitLab and push:

```bash
git remote add origin <your-new-repo-url>
git push -u origin main
```

---

## Daily Dev Commands

| Command | What it does |
|---------|-------------|
| `npx nx serve backend` | Start backend API server (port 3100, watch mode) |
| `npx nx serve wp-plugin` | Watch JS + CSS changes for the plugin |
| `npx nx serve frontend` | Start Next.js dev server |
| `npx nx run-many -t build` | Build all three apps |
| `npx nx test backend` | Run backend Jest tests (178 tests) |
| `npx nx lint backend` | Lint backend source |

---

## Notes

- Each app keeps its own `package.json` + `node_modules`. Run `npm install` inside `apps/backend/` and `apps/wp-plugin/` after moving them.
- The old WordPress repo (`/app/public/`) can be abandoned — the new NX repo is the canonical source.
- The backend still needs its own `.env` file at `apps/backend/.env` (copy from `.env.example`).

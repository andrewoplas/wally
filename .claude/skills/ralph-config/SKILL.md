---
name: ralph-config
description: Update Ralph Loop configuration files (.ralph/ directory). Use when the user asks to configure Ralph, update PROMPT.md, fix_plan.md, AGENT.md, specs, or set up a new Ralph mission.
argument-hint: [what to update]
---

# Ralph Loop Configuration Skill

You are helping the user update their **Ralph Loop** configuration — an autonomous AI development agent that runs in a loop via Claude Code CLI. Ralph reads config files from the `.ralph/` directory and executes tasks autonomously.

**Repository:** https://github.com/frankbria/ralph-claude-code
**Version:** v0.11.5

## CLI Quick Reference

```bash
# Running Ralph
ralph --monitor          # Recommended: tmux dashboard + loop
ralph --live             # Stream Claude output in real-time
ralph --verbose          # Detailed logging
ralph --calls 50         # Override hourly API limit
ralph --timeout 20       # Execution timeout in minutes (1-120)
ralph --resume <id>      # Resume existing session
ralph --no-continue      # Start fresh, don't resume
ralph --reset-session    # Clear session data
ralph --status           # Show current status

# Project setup
ralph-enable             # Interactive wizard for existing project
ralph-enable --from prd ./docs/requirements.md  # Import from PRD
ralph-import <file> [project]  # Convert specs to Ralph format
ralph-setup <name>       # Create new Ralph project from scratch
```

## How Ralph Works

Ralph operates in a continuous loop cycle:
1. **Read** — Loads PROMPT.md, fix_plan.md, AGENT.md, and specs/
2. **Execute** — Runs Claude Code with context and picks first unchecked `[ ]` task
3. **Track** — Updates task list, logs results
4. **Evaluate** — Checks exit conditions via dual-condition gate
5. **Repeat** — Continues until complete or limits reached

### Exit Detection (Dual-Condition Gate)

Ralph exits ONLY when BOTH conditions are true:
- `completion_indicators >= 2` (heuristic from natural language patterns)
- `EXIT_SIGNAL: true` in the RALPH_STATUS block

Other exit triggers: all fix_plan.md tasks marked `[x]`, circuit breaker trip, API 5-hour limit reached.

## Ralph File Reference

All config lives in `.ralph/` at the project root. Here's what each file does and how to edit it:

### 1. PROMPT.md — Mission & Instructions (MOST COMMON EDIT)

**Purpose:** High-level instructions Ralph reads at the START of every loop iteration. This is the "brain" of the mission.

**What to include:**
- Project context (name, type, tech stack)
- Current mission (what Ralph should accomplish)
- Key principles and constraints
- Quality standards
- Loop flow (what Ralph does each iteration)
- Status reporting format
- Protected files list

**What NOT to include:**
- Step-by-step task lists (that's fix_plan.md)
- Build commands (that's AGENT.md)
- Detailed specs (that's specs/)

**Structure template:**
```markdown
# Ralph Development Instructions

## Context
You are Ralph, an autonomous AI development agent working on the **<project>** project.
**Project Type:** <type>
**Framework:** <framework>

## Current Mission: <Mission Title>
<Description of what Ralph should accomplish across all loops>

## Technology Stack
- <tech details relevant to the mission>

## Key Principles
1. <Principle about work cadence, e.g., ONE TASK PER LOOP>
2. <Principle about verification/quality>
3. <Principle about safety>
4. <Principle about style/conventions>

## Quality Standards
- <Standard 1>
- <Standard 2>

## Loop Flow
1. Read fix_plan.md → find the FIRST unchecked `[ ]` task
2. <Verification/research step if needed>
3. <Implementation step>
4. <Commit step with message format>
5. Mark that ONE task as `[x]` in fix_plan.md
6. Output status block and STOP

## Protected Files (DO NOT MODIFY)
- `.ralph/` (entire directory and all contents)
- `.ralphrc` (project configuration)

## Build & Run
See AGENT.md for build and run instructions.

## Status Reporting
Output this after completing each task (or if blocked):
\```
---RALPH_STATUS---
STATUS: IN_PROGRESS | COMPLETE | BLOCKED
TASKS_COMPLETED_THIS_LOOP: 1
FILES_MODIFIED: <number>
TESTS_STATUS: PASSED | FAILED | NOT_RUN
WORK_TYPE: IMPLEMENTATION | BUGFIX | REFACTOR
EXIT_SIGNAL: false
RECOMMENDATION: <next unchecked task from fix_plan.md>
---END_RALPH_STATUS---
\```
- `EXIT_SIGNAL: false` while unchecked tasks remain
- `EXIT_SIGNAL: true` only when ALL tasks in fix_plan.md are `[x]`
```

### 2. fix_plan.md — Task Checklist

**Purpose:** Prioritized checklist of tasks Ralph works through. Ralph picks the first unchecked `[ ]` item each loop.

**Format:**
```markdown
# Fix Plan — <Mission Title>

## Tier 1: <Highest Priority Group>

- [ ] **1.1 Task Name** — `optional-file-reference`
  - Details about what to do
  - Expected output or deliverable
  - Special notes (e.g., "requires confirmation")

- [ ] **1.2 Another Task** — `file-reference`
  - Details

## Tier 2: <Next Priority Group>

- [ ] **2.1 Task Name**
  - Details

## Discovered
<!-- Ralph adds discovered tasks here -->
```

**Task sizing (Goldilocks Principle):**
- Too large: "Build entire auth system" — Ralph won't know where to start
- Too small: "Create file X, then add import Y" — wastes loop iterations
- Just right: "Create auth routes with POST /login and /logout; add JWT middleware; add refresh token endpoint"

**Key rules:**
- Each task = one meaningful work cycle
- Use `[ ]` for pending, `[x]` for completed (Ralph marks these)
- Group by priority tiers
- Bold task numbers for readability
- Include enough context so Ralph can work autonomously
- The `## Discovered` section is where Ralph adds tasks it finds during implementation

### 3. AGENT.md — Build & Project Configuration

**Purpose:** Build commands, test commands, project structure, and conventions. Ralph reads this to understand HOW to build/test/run the project.

**What to include:**
- Prerequisites (Node version, PHP version, etc.)
- Build instructions (install, build, dev commands)
- Test instructions
- Lint instructions
- Project structure (directory tree)
- Coding conventions relevant to the mission
- Important notes about the build process

**Structure template:**
```markdown
# Ralph Agent Configuration

## Prerequisites
- <runtime and versions>

## Build Instructions
\```bash
<install and build commands>
\```

## Test Instructions
\```bash
<test commands>
\```

## Lint Instructions
\```bash
<lint commands>
\```

## Project Structure
\```
<relevant directory tree>
\```

## Conventions
- <coding style notes>
- <naming conventions>
- <return format standards>

## Notes
- <important build/run notes>
```

### 4. specs/ — Detailed Specifications

**Purpose:** Supplementary docs for complex features that need more detail than PROMPT.md provides.

**When to use:**
- Complex algorithms or business logic
- API contracts that must be followed exactly
- Multi-step verification processes
- Data model definitions

**When to skip:**
- Simple CRUD operations
- Well-documented features
- Tasks that PROMPT.md describes adequately

**Sub-directories:**
- `specs/` — Feature-specific specs
- `specs/stdlib/` — Reusable team patterns (error handling, logging, testing conventions)

### 5. State Files (Usually Read-Only)

These are managed by Ralph during execution. Only edit to reset state:

| File | Purpose | When to edit manually |
|------|---------|----------------------|
| `progress.json` | Current task status | Reset to `{"status":"pending"}` to restart |
| `status.json` | Loop metrics and counters | Usually don't edit |
| `.ralph_session` | Session state | Clear to force new session |
| `.circuit_breaker_state` | Fault tolerance state | Reset if stuck in OPEN state |
| `.call_count` | API calls this hour | Usually don't edit |
| `.loop_start_sha` | Git SHA at loop start | Usually don't edit |

### 6. .ralphrc — Ralph Settings (Rarely Changed)

**Purpose:** Operational settings for Ralph itself. Defaults work for most projects.

**Common settings:**
```bash
PROJECT_NAME="my-project"
PROJECT_TYPE="typescript"            # or php, python, etc.
CLAUDE_CODE_CMD="claude"             # Claude CLI command
MAX_CALLS_PER_HOUR=100               # Rate limit
CLAUDE_TIMEOUT_MINUTES=15            # Execution timeout per loop (1-120 min)
CLAUDE_OUTPUT_FORMAT="json"          # Output format
ALLOWED_TOOLS="Write,Read,Edit,Glob,Grep,WebSearch,WebFetch,Bash"
SESSION_CONTINUITY=true              # Resume sessions across loops
SESSION_EXPIRY_HOURS=24              # Session TTL
# Circuit breaker thresholds
CB_NO_PROGRESS_THRESHOLD=3           # Consecutive loops with no progress
CB_SAME_ERROR_THRESHOLD=5            # Consecutive loops with same error
```

## Common Operations

### Starting a New Mission
1. Update `PROMPT.md` with the new mission context and principles
2. Replace `fix_plan.md` with new task checklist
3. Update `AGENT.md` if build/test commands changed
4. Add specs to `specs/` if complex features need detailed requirements
5. Reset state files if reusing from a previous mission:
   - Set `progress.json` to `{"status":"pending","timestamp":"<now>"}`
   - Clear `.ralph_session` contents
   - Reset `.circuit_breaker_state` to CLOSED with zero counters

### Adding Tasks to an Existing Mission
- Add new `- [ ]` items to `fix_plan.md` in the appropriate tier
- Or add to the `## Discovered` section at the bottom

### Changing Loop Behavior
- Edit `PROMPT.md` → "Key Principles" and "Loop Flow" sections
- Example: Change from "ONE TASK PER LOOP" to "UP TO THREE TASKS PER LOOP"

### Fixing a Stuck Loop
- Check `.circuit_breaker_state` — if OPEN, reset to CLOSED
- Check `progress.json` — reset status to "pending"
- Check `.ralph_session` — clear if stale
- Review `logs/` for error details

## Important Notes

- **Always read the current file before editing** — Ralph may have modified it during a loop
- **PROMPT.md is the most impactful file** — small changes here affect every loop iteration
- **fix_plan.md evolves** — Ralph adds discovered tasks and marks completions
- **Don't over-specify** — give Ralph enough context to work autonomously, but trust it to make reasonable implementation decisions
- **Task specificity matters** — vague tasks cause Ralph to loop without progress; specific tasks complete in one iteration

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Ralph loops without progress | Vague tasks in fix_plan.md | Make tasks more specific and actionable |
| Circuit breaker tripped (OPEN) | Repeated errors or no progress | Reset `.circuit_breaker_state` to CLOSED, fix root cause in PROMPT.md or fix_plan.md |
| "API limit exceeded" | Hit hourly rate limit | Wait for reset (shown in status.json `next_reset`), or lower `MAX_CALLS_PER_HOUR` |
| "Session expired" | Exceeded `SESSION_EXPIRY_HOURS` | Use `ralph --resume <id>` or increase expiry in .ralphrc |
| Ralph exits too early | Exit detection false positive | Ensure PROMPT.md instructs `EXIT_SIGNAL: false` until all tasks done |
| Ralph won't exit when done | Missing exit signal | Ensure PROMPT.md includes status reporting block with `EXIT_SIGNAL: true` when all tasks are `[x]` |

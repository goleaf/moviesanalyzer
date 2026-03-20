# pre-deploy-checklist

Intelligent pre-deployment QA checklist generator for any project.

## What it does

1. Explores the codebase structure (excluding build artifacts)
2. Confirms project understanding with user before proceeding
3. Spawns 2-5 parallel domain subagents (frontend, backend, database, security, etc.)
4. Collects domain reports and generates a complete, measurable checklist in `docs/pre-deploy-checklist.md`

## Trigger phrases

- "pre-deploy check"
- "generate QA checklist"
- "ready to deploy?"
- "deploy checklist"

## Output

`docs/pre-deploy-checklist.md` — a human-executable checklist with 🔴/🟡/🟢 priority levels and exact test steps.

## References

- `references/domain-prompts.md` — Subagent prompts for each domain
- `references/checklist-categories.md` — Checklist item templates by domain

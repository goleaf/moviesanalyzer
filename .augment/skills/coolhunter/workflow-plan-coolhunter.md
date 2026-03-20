---
status: COMPLETE
completionDate: 2025-12-31
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9]
---

# Workflow Creation Plan: coolhunter

## Initial Project Context

- **Module:** stand-alone
- **Target Location:** /home/v/project/AI-Agile-Creative-Studio/creative-studio/workflow/creative-studio/workflows/coolhunter
- **Created:** 2025-12-31

## Requirements

### Purpose and Scope

- **Primary user:** V
- **Primary deliverable:** Always a Markdown “trend report”
- **Source material:** Transform from `creative-studio/skill/coolhunter/SKILL.md`

### Workflow Type

- **Type:** Mixed workflow (interactive + action/web research + document output)

### Flow / Intents

- **Keep existing intents/modes as-is:**
  - `coolhunt [topic]` = full research workflow
  - `news scan [topic]` = quick news gathering (simplified route)
  - `trend analysis` / `analyze this trend` = analysis-only route

### Input Requirements

- **Required input:** Topic (for `coolhunt` / `news scan`)
- **Optional input:** User-provided links/snippets (offline-friendly mode)
- **Browsing:** Supported and expected “most of the time”

### Output Specifications

- **Output format:** Markdown report (headline, summary, fact-check, behavioral analysis)
- **Auto-save:** Yes
- **Save path convention:** `coolhunter-output/report-{datetime}/...` (match existing skill behavior)

### Success Criteria

- **Sources**
  - Minimum: 2
  - Target: 3–5
  - Excellent: 5+ diverse
- **Signals extracted**
  - Minimum: 2
  - Target: 3–4
  - Excellent: 5+ distinct
- **Recency**
  - Minimum: <7 days
  - Target: <48 hours
  - Excellent: <24 hours
- **Fact-check claims**
  - Minimum: 2
  - Target: 3–5
  - Excellent: All major claims
- **Behavioral implications**
  - Minimum: 1 category
  - Target: 2 categories
  - Excellent: All 3 categories

## Tools Configuration

### Core BMAD Tools

- **Party-Mode**: included - Integration points: optional multi-persona debate/synthesis for (a) ambiguous signals, (b) controversial topics, (c) conflicting sources, (d) headline + “Coolhunter’s Take” framing
- **Advanced Elicitation**: included - Integration points: quality-gate pass before finalizing the report (method selection check + claim verification rigor + confidence calibration), plus on-demand deep dives
- **Brainstorming**: included - Integration points: widening search angles/subtopics, generating hypotheses, and generating “Watch For / Timeline / Opportunity Window” variants from signals

### LLM Features

- **Web-Browsing**: included - Use cases: default for `coolhunt` and `news scan` with recency targeting (<48h, ideally <24h); fallback supports user-provided links/snippets
- **File I/O**: included - Operations: create output directory + slugified filename; save report to `coolhunter-output/report-{YYYY-MM-DD-HHmm}/{title-slug}.md`
- **Sub-Agents**: included - Use cases: parallelize (a) source discovery, (b) per-source signal extraction, (c) claim verification / cross-source consistency checks
- **Sub-Processes**: included - Use cases: parallel research/extraction pipelines when browsing returns many candidates

### Memory Systems

- **Sidecar File**: included - Purpose: remember preferences across runs (e.g., time window defaults, source preferences/blocks, quality thresholds, recurring topics/angles)

### External Integrations

- **No-install integrations included** (excluded install-required items such as git-integration, vector-database, and other install-required MCPs)

### Elicitation Method Catalog (Workflow-Local Reference)

- **Primary curated catalog:** `creative-studio/skill/coolhunter/assets/elicitation-methods.csv` (50 methods, categories, patterns)
- **Extended descriptions:** `creative-studio/skill/coolhunter/knowledge/elicitation-methods.md`
- **Execution reference:** `creative-studio/skill/coolhunter/references/workflow-execution.md` (context scoring + selection matrix + report/fact-check rules)

### Installation Requirements

- **Tools requiring installation**: none (by selection)
- **User Installation Preference**: avoid install-required integrations

## Plan Review and Approval

- **Plan reviewed:** 2025-12-31
- **User approval:** Approved (continue to design)
- **Notes:** No changes requested during review

## Output Format Design

**Format Type**: Structured

**Output Requirements**:

- Document type: Trend report
- File format: Markdown (`.md`)
- Frequency: Per run (one report per run)
- Save path: `coolhunter-output/report-{YYYY-MM-DD-HHmm}/{title-slug}.md`

**Structure Specifications** (required sections, flexible content within each):

- `# [COOLHUNTER HEADLINE]` (original interpretation, not copied)
- Generated metadata block:
  - `Generated: {datetime}`
  - `Topic: {original_request}`
  - `Analysis Method: {selected_method}`
- `## 📰 News Summary` (cover what happened, why it matters, who’s involved; can include multiple sources)
- `## ✅ Fact-Check` (claims table with ✅/⚠️/❌, plus confidence level + notes)
- `## 🔮 Behavioral & Cultural Analysis`
  - `### Consumer Behavior Shifts`
  - `### Cultural Signals`
  - `### Technology Adoption Curve`
  - `### Pre-Mainstream Indicators`
- `## 🎯 Coolhunter's Take` (forward-looking synthesis + Watch For / Timeline / Opportunity Window)
- `## 📚 Sources` (full citations + URLs)

**Special Considerations**:

- Follow success criteria quality gates (sources/signals/recency/fact-check/behavioral categories).
- When recency <48h is not possible, expand search window up to <7 days and explicitly note the limitation.

## Workflow Structure Design

### Continuation Support

- **Multi-session support:** Yes
- **Design decision:** Include `step-01b-continue.md` to resume from prior runs using output frontmatter (`stepsCompleted`) + sidecar preferences.

### Step Outline (Blueprint)

1. **step-01-init** — Initialize session, detect whether resuming, set run context (topic/mode), explain outputs + save path.
2. **step-01b-continue** (conditional) — Load prior state/output, confirm next step, resume execution.
3. **step-02-route-intent** — Determine intent: `coolhunt` vs `news scan` vs `trend analysis`; confirm topic/inputs (links/snippets optional) and time-window target.
4. **step-03-research-and-signal-extract** — Web browse (or offline sources) + evaluate sources + extract per-source signals (support sub-agents/sub-processes).
5. **step-04-elicitation-method-select-and-apply** — Context scoring → select method(s) from curated catalog → apply pattern(s) → generate insights + implications.
6. **step-05-report-and-save** — Assemble structured Markdown report, run quality gates (success criteria), slugify headline, save to `coolhunter-output/report-{YYYY-MM-DD-HHmm}/{title-slug}.md`.

### Interaction Patterns (Menus / Decision Points)

- **Route selection:** Confirm intent + topic, accept optional links/snippets, confirm recency target.
- **Research controls:** Option to widen search angles/subtopics, accept fewer sources when coverage is limited, or expand recency window up to <7 days with explicit note.
- **Analysis controls:** Option to accept suggested elicitation method, pick from categories, or invoke party-mode for ambiguous/contested signals.
- **Pre-save review:** Option to re-run fact-checking, add sources, adjust headline/slug, or proceed to save.

### Data Flow / State Tracking

- **Tracked in output report frontmatter:** `stepsCompleted`, `topic`, `intent`, `selected_method(s)`, `sources`, `signals`, `claims`, `confidence`, `datetime`, `slug`.
- **Sidecar preferences (cross-run):** recency defaults, source preferences/blocks, and quality thresholds.

### File Structure (Workflow Folder)

Target folder: `creative-studio/workflow/creative-studio/workflows/coolhunter/`

- `workflow.md`
- `steps/step-01-init.md`
- `steps/step-01b-continue.md`
- `steps/step-02-route-intent.md`
- `steps/step-03-research-and-signal-extract.md`
- `steps/step-04-elicitation-method-select-and-apply.md`
- `steps/step-05-report-and-save.md`
- `assets/elicitation-methods.csv` (copy from `creative-studio/skill/coolhunter/assets/elicitation-methods.csv` for workflow self-containment)

## Build Summary

### Generated Files

- `creative-studio/workflow/creative-studio/workflows/coolhunter/workflow.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/templates/report-template.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-01-init.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-01b-continue.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-02-route-intent.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-03-research-and-signal-extract.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-04-elicitation-method-select-and-apply.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/steps/step-05-report-and-save.md`
- `creative-studio/workflow/creative-studio/workflows/coolhunter/assets/elicitation-methods.csv` (copied from `creative-studio/skill/coolhunter/assets/elicitation-methods.csv`)

### Customizations / Notes

- Workflow is stand-alone (non-pipeline) and writes reports to `{project-root}/coolhunter-output/report-{YYYY-MM-DD-HHmm}/{title-slug}.md`.
- Continuation support implemented via `step-01-init.md` detection + `step-01b-continue.md` resume flow.
- Curated elicitation catalog is workflow-local at `assets/elicitation-methods.csv`.

### Next Steps (Validation)

- Run a dry execution by starting at `creative-studio/workflow/creative-studio/workflows/coolhunter/workflow.md` and following `step-01-init.md`.

## Review Summary

### Validation Results

- Configuration validation: PASSED
- Step compliance: PASSED
- Cross-file consistency: PASSED
- Requirements verification: PASSED

### Completeness Check

- All required files present (workflow, steps, template, assets).
- YAML frontmatter parses cleanly across all generated `.md` files.
- Step sequencing is consistent (`nextStepFile` references resolve).
- Step file sizes are within best-practice range (≈3–4KB each).

### Issues Found

- **Critical Issues:** none
- **Warnings:** none
- **Suggestions:** Optional: add a `README.md` in the workflow folder documenting invocation examples (`coolhunt [topic]`, `news scan [topic]`, `trend analysis`) and the output directory convention.

### Usage Guidance

- Entry point: `creative-studio/workflow/creative-studio/workflows/coolhunter/workflow.md`
- Reports saved under: `{project-root}/coolhunter-output/report-{YYYY-MM-DD-HHmm}/{title-slug}.md`

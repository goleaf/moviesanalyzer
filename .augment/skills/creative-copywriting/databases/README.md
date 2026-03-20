# Creative Copywriting Databases (CSV)

These CSV files are a project-local knowledge base used by the `creative-copywriting` workflow to select hooks, triggers, and structures without relying on web browsing.

## Usage Principles

- Use these CSVs as **lookup tables** (pick a small number of relevant rows) rather than dumping large lists into the conversation.
- When a row meaningfully influences an output choice (hook/trigger/structure), prefer citing its `num` and `*_name`/`hook_type` in the workflow output for traceability.
- Keep the workflow **intent-based**: the CSVs guide selection; they should not force rigid scripts.

## Files

### `hook-formulas.csv`

- **Purpose:** Hook patterns by platform with example formulas and expected engagement (stop rate).
- **Primary columns:** `num`, `hook_type`, `platform`, `formula`, `example`, `emotion_trigger`, `psychology_principle`, `stop_rate`, `best_for`, `notes`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`

### `psychological-triggers.csv`

- **Purpose:** Trigger registry with ethical-use notes and manipulation warnings.
- **Primary columns:** `num`, `trigger_name`, `psychology_principle`, `description`, `application`, `example_copy`, `power_level`, `ethical_use`, `manipulation_warning`
- **Used in:** `step-03-strategy.md`, `step-06-qa.md` (as a checklist/reference)

### `power-words.csv`

- **Purpose:** Curated “power word” index for emotional framing, with contexts to use/avoid.
- **Primary columns:** `num`, `word`, `category`, `emotion_trigger`, `intensity`, `best_context`, `avoid_context`, `example_usage`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`, `step-05-draft.md`
- **Note:** This file is the largest CSV in this set; consider splitting by category if it grows.

### `swipe-triggers.csv`

- **Purpose:** Swipe/click continuation phrases and placement guidance for carousels/threads.
- **Primary columns:** `num`, `trigger_type`, `trigger_text`, `psychology`, `placement`, `effectiveness`, `example`, `notes`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`

### `read-more-patterns.csv`

- **Purpose:** Read-more truncation patterns for captions/threads to increase expansion.
- **Primary columns:** `num`, `pattern_name`, `formula`, `truncation_strategy`, `example`, `expansion_rate`, `best_for`, `psychology`, `notes`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`

### `carousel-structures.csv`

- **Purpose:** Carousel structure templates (slide-by-slide) mapped to outcomes and CTA types.
- **Primary columns:** `num`, `structure_name`, `type`, `slide_count`, `structure_template`, `example_topic`, `swipe_rate`, `best_for`, `hook_style`, `cta_type`, `psychology_principle`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`

### `emotional-arcs.csv`

- **Purpose:** Story/emotion arcs for longer-form threads and narrative carousels.
- **Primary columns:** `num`, `arc_name`, `structure`, `emotions_sequence`, `duration`, `use_case`, `example_topic`, `engagement_level`, `resolution_type`
- **Used in:** `step-03-strategy.md`, `step-04-variations.md`

## Maintenance

- When adding new rows, keep columns consistent and avoid duplicate entries.
- Prefer concrete, specific entries over generic statements.
- If you add external sources or statistics, document sources in the corresponding workflow references (Markdown) and keep the CSV as an index/registry.

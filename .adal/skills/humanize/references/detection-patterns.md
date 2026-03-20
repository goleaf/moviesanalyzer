# AI Signature Detection Patterns

Before transforming a document, confirm it's actually AI-generated. This checklist helps you spot the tell-tale patterns.

## Table of Contents

- [Quick Detection Test](#quick-detection-test-30-seconds)
- [Deep Pattern Analysis](#deep-pattern-analysis)
  - [Pattern 1: Structural Symmetry](#pattern-1-structural-symmetry)
  - [Pattern 2: Sentence Length Uniformity](#pattern-2-sentence-length-uniformity)
  - [Pattern 3: Checkbox Addiction](#pattern-3-checkbox-addiction)
  - [Pattern 4: Template Embedding Mania](#pattern-4-template-embedding-mania)
  - [Pattern 5: AI Trigger Phrases](#pattern-5-ai-trigger-phrases)
  - [Pattern 6: Emotional Flatness](#pattern-6-emotional-flatness)
  - [Pattern 7: The Perfect Introduction](#pattern-7-the-perfect-introduction)
  - [Pattern 8: Conclusion Obsession](#pattern-8-conclusion-obsession)
- [Edge Cases & False Positives](#edge-cases--false-positives)
- [Detection Workflow](#detection-workflow)
- [Real-World Examples](#real-world-examples)
- [Summary Checklist](#summary-checklist)

---

## Quick Detection Test (30 seconds)

Count how many of these the document has:

1. ☐ Checkbox spam (5+ checkboxes in single section)
2. ☐ CAPS LOCK emphasis for warnings
3. ☐ Template embedding (full code blocks >50 lines)
4. ☐ Perfect paragraph symmetry (all sections same length)
5. ☐ Zero sentence variation (all 15-20 words)
6. ☐ AI trigger phrases (see list below)
7. ☐ Emotional flatness (no personality)
8. ☐ Structure too perfect (intro/body/conclusion every time)

**Scoring:**
- 5+ boxes checked → **Definitely AI-generated**
- 3-4 boxes → **Probably AI-generated, proceed with caution**
- 1-2 boxes → **Might be human, analyze deeper**
- 0 boxes → **Likely human-written, don't transform**

## Deep Pattern Analysis

### Pattern 1: Structural Symmetry

**What to look for:**

Every section has the same structure - usually 3 paragraphs, each 4-5 sentences, each sentence 15-20 words.

**Example (AI-generated):**
```markdown
## Feature A
Paragraph 1: 5 sentences, 80 words
Paragraph 2: 5 sentences, 85 words  
Paragraph 3: 4 sentences, 75 words

## Feature B  
Paragraph 1: 5 sentences, 82 words
Paragraph 2: 5 sentences, 83 words
Paragraph 3: 4 sentences, 77 words
```

**Human writing:** Wildly uneven. One section might be 1 paragraph, next is 5 paragraphs.

**Detection method:**
Count paragraphs per section. If 80%+ of sections have same paragraph count, it's AI.

### Pattern 2: Sentence Length Uniformity

**What to look for:**

AI aims for ~17 words per sentence. Humans swing from 3 to 40.

**AI example:**
```
The system validates user input before processing (7 words).
It checks for required fields and data types (8 words).
Then it proceeds to the next validation step (8 words).
All errors are logged for debugging purposes (7 words).
```

**Human example:**
```
The system validates input. Obviously.

But here's the thing - it doesn't just check if required fields exist, it actually runs
a pretty sophisticated validation pipeline that catches edge cases most developers wouldn't 
even think about until they hit production and everything explodes.

All errors get logged. Thank god.
```

**Detection method:**
Calculate standard deviation of sentence word count. If <3, probably AI.

### Pattern 3: Checkbox Addiction

**What to look for:**

AI LOVES checklists. It will turn everything into checkboxes.

**Common checkbox patterns (AI signatures):**

```markdown
- [x] Task 1: Do thing (AC: #1)
  - [x] Subtask 1a
  - [x] Subtask 1b
- [x] Task 2: Do other thing (AC: #2)
  - [x] Subtask 2a
```

Notice:
- Double-nested checkboxes
- Every item has acceptance criteria reference
- Everything is "completed" (all [x] not [ ])
- Format is too consistent

**Human checkbox patterns:**

```markdown
- Install dependencies
- Configure .env file (copy from .env.example)
- Run migrations
- ??? (profit)
```

Notice:
- Mix of [ ] and natural bullets
- Inconsistent formatting
- Jokes ("??? profit")
- Some items more detailed than others

### Pattern 4: Template Embedding Mania

**What to look for:**

AI embeds ENTIRE code files, even when 10 lines would do.

**AI pattern:**
```markdown
Here is the complete implementation:

[200 lines of Python with every possible edge case]

Modify according to your needs.
```

**Human pattern:**
```markdown
Here's the basic setup:

```python
config = {
    'api_key': 'your-key',
    'timeout': 30
}
```

You can add more options (see docs) but honestly this works for 90% of cases.
```

**Detection method:**
Count code blocks >50 lines. If 3+, likely AI.

### Pattern 5: AI Trigger Phrases

These phrases are AI signatures. Humans almost never write like this:

**High-confidence AI indicators:**

| Phrase | Why It's AI | Human Alternative |
|--------|-------------|-------------------|
| "Certainly, here is..." | ChatGPT signature | Just start |
| "It is important to note that..." | Padding | State the fact |
| "Furthermore, additionally, moreover" | AI loves these | Use "also" or "and" |
| "Prior to" | Overly formal | "Before" |
| "In order to" | Redundant | "To" |
| "Utilize" | AI default | "Use" |
| "Leverage" | Corporate AI | "Use" |
| "Facilitate" | More corporate AI | "Help" or "enable" |
| "Subsequently" | Time padding | "Then" or "next" |
| "It should be noted" | Useless filler | Delete it |
| "As previously mentioned" | AI callback | "Remember..." or delete |
| "At this point in time" | Worst offender | "Now" |

**Detection method:**
Search for these phrases. If 5+ appear, it's AI.

### Pattern 6: Emotional Flatness

**What to look for:**

AI maintains perfect neutral tone. No frustration, excitement, or personality.

**AI example:**
```markdown
The configuration process involves several steps. Each step must be completed 
in the specified order. Failure to follow the sequence may result in errors. 
Please ensure all requirements are met before proceeding.
```

**Emotional temperature:** 0/10 (robotic)

**Human example:**
```markdown
Config is annoying but necessary. Do these in order or everything breaks:

1. Set up API keys
2. Configure database (this takes forever)
3. Run the migration script

If you skip step 2, you'll get the world's least helpful error message. 
Ask me how I know.
```

**Emotional temperature:** 6/10 (frustrated but helpful)

**Detection clues:**
- No personal pronouns (I, we, you)
- No asides or parentheticals
- No informal language ("stuff", "thing", "kinda")
- No acknowledgment of pain points
- Zero humor attempts

### Pattern 7: The Perfect Introduction

**AI loves this structure:**
```markdown
## [Topic]

This section explains [X]. It covers [Y] and [Z]. 
By the end, you will understand [outcome].
```

**Human writes:**
```markdown
## [Topic]

Quick version: [X] does [core thing].

Longer version if you care: [actual explanation]
```

Or just starts explaining without meta-commentary about what the section will do.

**Detection:** If every section starts with "This section explains...", it's AI.

### Pattern 8: Conclusion Obsession

**AI needs closure:**
```markdown
In conclusion, this approach provides several benefits including X, Y, and Z. 
By following these steps, you can successfully implement the solution.
```

**Humans:** Often just stop. Or: "Anyway, that's the basics."

**Detection:** If 80%+ of sections have "In conclusion/summary/brief", it's AI.

## Edge Cases & False Positives

### When Humans Look Like AI

**Problem:** Some human writing triggers false positives.

**Common causes:**
1. Non-native English speakers (may use formal constructions)
2. Academic writing (deliberately formal)
3. Corporate style guides (mandate certain phrases)
4. Legal documents (required language)

**How to distinguish:**

Look for micro-variations:
- Do they ever use contractions?
- Is there ANY sentence variety (even slight)?
- Do they make choices that seem personal (word choice, examples)?
- Is there ANY personality, even subtle?

If yes to 2+, probably human.

### When AI Looks Human

**Problem:** Advanced AI (GPT-4, Claude) can mimic human patterns if prompted.

**Detection strategy:**

Look for tell-tale combination:
- Burstiness BUT too consistent (same pattern every paragraph)
- Personality BUT generic (could be anyone)
- Examples BUT too perfect (no rough edges)
- Contractions BUT in predictable places

**Real human writing:** Inconsistent in unpredictable ways. One section formal, next casual, no pattern.

## Detection Workflow

**Step 1: Quick test (30 sec)**
Count the 8 checkbox items at top of doc.
- 5+ → AI, proceed with transformation
- <5 → Continue to Step 2

**Step 2: Structural analysis (2 min)**
- Check paragraph symmetry
- Calculate sentence length variation
- Count checkboxes

**Step 3: Linguistic analysis (2 min)**
- Search for AI trigger phrases
- Assess emotional temperature
- Check for perfect intros/conclusions

**Step 4: Confidence score**
- 8+ AI signals → High confidence, transform aggressively
- 5-7 AI signals → Medium confidence, transform moderately
- 3-4 AI signals → Low confidence, proceed carefully
- <3 AI signals → Don't transform, might be human

## Real-World Examples

### Example 1: Flutter Story Documentation (Your Upload)

**AI signatures detected:**
- ✅ Checkbox overload: `- [x] Task 1: Create Flutter project (AC: #1)`
- ✅ Perfect structure: Every task has subtasks with same format
- ✅ Template embedding: 8 full code templates
- ✅ Meta-commentary: "LLM Developer Guardrails" section
- ✅ Zero emotional content
- ✅ "CRITICAL", "MANDATORY" caps emphasis
- ✅ "DO NOT" lists (robot instructions)

**Confidence:** 100% AI-generated ✅

### Example 2: LevelDB impl.md (Your Upload)

**AI signatures detected:**
- ❌ No checkboxes
- ❌ Natural flow, varying paragraph lengths
- ❌ Informal touches: "So maybe even the sharding is not necessary?"
- ❌ Conversational: "Note that if a level-L file overlaps..."
- ❌ Real data: Actual experiment results table
- ❌ Uncertainty: "we might want to...", "perhaps most of..."

**Confidence:** 0% AI-generated ❌ (Human-written)

### Example 3: Borderline Case

```markdown
## API Configuration

To configure the API, you need to set several environment variables. 
The following variables are required:

- API_KEY: Your authentication key
- BASE_URL: The API endpoint
- TIMEOUT: Request timeout in seconds

After setting these variables, restart the application to apply changes.
```

**Analysis:**
- Structure: Somewhat formal but not excessive
- Checkboxes: Uses bullets, not checkboxes (neutral)
- Language: "you need" (conversational), "several" (human), but "following variables are required" (AI-ish)
- Conclusion: "After setting..." (could be either)

**Verdict:** Unclear. Could be human writing formally, could be AI writing casually.

**Action:** Don't transform yet. Ask for more context or look at rest of document.

---

## Summary Checklist

Use this as your final decision matrix:

| Signal | AI Weight | Human Weight |
|--------|-----------|--------------|
| 5+ checkboxes per section | +3 | 0 |
| Perfect paragraph symmetry | +3 | 0 |
| Sentence length σ < 3 | +3 | 0 |
| 5+ AI trigger phrases | +2 | 0 |
| Zero contractions | +2 | 0 |
| Template dumps >50 lines | +2 | 0 |
| Every section has intro/conclusion | +2 | 0 |
| CAPS LOCK emphasis | +1 | 0 |
| Robot instructions (DO/DON'T lists) | +1 | 0 |
| Emotional flatness | +1 | 0 |
| Sentence variety (σ > 8) | 0 | +3 |
| Asides/parentheticals | 0 | +2 |
| Informal words (stuff, thing, kinda) | 0 | +2 |
| Spontaneous humor | 0 | +2 |
| Contradictions or corrections | 0 | +1 |

**Total score:**
- AI weight 10+ → Transform aggressively
- AI weight 7-9 → Transform moderately
- AI weight 4-6 → Transform gently
- Human weight > AI weight → Don't transform

# Prompt Engineering Decision Matrix

## Comprehensive Decision Framework

This matrix helps you select the optimal combination of prompting technique and output format based on multiple factors.

## Decision Factors

### 1. Task Complexity
- **Low:** Single-step, well-defined tasks
- **Medium:** Multi-step but straightforward
- **High:** Complex reasoning, planning, or exploration required

### 2. LLM Target
- **Claude (Anthropic):** Optimized for XML, strong reasoning
- **GPT (OpenAI):** Optimized for JSON, tool use
- **Gemini (Google):** Flexible, strong multimodal
- **Multi-LLM:** Need portability across platforms

### 3. Accuracy Requirements
- **Normal:** General use, some errors acceptable
- **Important:** Business-critical, minimize errors
- **Critical:** Life/safety/financial, zero tolerance

### 4. Available Context
- **No examples:** Must use zero-shot
- **2-5 examples:** Can use few-shot
- **Many examples:** Rich few-shot or self-consistency

### 5. Output Consumption
- **Human reading:** Natural language, YAML
- **Code parsing:** JSON, XML (structured)
- **Both:** JSON with clear schema

---

## Selection Matrix

| Task Complexity | LLM Target | Accuracy Need | Examples | Recommended Method | Recommended Format |
|----------------|-----------|---------------|----------|-------------------|-------------------|
| Low | Any | Normal | None | Zero-Shot | Natural Language |
| Low | Any | Normal | 2-5 | Few-Shot | Same as examples |
| Low | Claude | Important | None | Zero-Shot | XML |
| Low | GPT/Other | Important | None | Zero-Shot | JSON |
| Medium | Any | Normal | None | Zero-Shot CoT | Natural Language |
| Medium | Any | Normal | 2-5 | Few-Shot | Natural Language/JSON |
| Medium | Claude | Important | None | Chain of Thought | XML |
| Medium | GPT | Important | None | Chain of Thought | JSON |
| Medium | Any | Important | 2-5 | Few-Shot CoT | XML (Claude) / JSON (Other) |
| High | Any | Normal | None | Chain of Thought | Natural Language/XML |
| High | Any | Important | None | Tree of Thoughts | YAML (planning) / XML |
| High | Any | Critical | Any | Self-Consistency | XML (Claude) / JSON (Other) |
| High + Tools | Any | Any | None | ReAct | JSON |
| Planning | Claude | Important | None | Tree of Thoughts | XML / YAML |
| Planning | GPT | Important | None | Tree of Thoughts | JSON / YAML |

---

## Format Selection Detailed

### XML
**Use when:**
- Target is Claude (Anthropic models)
- Complex nested structure needed
- Clear semantic boundaries required
- Multiple contexts need separation

**Avoid when:**
- Output must be parsed by code (use JSON instead)
- Token efficiency is critical (XML is verbose)
- Target is non-Claude LLM

### JSON
**Use when:**
- Output parsed by code/APIs
- Multi-LLM compatibility needed
- Structured data with arrays/objects
- Token efficiency matters

**Avoid when:**
- Humans will frequently edit (use YAML)
- Very complex hierarchies (consider XML for Claude)

### YAML
**Use when:**
- Humans edit/maintain prompts
- Configuration-driven systems
- Readability is paramount
- Multi-line content common

**Avoid when:**
- LLM has limited YAML familiarity
- Output needs programmatic parsing (use JSON)
- Whitespace sensitivity is problem

### Natural Language
**Use when:**
- Task is simple and straightforward
- No structure adds value
- General conversation/explanation
- Brainstorming/creative work

**Avoid when:**
- Output must be parsed
- Structure reduces ambiguity
- Complex multi-part instructions

---

## Method Selection by Use Case

### Use Case: Code Review
- **Method:** Few-Shot CoT (show examples with reasoning)
- **Format:** XML (Claude) or JSON (GPT)
- **Why:** Need consistent format + reasoning transparency

### Use Case: Data Extraction
- **Method:** Few-Shot
- **Format:** JSON (for structured output)
- **Why:** Examples define format, JSON for parsing

### Use Case: Mathematical Problem
- **Method:** Chain of Thought
- **Format:** Natural Language or XML
- **Why:** Need step-by-step reasoning, structure helps

### Use Case: Planning/Strategy
- **Method:** Tree of Thoughts
- **Format:** YAML (human review) or XML (Claude processing)
- **Why:** Explore alternatives, human-readable planning

### Use Case: High-Stakes Decision
- **Method:** Self-Consistency
- **Format:** XML (Claude) or JSON (GPT)
- **Why:** Multiple reasoning paths, structured comparison

### Use Case: Tool/API Orchestration
- **Method:** ReAct
- **Format:** JSON
- **Why:** Tool calling requires structured format

---

## Quick Decision Flowchart

```
START
  ↓
Critical accuracy needed?
  ├─ YES → Self-Consistency
  │        ├─ Claude → XML
  │        └─ Other → JSON
  └─ NO → Continue
       ↓
Need tool/API use?
  ├─ YES → ReAct + JSON
  └─ NO → Continue
       ↓
Complex planning/exploration?
  ├─ YES → Tree of Thoughts
  │        ├─ Human review → YAML
  │        └─ LLM processing → XML (Claude) / JSON (GPT)
  └─ NO → Continue
       ↓
Multi-step reasoning?
  ├─ YES → Chain of Thought
  │        ├─ Examples available → Few-Shot CoT
  │        └─ No examples → Zero-Shot CoT
  │        Format: Natural Language or XML (Claude)
  └─ NO → Continue
       ↓
Examples available?
  ├─ YES → Few-Shot
  │        Format: Same as examples
  └─ NO → Zero-Shot
           Format: Natural Language
```

---

## Token Budget Considerations

| Method | Typical Tokens | When Token Budget is... |
|--------|---------------|------------------------|
| Zero-Shot | 50-200 | ✅ Tight budget |
| Few-Shot (3 ex) | 200-800 | ✅ Medium budget |
| Chain of Thought | 100-500 | ✅ Medium budget |
| ReAct | 300-1000 | ⚠️ Moderate budget needed |
| Tree of Thoughts | 500-2000+ | ❌ Large budget required |
| Self-Consistency | 500-3000+ | ❌ Large budget required |

**Format overhead:**
- Natural Language: Lowest overhead
- JSON: Low overhead (compact)
- YAML: Medium overhead (readable)
- XML: Higher overhead (verbose but semantic)

---

## Example Decision Process

**Scenario:** "I need to extract product features from customer reviews and output structured data for analysis."

**Decision process:**
1. **Task complexity?** Medium (extraction + structure)
2. **LLM target?** Multi-LLM (GPT/Claude both possible)
3. **Accuracy?** Important (business analysis)
4. **Examples?** Yes, can provide 3-5 review examples
5. **Output use?** Code parsing (Python analysis script)

**Selection:**
- **Method:** Few-Shot (examples define extraction pattern)
- **Format:** JSON (code will parse it)
- **Implementation:** 3-5 examples showing input review → JSON output with feature/sentiment/detail

**Result:** Few-Shot + JSON combination optimally balances all requirements.

---

**For implementation details of each method, see individual reference files.**

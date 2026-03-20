# Advanced Techniques: Combining Methods

## Overview

Most real-world tasks benefit from combining multiple prompt engineering techniques. This guide shows proven combinations and when to use them.

## Common Combinations

### 1. Few-Shot + Chain of Thought (Few-Shot CoT)

**When to use:** Need both format consistency AND transparent reasoning

**Structure:**
```
Show examples with reasoning steps included in each example.
```

**Template:**
```
Task: Solve complex math word problems

Example 1 (with reasoning):
Problem: If 3 oranges cost $2, how much do 7 oranges cost?

Reasoning:
Step 1: Find cost per orange = $2 ÷ 3 = $0.67
Step 2: Calculate for 7 oranges = $0.67 × 7 = $4.69
Answer: $4.69

Example 2 (with reasoning):
Problem: A car travels 120 miles in 2 hours. At the same speed, 
how long for 300 miles?

Reasoning:
Step 1: Find speed = 120 miles ÷ 2 hours = 60 mph
Step 2: Calculate time = 300 miles ÷ 60 mph = 5 hours
Answer: 5 hours

Now solve (show your reasoning):
Problem: If 5 apples cost $3, how much do 8 apples cost?
Reasoning:
```

**Benefits:**
- Consistent output format (from Few-Shot)
- Transparent reasoning (from CoT)
- Higher accuracy than either alone

**Token cost:** Medium-High (200-800 tokens)

---

### 2. ReAct + Self-Consistency

**When to use:** High-stakes decisions requiring both tool use AND verification

**Structure:**
```
Use ReAct framework multiple times with different tool sequences,
then compare results for consistency.
```

**Template:**
```
Problem: [Complex problem requiring both tools and high confidence]

Attempt 1 (Tool Sequence A):
Thought 1: [Reasoning]
Action 1: [Tool/API call]
Observation 1: [Result]
...
Final Answer 1: [Result]

Attempt 2 (Tool Sequence B):
Thought 1: [Different approach]
Action 1: [Different tool or order]
Observation 1: [Result]
...
Final Answer 2: [Result]

Attempt 3 (Tool Sequence C):
[Third approach]
Final Answer 3: [Result]

Consistency Check:
Answers: [Answer 1, Answer 2, Answer 3]
Agreement: [X/3 agree]
Final Answer: [Most consistent result]
```

**Benefits:**
- Tool use capability (from ReAct)
- Verified answers (from Self-Consistency)
- Higher reliability for critical tasks

**Token cost:** Very High (1000-3000+ tokens)

---

### 3. Tree of Thoughts + XML (Claude-Specific)

**When to use:** Complex planning for Claude with clear structure needed

**Template:**
```xml
<tree_of_thoughts>
  <problem>[Complex planning problem]</problem>
  
  <exploration_config>
    <max_depth>3</max_depth>
    <branches_per_level>3</branches_per_level>
    <evaluation_criteria>
      <criterion weight="0.4">Feasibility</criterion>
      <criterion weight="0.3">Cost</criterion>
      <criterion weight="0.3">Time</criterion>
    </evaluation_criteria>
  </exploration_config>
  
  <level_1>
    <thought id="A">
      <description>[Approach A]</description>
      <evaluation>
        <feasibility>0.9</feasibility>
        <cost>0.6</cost>
        <time>0.7</time>
        <total_score>0.75</total_score>
      </evaluation>
    </thought>
    <!-- More thoughts -->
    <selected>A</selected>
  </level_1>
  
  <!-- Continue levels -->
</tree_of_thoughts>
```

**Benefits:**
- Systematic exploration (from ToT)
- Clear structure (from XML)
- Claude-optimized format

**Token cost:** High (800-2000 tokens)

---

### 4. Few-Shot + JSON (API Integration)

**When to use:** Need format consistency + machine parsing

**Template:**
```json
{
  "task": "Extract structured data from text",
  "examples": [
    {
      "input": "John Doe, 30 years old, works at Google",
      "output": {
        "name": "John Doe",
        "age": 30,
        "company": "Google"
      }
    },
    {
      "input": "Jane Smith, age 25, employed by Microsoft",
      "output": {
        "name": "Jane Smith",
        "age": 25,
        "company": "Microsoft"
      }
    }
  ],
  "new_input": "[Text to process]",
  "output_schema": {
    "type": "object",
    "properties": {
      "name": {"type": "string"},
      "age": {"type": "integer"},
      "company": {"type": "string"}
    },
    "required": ["name", "age", "company"]
  }
}
```

**Benefits:**
- Format learning (from Few-Shot)
- Machine-parseable (from JSON)
- Schema validation possible

**Token cost:** Medium (200-600 tokens)

---

### 5. Zero-Shot CoT + XML (Claude Simple Reasoning)

**When to use:** Simple reasoning tasks for Claude without examples

**Template:**
```xml
<reasoning_task>
  <problem>
    [State problem clearly]
  </problem>
  
  <instruction>
    Let's solve this step by step.
  </instruction>
  
  <reasoning_steps>
    <!-- LLM fills this in -->
  </reasoning_steps>
  
  <answer>
    <!-- LLM provides final answer -->
  </answer>
</reasoning_task>
```

**Benefits:**
- Step-by-step reasoning (from CoT)
- Clean structure (from XML)
- No examples needed (from Zero-Shot)

**Token cost:** Low-Medium (100-300 tokens)

---

## Decision Matrix: Which Combination?

| Need | Combination | Token Cost |
|------|-------------|------------|
| Format + Reasoning | Few-Shot CoT | Medium-High |
| Tools + Verification | ReAct + Self-Consistency | Very High |
| Planning + Structure (Claude) | ToT + XML | High |
| Format + Parsing | Few-Shot + JSON | Medium |
| Simple Reasoning (Claude) | Zero-Shot CoT + XML | Low-Medium |

---

## Layering Techniques

### Layer 1: Base Method
Choose primary technique:
- Zero-Shot / Few-Shot / CoT / ReAct / ToT / Self-Consistency

### Layer 2: Format
Choose output format:
- Natural Language / JSON / XML / YAML

### Layer 3: Enhancements (Optional)
Add refinements:
- Examples (if not Few-Shot)
- Verification steps
- Edge case handling
- Output schema

**Example Layering:**
```
Base: ReAct (tool use needed)
Format: JSON (API integration)
Enhancement: Add schema validation

Result: ReAct + JSON + Schema
```

---

## Anti-Patterns to Avoid

### ❌ Don't Mix Contradictory Techniques

**Bad:**
```
Use Zero-Shot (no examples)
Here are 5 examples... (Few-Shot)
But ignore the examples and reason from scratch (Contradiction)
```

### ❌ Don't Over-Engineer Simple Tasks

**Bad:**
```
Use Tree of Thoughts + Self-Consistency + Few-Shot CoT 
to answer: "What is 2 + 2?"

Token cost: 2000+
Correct approach: Zero-Shot, 10 tokens
```

### ❌ Don't Combine Incompatible Formats

**Bad:**
```xml
<!-- XML structure -->
<prompt>
  {
    "json": "inside xml"  <!-- Confusing mix -->
  }
</prompt>
```

**Good:**
```xml
<prompt>
  <data_format>JSON</data_format>
  <expected_output>
    <!-- Describe JSON, don't embed it -->
  </expected_output>
</prompt>
```

---

## Progressive Enhancement

Start simple, add complexity only if needed:

### Level 1: Start Simple
```
Zero-Shot with Natural Language
```

### Level 2: Add Examples (if output quality poor)
```
Few-Shot with Natural Language
```

### Level 3: Add Reasoning (if logic unclear)
```
Few-Shot CoT with Natural Language
```

### Level 4: Add Structure (if Claude or complex)
```
Few-Shot CoT with XML
```

### Level 5: Add Verification (if high-stakes)
```
Few-Shot CoT + Self-Consistency with XML
```

**Rule:** Only progress to next level if previous level insufficient.

---

## Real-World Combination Examples

### Example 1: Medical Diagnosis Support

**Combination:** Few-Shot CoT + Self-Consistency + XML

```xml
<medical_diagnosis>
  <examples>
    <example>
      <symptoms>[Symptom list]</symptoms>
      <reasoning>
        <step>Step 1: Consider symptom cluster</step>
        <step>Step 2: Differential diagnosis</step>
        <step>Step 3: Most likely condition</step>
      </reasoning>
      <diagnosis>[Diagnosis]</diagnosis>
    </example>
  </examples>
  
  <new_case>
    <symptoms>[Patient symptoms]</symptoms>
  </new_case>
  
  <instruction>
    Provide 3 independent diagnostic approaches,
    then select most consistent diagnosis.
  </instruction>
</medical_diagnosis>
```

**Why this combination:**
- Few-Shot CoT: Show diagnostic reasoning pattern
- Self-Consistency: Critical decision needs verification
- XML: Claude-optimized, clear structure

---

### Example 2: Code Generation with Validation

**Combination:** Few-Shot + ReAct + JSON

```json
{
  "task": "Generate and validate Python function",
  "examples": [
    {
      "request": "Function to calculate factorial",
      "code": "def factorial(n): ...",
      "tests": ["assert factorial(5) == 120"]
    }
  ],
  "new_request": "[User request]",
  "workflow": {
    "step_1": {
      "action": "GENERATE_CODE",
      "thought": "Based on examples, create function"
    },
    "step_2": {
      "action": "VALIDATE_SYNTAX",
      "thought": "Check for syntax errors"
    },
    "step_3": {
      "action": "RUN_TESTS",
      "thought": "Execute test cases"
    },
    "step_4": {
      "action": "FINISH",
      "output": "Validated code"
    }
  }
}
```

**Why this combination:**
- Few-Shot: Show code style and pattern
- ReAct: Multi-step with validation actions
- JSON: Machine-parseable output

---

## Quick Reference

| Scenario | Best Combination | Cost |
|----------|-----------------|------|
| Math word problems | Few-Shot CoT + Natural Language | Medium |
| Medical diagnosis | Few-Shot CoT + Self-Consistency + XML | Very High |
| API data extraction | Few-Shot + JSON | Medium |
| Strategic planning (Claude) | ToT + XML | High |
| Tool use + verification | ReAct + Self-Consistency | Very High |
| Simple Claude reasoning | Zero-Shot CoT + XML | Low |

---

**Related:**
- Individual technique guides for details
- [Decision Matrix](decision_matrix.md) - Choosing techniques
- [Pitfalls](pitfalls.md) - Avoid anti-patterns

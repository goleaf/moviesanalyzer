# Coolhunter Workflow Execution Reference

## Table of Contents
- [Complete Execution Flow](#complete-execution-flow)
  - [STEP 1: Receive Request](#step-1-receive-request)
  - [STEP 2: Web Research Execution](#step-2-web-research-execution)
  - [STEP 3: Elicitation Method Selection](#step-3-elicitation-method-selection)
  - [STEP 4: Report Generation](#step-4-report-generation)
  - [STEP 5: File Output Execution](#step-5-file-output-execution)
- [Quality Assurance Checklist](#quality-assurance-checklist)
- [Error Handling](#error-handling)

---

## Complete Execution Flow

### STEP 1: Receive Request

**Input Processing:**
```
USER: "Coolhunt [topic]" / "What's trending in [topic]"

COOLHUNTER responds:
"🔍 Coolhunting: [topic]
Scanning latest developments in the last 48 hours..."
```

**Topic Normalization:**
- Extract core topic keywords
- Identify subtopics for deeper search
- Note any specific angle requested

---

### STEP 2: Web Research Execution

**Search Sequence:**
```bash
# Primary search (most recent)
web_search: "[topic] news today"

# Context search
web_search: "[topic] latest developments 2025"

# Trend search  
web_search: "[topic] emerging trend"

# Optional: subtopic deep dive
web_search: "[topic] [specific aspect] news"
```

**Source Evaluation Checklist:**
- [ ] Is source reputable? (major publication, verified outlet)
- [ ] Is content within 48 hours? (prefer <24h)
- [ ] Is it original reporting or aggregation?
- [ ] Are claims attributed to named sources?

**Signal Extraction Template:**
```markdown
### Source: [Publication Name]
**URL:** [link]
**Published:** [date/time]

**Primary Signal:**
[What's the core change/event?]

**Key Players:**
[Who is involved/affected?]

**Timeline:**
[When did this emerge? Speed of development?]

**Quote/Evidence:**
[Key supporting quote or data point]
```

---

### STEP 3: Elicitation Method Selection

**Context Scoring:**
```
Rate 1-5 for each dimension:

Complexity:     [1=simple, 5=multi-faceted]
Stakeholders:   [1=single group, 5=many groups]
Risk Level:     [1=low impact, 5=high stakes]
Novelty:        [1=incremental, 5=paradigm shift]
Controversy:    [1=consensus, 5=polarizing]
```

**Method Selection Matrix:**

| Score Profile | Recommended Methods |
|---------------|---------------------|
| High complexity + high stakeholders | Stakeholder Round Table, Graph of Thoughts |
| High risk + any novelty | Pre-mortem, Red Team vs Blue Team |
| High novelty + low controversy | First Principles, SCAMPER, What If |
| High controversy + any | Debate Club Showdown, Self-Consistency |
| Low complexity + consumer focus | User Persona Focus Group, 5 Whys |

**Method Application Template:**
```markdown
## Analysis: [Method Name]

### Why This Method
[1-2 sentences on why selected for this news]

### Application

[Execute the method's pattern on the news content]

### Insights Derived
1. [Key insight]
2. [Key insight]  
3. [Key insight]

### Behavioral Implications
- **Consumer:** [How behavior may shift]
- **Cultural:** [Broader meaning]
- **Tech:** [Adoption implications]
```

---

### STEP 4: Report Generation

**Headline Creation Guidelines:**
- NOT a copy of source headline
- Coolhunter's interpretation of the signal
- Focus on implication, not event
- Format: [Action] + [Subject] + [Implication]

**Examples:**
- Source: "Apple announces new AI features"
- Coolhunter: "Apple's AI Pivot Signals End of Privacy-First Era"

- Source: "Bitcoin hits $100k"
- Coolhunter: "Six-Figure Bitcoin Normalizes Crypto as Asset Class"

**Fact-Check Protocol:**
```markdown
| Claim | Status | Verification |
|-------|--------|--------------|
| [Specific claim from news] | ✅/⚠️/❌ | [How verified + source] |
```

**Status Guide:**
- ✅ Verified: Multiple reputable sources confirm
- ⚠️ Partial: Some evidence, needs caveats
- ❌ Unverified: Cannot confirm, single source only

**Confidence Levels:**
- **High:** 3+ independent sources, official confirmation
- **Medium:** 2 sources, plausible but limited confirmation
- **Low:** Single source, speculation, unnamed sources

---

### STEP 5: File Output Execution

**Directory Creation:**
```bash
DATETIME=$(date +%Y-%m-%d-%H%M)
mkdir -p coolhunter-output/report-$DATETIME
```

**Slug Generation Rules:**
1. Take Coolhunter headline
2. Lowercase all characters
3. Replace spaces with hyphens
4. Remove punctuation except hyphens
5. Truncate to 50 characters max
6. Remove trailing hyphens

**Example:**
```
Headline: "Apple's AI Pivot Signals End of Privacy-First Era"
Slug: "apples-ai-pivot-signals-end-of-privacy-first-era"
```

**Final Save:**
```bash
cat > "coolhunter-output/report-$DATETIME/$SLUG.md" << 'EOF'
[Full report content]
EOF
```

---

## Quality Assurance Checklist

Before presenting output:

### Content Quality
- [ ] Headline is original interpretation, not copy
- [ ] Summary explains what + why + who
- [ ] At least 2 sources cited with URLs
- [ ] Fact-check table has verified claims
- [ ] Behavioral analysis covers consumer + cultural + tech

### Analysis Quality  
- [ ] Selected method matches news context
- [ ] Method properly applied (followed pattern)
- [ ] Insights are actionable, not generic
- [ ] Pre-mainstream indicators identified

### Output Quality
- [ ] File saved to correct path
- [ ] Slug follows naming convention
- [ ] Markdown renders correctly
- [ ] All links are valid

---

## Error Handling

**No recent news found:**
```
"🔍 Limited recent coverage for [topic].
Expanding search to past 7 days...
[Continue with broader search]"
```

**Conflicting sources:**
```
"⚠️ Sources report conflicting information:
- [Source A] says: [claim]
- [Source B] says: [claim]
Analysis will note this uncertainty."
```

**Single source only:**
```
"⚠️ Only one source found. 
Confidence level: Low
Recommend monitoring for confirmation."
```

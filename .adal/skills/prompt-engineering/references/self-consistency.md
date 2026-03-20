# Self-Consistency Prompting

## Overview

Self-Consistency generates multiple reasoning paths independently, then selects the most consistent answer. Improves accuracy for high-stakes decisions by comparing multiple solutions.

## When to Use

✅ **Use Self-Consistency When:**
- High-stakes decisions requiring verification
- Accuracy is more important than speed
- Multiple reasoning paths are possible
- Need confidence in the answer
- Problem has objective correct answer
- Cost of error is high

❌ **Don't Use Self-Consistency When:**
- Simple tasks with obvious answers
- Speed is critical (Self-Consistency is slow)
- Token budget is tight (very expensive)
- Subjective questions (no "correct" answer)
- Creative tasks (variety is good, not consensus)

## Method

1. **Generate** 3-10 independent reasoning paths
2. **Compare** final answers from all paths
3. **Select** most consistent/common answer
4. **Verify** by investigating discrepancies

## Structure

```
Problem → 
  Path 1 (Method A) → Answer 1
  Path 2 (Method B) → Answer 2  
  Path 3 (Method C) → Answer 3
  Path 4 (Method D) → Answer 4
  Path 5 (Method E) → Answer 5
→ Consistency Check → Final Answer
```

## Template

```
Problem: [Critical problem requiring high confidence]

I will solve this using 5 different reasoning approaches to ensure consistency.

Approach 1 (Method: [e.g., Direct calculation]):
[Reasoning steps]
Answer 1: [Result]

Approach 2 (Method: [e.g., Work backwards]):
[Reasoning steps]
Answer 2: [Result]

Approach 3 (Method: [e.g., Analogical reasoning]):
[Reasoning steps]
Answer 3: [Result]

Approach 4 (Method: [e.g., Formula-based]):
[Reasoning steps]
Answer 4: [Result]

Approach 5 (Method: [e.g., Verification approach]):
[Reasoning steps]
Answer 5: [Result]

Consistency Check:
- All answers: [List: Answer 1, Answer 2, Answer 3, Answer 4, Answer 5]
- Most common answer: [X appears Y times]
- Confidence: [High/Medium/Low based on agreement]
- Discrepancies: [Note any different answers]
- Investigation: [If answers differ, why?]

Final Answer: [Most consistent answer with reasoning]
Confidence: [Percentage based on agreement]
```

## Best Practices

✓ Use 5-7 diverse reasoning paths (optimal)
✓ Vary approaches/starting points genuinely
✓ Compare answers, don't just pick majority
✓ Investigate disagreements seriously
✓ Use different methods, not just repeat same logic

✗ Don't use identical reasoning each time
✗ Avoid stopping at first answer
✗ Don't ignore minority answers without investigation
✗ Don't use self-consistency for subjective questions

## Example: Mathematical Problem

```
Problem: A store sells apples at $2 for 3 apples. 
If you buy 17 apples, how much do you pay?

Approach 1 (Bundle counting):
Step 1: 17 apples ÷ 3 = 5 bundles + 2 individual
Step 2: 5 bundles × $2 = $10
Step 3: 2 individual × ($2÷3) = $1.33
Answer 1: $11.33

Approach 2 (Unit price):
Step 1: Price per apple = $2 ÷ 3 = $0.667
Step 2: 17 apples × $0.667 = $11.33
Answer 2: $11.33

Approach 3 (Work backwards from total):
Step 1: For $11, I get 16.5 apples (11 ÷ 2 × 3)
Step 2: Need 0.5 more apple = $0.33
Answer 3: $11.33

Approach 4 (Proportion):
Step 1: 3 apples : $2 = 17 apples : X
Step 2: X = (17 × $2) ÷ 3 = $11.33
Answer 4: $11.33

Approach 5 (Verification):
Step 1: At $11.33, I should get 17 apples
Step 2: $11.33 ÷ $2 = 5.665 bundles
Step 3: 5.665 × 3 = 17 apples ✓
Answer 5: $11.33

Consistency Check:
- All 5 approaches agree: $11.33
- Confidence: Very High (100% agreement)
- No discrepancies to investigate

Final Answer: $11.33 (Verified through 5 independent methods)
```

## Example: Decision-Making

```
Problem: Should we migrate our database from PostgreSQL to MongoDB?

Approach 1 (Performance analysis):
- Current query patterns: 80% relational, 20% document
- Relational queries would become slower in MongoDB
- Document queries marginally faster
Conclusion 1: Stay with PostgreSQL

Approach 2 (Cost analysis):
- Migration cost: $50K (engineering time)
- MongoDB licenses: +$10K/year
- PostgreSQL optimizations: $15K (one-time)
- 3-year TCO: MongoDB $80K, PostgreSQL $15K
Conclusion 2: Stay with PostgreSQL

Approach 3 (Team expertise):
- Team has 5 years PostgreSQL experience
- MongoDB: No experience, 6-month learning curve
- Risk of migration bugs: High
Conclusion 3: Stay with PostgreSQL

Approach 4 (Scalability needs):
- Current scale: 1TB, 10K QPS
- PostgreSQL scales to 10TB, 100K QPS (sufficient)
- MongoDB advantages not needed at our scale
Conclusion 4: Stay with PostgreSQL

Approach 5 (Industry trends):
- Competitors use both successfully
- No compelling reason to switch
- "If it ain't broke, don't fix it"
Conclusion 5: Stay with PostgreSQL

Consistency Check:
- All 5 analyses agree: Stay with PostgreSQL
- Confidence: Very High (unanimous)
- Key factors: Cost, expertise, current performance adequate

Final Decision: Stay with PostgreSQL
Confidence: 95%
Rationale: All analysis approaches converge on same conclusion
```

## Diversity of Approaches

### Good Diversity (Different Methods):
```
1. Mathematical formula
2. Analogical reasoning  
3. Work backwards
4. Case-by-case analysis
5. Verification approach
```

### Bad Diversity (Same Method):
```
1. Formula approach
2. Formula approach (slightly different notation)
3. Formula approach (in different order)
4. Formula approach (with verification)
5. Formula approach (repeated)
```

**Key:** Use genuinely different reasoning strategies.

## Handling Disagreements

### If 3/5 Agree:
```
Majority: Answer A (3 times)
Minority: Answer B (2 times)

Action:
1. Review minority reasoning carefully
2. Check for calculation errors in majority
3. Consider if minority found edge case
4. Re-calculate using hybrid approach
5. Report: "Answer A with medium confidence (60%)"
```

### If No Consensus (2-2-1 split):
```
Answer A: 2 times
Answer B: 2 times  
Answer C: 1 time

Action:
1. Add 2-3 more approaches to break tie
2. Deep-dive into why disagreement exists
3. Check assumptions in each approach
4. Report: "Low confidence, needs more analysis"
```

## Confidence Scoring

| Agreement | Confidence | Action |
|-----------|-----------|--------|
| 5/5 or 4/5 | Very High (90%+) | Accept answer |
| 3/5 | Medium (60-70%) | Investigate minority views |
| 2/5 or worse | Low (<50%) | Add more approaches or flag for review |

## Token Cost

Self-Consistency is very expensive:

**Typical costs:**
- 5 approaches × 100 tokens each = 500 tokens
- Consistency check: 100 tokens
- **Total: 500-3000+ tokens**

**When to justify the cost:**
- Critical business decisions
- Safety-critical calculations
- Legal/compliance requirements
- High-value transactions
- Medical diagnoses support

## Optimization Strategies

### Reduce Paths:
```
Instead of 7 paths → Use 5 paths
Savings: ~200 tokens
Trade-off: Slightly less confidence
```

### Compact Format:
```
Don't write: "In the first approach, using the method of..."
Write: "Approach 1 (Direct): Answer = X"
```

### Early Termination:
```
If first 3 approaches all agree:
  → Can stop early with high confidence
  → Save tokens on approaches 4-5
```

## Self-Consistency vs Other Methods

| Method | Paths | Speed | Cost | Best For |
|--------|-------|-------|------|----------|
| Zero-Shot | 1 | Fast | Low | Simple tasks |
| CoT | 1 | Medium | Medium | Reasoning |
| ToT | Multiple (explored) | Slow | High | Planning |
| Self-Consistency | Multiple (independent) | Slow | High | Verification |

**Key difference:** 
- **ToT** explores paths in a tree (dependent)
- **Self-Consistency** generates independent parallel paths

## Real-World Use Cases

### ✅ Good Use Cases:
- Financial calculations for large transactions
- Medical diagnosis support (with human oversight)
- Legal contract analysis
- Engineering safety calculations
- Risk assessment for critical decisions

### ❌ Bad Use Cases:
- "What's the capital of France?" (obvious answer)
- Creative writing (variety is good)
- Subjective preferences
- Time-sensitive queries
- Low-stakes decisions

## Quick Reference

| Aspect | Recommendation |
|--------|---------------|
| **Number of paths** | 5-7 approaches |
| **Diversity** | Use genuinely different methods |
| **Agreement threshold** | 60%+ for medium confidence |
| **Token cost** | 500-3000+ tokens |
| **Best for** | High-stakes, objective problems |
| **Confidence metric** | % of approaches agreeing |

---

**Related:**
- [Chain of Thought](chain-of-thought.md) - Single reasoning path
- [Tree of Thoughts](tree-of-thoughts.md) - Explored paths (not independent)
- [Decision Matrix](decision_matrix.md) - When to use Self-Consistency

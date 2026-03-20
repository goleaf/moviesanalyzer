# Chain of Thought (CoT) Prompting

## Overview

Chain of Thought prompting encourages the LLM to break down complex problems into intermediate reasoning steps, making the thought process explicit and transparent.

## When to Use

✅ **Use CoT When:**
- Multi-step reasoning required
- Mathematical or logical problems
- Need transparency in reasoning
- Complex decision-making tasks
- Problem involves calculations or deductions

❌ **Don't Use CoT When:**
- Simple, single-step tasks
- Just need final answer quickly
- Token budget is extremely tight
- Task doesn't benefit from step-by-step breakdown

## Variants

### 1. Zero-Shot CoT
Simply add "Let's think step by step" to prompt.
```
Problem: [Your problem]
Let's think step by step:
```

### 2. Few-Shot CoT
Provide examples with reasoning steps shown.
```
Example:
Problem: [Example problem]
Step 1: [First reasoning step]
Step 2: [Second reasoning step]
Answer: [Result]

Now solve:
Problem: [Your problem]
```

### 3. Auto-CoT
Let model generate reasoning automatically with minimal guidance.

## Template: Zero-Shot CoT

```
Problem: [State the problem clearly]

Let's solve this step by step:

1. First, let's identify what we know:
   [List known information]

2. Next, let's determine what we need to find:
   [List unknowns]

3. Now, let's work through the solution:
   Step 1: [First reasoning step]
   Step 2: [Second reasoning step]
   Step 3: [Third reasoning step]

4. Finally, let's verify our answer:
   [Verification step]

Answer: [Final answer]
```

## Template: Few-Shot CoT (XML for Claude)

```xml
<chain_of_thought>
  <examples>
    <example>
      <problem>[Problem 1]</problem>
      <reasoning>
        <step number="1">[First step]</step>
        <step number="2">[Second step]</step>
        <step number="3">[Third step]</step>
      </reasoning>
      <answer>[Answer 1]</answer>
    </example>
  </examples>

  <new_problem>
    [Your actual problem]
  </new_problem>

  <instruction>
    Please solve using the same step-by-step reasoning approach.
  </instruction>
</chain_of_thought>
```

## Real-World Example

### Math Problem

```
Problem: A store offers 20% discount on items over $100, and additional 10% member discount. 
If an item costs $150 and I'm a member, what's the final price?

Let's think step by step:

1. Original price: $150
2. First, apply 20% discount (item over $100): 
   $150 × 0.80 = $120
3. Then, apply 10% member discount on discounted price: 
   $120 × 0.90 = $108
4. Verification: Total discount = $150 - $108 = $42 (28%)
   This makes sense: 20% + 10% of remaining ≈ 28%

Answer: $108
```

## Best Practices

✓ Break down into clear, numbered steps
✓ Show all intermediate calculations
✓ Make assumptions explicit
✓ Verify answer makes sense
✓ Use consistent step format

✗ Don't skip critical steps
✗ Avoid jumping to conclusions
✗ Don't mix multiple calculations in one step

## Token Cost

- Typical: 100-500 tokens
- Trade-off: More tokens for better accuracy and transparency

---

**Related:**
- [Few-Shot](few-shot.md) - Combine with CoT for Few-Shot CoT
- [Self-Consistency](self-consistency.md) - Multiple CoT paths
- [Decision Matrix](decision_matrix.md) - When to use CoT

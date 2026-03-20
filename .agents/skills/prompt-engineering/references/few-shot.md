# Few-Shot Prompting

## Overview

Few-shot prompting provides 2-10 examples in the prompt to guide the LLM's response format, style, and pattern. The model learns from these examples to apply the same pattern to new inputs.

## When to Use

✅ **Use Few-Shot When:**
- Need specific formatting or style consistency
- Task is domain-specific or unusual
- Examples demonstrate nuances better than instructions
- You have 2-10 quality examples available
- Pattern is easier to show than explain

❌ **Don't Use Few-Shot When:**
- Task is simple and well-known (use Zero-Shot)
- No good examples available
- Examples would use too many tokens
- Each input requires unique handling

## Best Practices

### Example Quality
✓ Use 2-5 diverse, representative examples (sweet spot)
✓ Show edge cases in examples
✓ Maintain consistent format across all examples
✓ Quality over quantity - better examples > more examples
✓ Include explanations if pattern is complex

✗ Avoid too many examples (diminishing returns after 5-7)
✗ Don't use contradictory examples
✗ Avoid examples that don't cover the actual use case
✗ Don't include wrong or misleading examples

### Example Selection
1. **Coverage:** Examples should cover main variations
2. **Diversity:** Show different scenarios, not just similar ones
3. **Clarity:** Each example should be unambiguous
4. **Relevance:** Examples match the actual task domain

## Template Patterns

### Pattern 1: Input-Output Pairs (Natural Language)

```
Task: [Clear description]

Here are examples of the desired output:

Example 1:
Input: [Example input 1]
Output: [Example output 1]

Example 2:
Input: [Example input 2]
Output: [Example output 2]

Example 3:
Input: [Example input 3]
Output: [Example output 3]

Now, apply the same pattern to:
Input: [Your actual input]
Output:
```

### Pattern 2: Structured JSON Format

```json
{
  "task": "[Task description]",
  "examples": [
    {
      "input": "[Example input 1]",
      "output": "[Example output 1]",
      "explanation": "[Why this output is correct]"
    },
    {
      "input": "[Example input 2]",
      "output": "[Example output 2]",
      "explanation": "[Why this output is correct]"
    },
    {
      "input": "[Example input 3]",
      "output": "[Example output 3]",
      "explanation": "[Why this output is correct]"
    }
  ],
  "new_input": "[Your actual input]"
}
```

### Pattern 3: XML Format (Claude-Optimized)

```xml
<few_shot_prompt>
  <task>[Task description]</task>

  <examples>
    <example>
      <input>[Example input 1]</input>
      <output>[Example output 1]</output>
      <note>[Optional explanation]</note>
    </example>

    <example>
      <input>[Example input 2]</input>
      <output>[Example output 2]</output>
      <note>[Optional explanation]</note>
    </example>

    <example>
      <input>[Example input 3]</input>
      <output>[Example output 3]</output>
      <note>[Optional explanation]</note>
    </example>
  </examples>

  <new_input>
    [Your actual input]
  </new_input>
</few_shot_prompt>
```

## Real-World Examples

### Example 1: Product Feature Extraction

**Task:** Extract product features from customer reviews in structured format.

```
Task: Extract product features from reviews

Example 1:
Input: "This phone has amazing battery life, lasts 2 days!"
Output: {"feature": "battery_life", "sentiment": "positive", "detail": "2 days"}

Example 2:
Input: "Camera quality is terrible in low light"
Output: {"feature": "camera", "sentiment": "negative", "detail": "poor low light"}

Example 3:
Input: "Screen is gorgeous, super bright and colorful"
Output: {"feature": "screen", "sentiment": "positive", "detail": "bright and colorful"}

Now apply to:
Input: "The speaker volume is disappointing, barely audible outdoors"
Output:
```

**Expected:** `{"feature": "speaker", "sentiment": "negative", "detail": "low volume outdoors"}`

### Example 2: Email Classification

```
Task: Classify customer emails into categories

Example 1:
Email: "I can't log into my account, password reset doesn't work"
Category: technical_support
Priority: high
Reason: Account access issue

Example 2:
Email: "When will the new product line be available?"
Category: general_inquiry
Priority: low
Reason: Product information request

Example 3:
Email: "I was charged twice for the same order #12345"
Category: billing_issue
Priority: high
Reason: Payment problem

Now classify:
Email: "Can you send me the invoice for last month's subscription?"
Category:
Priority:
Reason:
```

### Example 3: Code Documentation Style

```
Task: Write Python docstrings following project style

Example 1:
def calculate_total(items, tax_rate):
    """Calculate total price including tax.

    Args:
        items (list): List of item prices
        tax_rate (float): Tax rate as decimal (e.g., 0.08 for 8%)

    Returns:
        float: Total price with tax applied

    Example:
        >>> calculate_total([10, 20], 0.08)
        32.4
    """

Example 2:
def validate_email(email):
    """Check if email address is valid format.

    Args:
        email (str): Email address to validate

    Returns:
        bool: True if valid format, False otherwise

    Example:
        >>> validate_email("user@example.com")
        True
    """

Now document this function:
def merge_dicts(dict1, dict2, overwrite=True):
    [implementation here]
```

## Choosing the Right Number of Examples

| Number of Examples | Best For | Trade-offs |
|-------------------|----------|------------|
| 1 example | Very simple patterns | May not capture variation |
| 2-3 examples | Most tasks (recommended) | Good balance of clarity vs tokens |
| 4-5 examples | Complex patterns with variations | Higher token cost |
| 6-10 examples | Domain-specific or unusual tasks | Diminishing returns, high cost |
| 10+ examples | Fine-tuning might be better | Token limit issues |

**Rule of thumb:** Start with 3 examples. Add more only if output quality is poor.

## Common Patterns by Domain

### Data Extraction
```
Input: [Unstructured text]
Output: {structured JSON}
```
Best with 3-4 examples showing different data types.

### Text Transformation
```
Input: [Text in one style]
Output: [Text in another style]
```
Best with 2-3 examples showing transformation pattern.

### Classification
```
Input: [Item to classify]
Output: Category name + confidence/reasoning
```
Best with 3-5 examples covering main categories.

### Code Generation
```
Input: [Natural language description]
Output: [Code implementation]
```
Best with 2-3 examples showing coding style.

## Combining Few-Shot with Other Techniques

### Few-Shot + Chain of Thought (Few-Shot CoT)

Show examples with reasoning steps:

```
Problem: If 5 apples cost $3, how much do 8 apples cost?

Example with reasoning:
Problem: If 3 oranges cost $2, how much do 7 oranges cost?
Reasoning:
  Step 1: Cost per orange = $2 ÷ 3 = $0.67
  Step 2: Cost of 7 oranges = $0.67 × 7 = $4.69
Answer: $4.69

Now solve:
Problem: If 5 apples cost $3, how much do 8 apples cost?
Reasoning:
```

See [chain-of-thought.md](chain-of-thought.md) for more details.

## Common Mistakes & Fixes

### ❌ Mistake 1: Too Many Similar Examples
```
Example 1: "Great product!" → Positive
Example 2: "Excellent item!" → Positive
Example 3: "Amazing purchase!" → Positive
```
**Problem:** All examples are too similar, doesn't show variation.

✅ **Fix:** Show diverse examples
```
Example 1: "Great product!" → Positive
Example 2: "Terrible quality, broke immediately" → Negative
Example 3: "It's okay, nothing special" → Neutral
```

### ❌ Mistake 2: Inconsistent Format
```
Example 1:
Input: "text here"
Output: positive

Example 2:
Text: "more text"
Result: {"sentiment": "negative", "score": 0.8}
```
**Problem:** Format changes between examples.

✅ **Fix:** Keep consistent format
```
Example 1:
Input: "text here"
Output: {"sentiment": "positive", "score": 0.9}

Example 2:
Input: "more text"
Output: {"sentiment": "negative", "score": 0.8}
```

## Quick Reference

| Aspect | Recommendation |
|--------|---------------|
| **Optimal number** | 2-5 examples |
| **Format** | Match output use (JSON for APIs, XML for Claude) |
| **Diversity** | Show main variations and edge cases |
| **Explanation** | Add if pattern is non-obvious |
| **Token cost** | ~200-800 tokens (3 examples) |
| **Best for** | Format consistency, style matching |

---

**Related:**
- [Zero-Shot Prompting](zero-shot.md) - When you have no examples
- [Chain of Thought](chain-of-thought.md) - Add reasoning to examples
- [Decision Matrix](decision_matrix.md) - Choosing Few-Shot vs other methods

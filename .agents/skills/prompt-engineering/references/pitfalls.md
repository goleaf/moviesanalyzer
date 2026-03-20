# Common Pitfalls & Solutions

## Overview

This guide covers the most common mistakes in prompt engineering and how to fix them.

---

## Pitfall 1: Ambiguous Instructions

### ❌ Bad Example
```
Make this better.
```

**Problems:**
- No definition of "better"
- No specific improvements requested
- No success criteria

### ✅ Good Example
```
Improve this code by:
1. Adding error handling for null inputs
2. Optimizing the algorithm from O(n²) to O(n log n)
3. Adding docstring documentation
4. Following PEP 8 style guide

Success criteria:
- All edge cases handled
- Performance improved >50%
- All functions documented
```

**Fix:** Be specific about what improvements you want and how to measure success.

---

## Pitfall 2: No Examples When Needed

### ❌ Bad Example
```
Extract product features from reviews in structured format.
```

**Problems:**
- "Structured format" is vague
- No example of desired output
- Ambiguous what constitutes a "feature"

### ✅ Good Example
```
Extract product features from reviews in structured format.

Example 1:
Input: "Battery life is amazing, lasts 2 days easily!"
Output: {"feature": "battery_life", "sentiment": "positive", "detail": "2 days"}

Example 2:
Input: "Screen brightness is poor in sunlight"
Output: {"feature": "screen", "sentiment": "negative", "detail": "poor sunlight visibility"}

Now process: [actual review]
```

**Fix:** Provide 2-3 concrete examples showing exact input→output mapping.

---

## Pitfall 3: Wrong Format for Target LLM

### ❌ Bad Example (JSON for Claude with complex hierarchy)
```json
{
  "complex": {
    "nested": {
      "structure": {
        "with": {
          "many": {
            "levels": "for Claude"
          }
        }
      }
    }
  }
}
```

**Problems:**
- Claude works better with XML for complex structures
- Deep nesting is hard to read
- Missing semantic meaning

### ✅ Good Example (XML for Claude)
```xml
<complex>
  <nested>
    <structure>
      <content>For Claude with clear hierarchy</content>
    </structure>
  </nested>
</complex>
```

**Fix:** Use XML for Claude (especially complex structures), JSON for GPT/APIs.

---

## Pitfall 4: Mixing Techniques Incorrectly

### ❌ Bad Example
```
Let's think step by step [Zero-shot CoT]
Also here are some examples [Few-shot]
But don't use the examples, reason from scratch [Contradiction]
```

**Problems:**
- Contradictory instructions
- Unclear which technique to apply
- Confusing guidance

### ✅ Good Example (Intentional combination)
```
Here are examples showing step-by-step reasoning [Few-shot CoT]:

Example 1:
Problem: [problem]
Step 1: [reasoning step]
Step 2: [reasoning step]
Answer: [answer]

Now apply the same reasoning approach to: [your problem]
```

**Fix:** Either pick one technique or combine them intentionally (e.g., Few-Shot CoT).

---

## Pitfall 5: No Output Specification

### ❌ Bad Example
```
Analyze this data.
```

**Problems:**
- Unclear what "analyze" means
- No output format specified
- Could return anything

### ✅ Good Example
```
Analyze this data and output results in this EXACT JSON format:
{
  "summary": "brief overview",
  "key_findings": ["finding 1", "finding 2"],
  "recommendations": ["rec 1", "rec 2"],
  "confidence_score": 0.85
}

Analysis requirements:
- Identify top 3 findings minimum
- Confidence score based on data quality
- Recommendations must be actionable
```

**Fix:** Specify exact output format, structure, and requirements.

---

## Pitfall 6: Overcomplicating Simple Tasks

### ❌ Bad Example (ToT for temperature conversion)
```
Use Tree of Thoughts to explore multiple approaches for converting 25°C to Fahrenheit.

Level 1 options:
A) Use formula F = C × 9/5 + 32
B) Use online converter
C) Ask someone else

Evaluate each...
[Massive overhead for simple: 25 × 9/5 + 32 = 77°F]
```

**Problems:**
- Extreme overkill for simple task
- Wastes tokens
- Slower response

### ✅ Good Example (Simple zero-shot)
```
Convert 25°C to Fahrenheit.
```

**Fix:** Match technique complexity to task complexity. Simple tasks = simple prompts.

---

## Pitfall 7: Insufficient Context

### ❌ Bad Example
```
Fix this bug.
[No code, no error message, no context]
```

**Problems:**
- No code provided
- No error message
- No context about what's wrong

### ✅ Good Example
```
Fix this bug in my Python web scraper:

CODE:
```python
response = requests.get(url)
data = response.json()
```

ERROR:
JSONDecodeError: Expecting value: line 1 column 1 (char 0)

CONTEXT:
- Scraping product data from e-commerce site
- URL returns HTML, not JSON
- Need to parse HTML instead
- Using BeautifulSoup is okay

Please fix and explain the issue.
```

**Fix:** Provide code, error messages, context, and constraints.

---

## Pitfall 8: Unclear Success Criteria

### ❌ Bad Example
```
Write a good function for processing payments.
```

**Problems:**
- "Good" is subjective
- No requirements specified
- No test cases

### ✅ Good Example
```
Write a payment processing function that:
1. Accepts amount (float) and payment method (string)
2. Validates amount > 0
3. Returns transaction ID on success, error message on failure
4. Handles exceptions: InvalidAmount, UnsupportedPaymentMethod
5. Includes type hints and docstring

Test cases must pass:
- process_payment(100.0, "credit_card") → "TXN_12345"
- process_payment(-50, "credit_card") → raises InvalidAmount
- process_payment(100, "bitcoin") → raises UnsupportedPaymentMethod
```

**Fix:** Define specific requirements, constraints, and test cases.

---

## Pitfall 9: Ignoring Token Limits

### ❌ Bad Example (10 few-shot examples for simple task)
```
Example 1: [100 tokens]
Example 2: [100 tokens]
...
Example 10: [100 tokens]
Total: 1000+ tokens just for examples
```

**Problems:**
- Diminishing returns after 3-5 examples
- Wastes token budget
- Higher cost, slower response

### ✅ Good Example (3 diverse examples)
```
Example 1: [Simple case - 50 tokens]
Example 2: [Edge case - 50 tokens]
Example 3: [Complex case - 50 tokens]
Total: ~150 tokens
```

**Fix:** Use 2-5 well-chosen examples instead of many redundant ones.

---

## Pitfall 10: No Validation/Verification

### ❌ Bad Example
```
Calculate the ROI and that's it.
```

**Problems:**
- No verification step
- Could produce wrong answers
- No sanity checks

### ✅ Good Example
```
Calculate the ROI using this formula:
ROI = (Net Profit / Investment Cost) × 100

After calculating:
1. Verify ROI is between -100% and 1000% (sanity check)
2. Show intermediate values: Net Profit, Investment Cost
3. Explain if result seems unusual

If ROI < -100% or > 1000%, flag as "Requires review - unusual value"
```

**Fix:** Add verification steps, sanity checks, and validation logic.

---

## Quick Reference: Common Fixes

| Problem | Quick Fix |
|---------|-----------|
| Vague instructions | Be specific: what, how, why |
| No examples | Add 2-3 concrete input→output examples |
| Wrong format | XML for Claude, JSON for GPT/APIs |
| Mixed techniques | Pick one or combine intentionally |
| No output spec | Specify exact format + schema |
| Overcomplicated | Match technique to task complexity |
| Missing context | Provide code, errors, constraints |
| Unclear criteria | Define requirements + test cases |
| Token bloat | Use 2-5 examples max |
| No validation | Add verification + sanity checks |

---

## Prevention Checklist

Before sending your prompt, verify:

- [ ] Instructions are specific and unambiguous
- [ ] Examples provided if needed (2-5 max)
- [ ] Format matches target LLM (XML for Claude, JSON for GPT)
- [ ] One clear technique chosen
- [ ] Output format explicitly specified
- [ ] Complexity matches task
- [ ] Sufficient context provided
- [ ] Success criteria defined
- [ ] Token usage reasonable
- [ ] Validation/verification included

---

**Related:**
- [Decision Matrix](decision_matrix.md) - Choosing right technique
- [Zero-Shot](zero-shot.md) - Simple task prompting
- [Few-Shot](few-shot.md) - Example-based prompting

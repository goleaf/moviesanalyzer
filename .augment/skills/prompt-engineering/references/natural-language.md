# Natural Language Format

## When to Use Natural Language

✅ **Use Natural Language When:**
- Simple, straightforward tasks
- General conversation or explanation
- Brainstorming or creative writing
- Structure adds no value
- Quick queries or ad-hoc requests
- Exploring ideas without rigid format

❌ **Don't Use Natural Language When:**
- Output must be parsed by code (use JSON)
- Complex nested structure needed (use XML/JSON)
- Multiple clear sections required (use structured format)
- Consistency across many prompts needed (use templates)

## Best Practices

✓ Be explicit and specific
✓ Use clear action verbs (analyze, extract, generate)
✓ Number steps when order matters
✓ Include examples for clarity
✓ Define constraints upfront
✓ Specify output format if needed

✗ Don't be overly verbose
✗ Avoid ambiguous pronouns (it, this, that)
✗ Don't assume implicit knowledge
✗ Avoid jargon without definition
✗ Don't mix multiple tasks in one prompt

## Template: Simple Task

```
[Clear, direct instruction]

Context: [Relevant background]

Requirements:
1. [Requirement 1]
2. [Requirement 2]
3. [Requirement 3]

Please [specific action] and provide [expected output].
```

## Template: Complex Task with Structure

```
TASK: [Main task description]

BACKGROUND:
[Relevant context that informs the task]

SPECIFIC REQUIREMENTS:
1. [Detailed requirement 1]
2. [Detailed requirement 2]
3. [Detailed requirement 3]

CONSTRAINTS:
- [Constraint 1]
- [Constraint 2]

EXAMPLES:
Example 1:
[Input/scenario 1]
Expected: [Output 1]

Example 2:
[Input/scenario 2]
Expected: [Output 2]

OUTPUT FORMAT:
[Describe how to structure the response]

QUALITY CRITERIA:
- [Criterion 1]
- [Criterion 2]

Please complete the task following the above guidelines.
```

## Template: Conversational/Iterative

```
I need help with [task/problem].

Here's what I've tried so far:
[Previous attempts or context]

Specifically, I'm struggling with:
[Specific challenge]

What I need from you:
1. [Specific help item 1]
2. [Specific help item 2]

Additional context that might be relevant:
[Any other useful information]

Please provide [type of response] that [specific outcome].
```

## Real-World Examples

### Example 1: Code Review

```
I need a code review for a Python function that processes user payments.

CODE:
```python
def process_payment(user_id, amount, card_info):
    user = db.get_user(user_id)
    charge = stripe.charge(amount, card_info)
    if charge.success:
        user.balance += amount
        db.save(user)
        return True
    return False
```

CONTEXT:
This is for a subscription service. Users can add funds to their account balance.

SPECIFIC CONCERNS:
1. Are there any security vulnerabilities?
2. Is error handling sufficient?
3. Should this be wrapped in a transaction?
4. Is the logic sound?

Please review focusing on security and reliability, and suggest specific improvements.
```

### Example 2: Content Generation

```
Write a blog post introduction about the benefits of meditation for busy professionals.

REQUIREMENTS:
- Length: 150-200 words
- Tone: Professional but approachable
- Include a hook in the first sentence
- Mention 2-3 specific benefits
- End with a transition to the main content

TARGET AUDIENCE:
Corporate professionals aged 30-45 who work long hours and feel stressed.

AVOID:
- Overly spiritual language
- Unsubstantiated claims
- Clichés like "in today's fast-paced world"
```

### Example 3: Data Analysis Request

```
Analyze the following customer feedback data and identify the top 3 issues.

DATA:
- 45 mentions of "slow loading time"
- 23 mentions of "confusing interface"
- 67 mentions of "expensive pricing"
- 12 mentions of "missing features"
- 34 mentions of "poor customer support"

For each top issue:
1. Explain why it's significant
2. Suggest a potential solution
3. Estimate impact if fixed (low/medium/high)

Format as a brief report (300 words max).
```

## Structuring for Clarity

### Use Headers for Sections:
```
TASK: [What to do]

CONTEXT: [Background]

REQUIREMENTS: [What's needed]

OUTPUT: [Expected format]
```

### Use Lists for Multiple Items:
```
Requirements:
- Item 1
- Item 2
- Item 3
```

### Use Numbers for Sequential Steps:
```
Process:
1. First, do X
2. Then, do Y
3. Finally, do Z
```

### Use Examples for Clarity:
```
Bad example: "Sort the data"

Good example: "Sort the data by date (newest first), 
then by priority (high to low)"
```

## Common Patterns

### Analysis Task:
```
Analyze [subject] focusing on:
1. [Aspect 1]
2. [Aspect 2]
3. [Aspect 3]

Provide insights on [specific question].
```

### Generation Task:
```
Generate [output type] that:
- Meets [requirement 1]
- Includes [requirement 2]
- Avoids [constraint]

Style: [tone/style guide]
Length: [word/character count]
```

### Explanation Task:
```
Explain [concept] to [audience].

Coverage:
- What it is
- Why it matters
- How it works
- When to use it

Use analogies and examples to clarify.
```

### Transformation Task:
```
Transform [input] into [output format].

Maintain: [what to preserve]
Change: [what to modify]
Remove: [what to exclude]

Example:
Input: [example input]
Output: [example output]
```

## Action Verbs for Clarity

| Instead of... | Use specific verb |
|--------------|-------------------|
| "Do something with..." | Analyze, Extract, Generate, Summarize |
| "Look at..." | Review, Evaluate, Assess, Examine |
| "Make..." | Create, Build, Design, Construct |
| "Fix..." | Correct, Repair, Improve, Optimize |
| "Change..." | Transform, Convert, Modify, Refactor |

## Specifying Output Format (in Natural Language)

### Instead of:
```
"Give me the results"
```

### Be Specific:
```
"Provide results as a bullet list with:
- Issue name
- Severity (Low/Medium/High)
- Recommended action

Example:
• Slow page load - High - Optimize images and enable caching
• Broken link - Medium - Update link to new URL"
```

## Handling Ambiguity

### ❌ Ambiguous:
```
"Make this better"
```

### ✅ Clear:
```
"Improve this code by:
1. Adding error handling for null inputs
2. Optimizing from O(n²) to O(n log n)
3. Adding docstrings to all functions"
```

### ❌ Ambiguous:
```
"Summarize this document"
```

### ✅ Clear:
```
"Summarize this document in 200 words focusing on:
1. Main conclusions
2. Key recommendations
3. Next steps

Use bullet points for readability."
```

## Combining with Other Formats

Natural language can introduce structured formats:

```
Analyze the customer reviews and return results in JSON format:

{
  "sentiment": "positive|negative|neutral",
  "key_themes": ["theme1", "theme2"],
  "action_items": ["action1", "action2"]
}

Focus on identifying actionable insights for product improvement.
```

## Quick Reference: Natural Language Checklist

When writing natural language prompts, ensure:

- [ ] Clear action verb (analyze, generate, explain)
- [ ] Specific requirements listed
- [ ] Output format described
- [ ] Examples provided if pattern not obvious
- [ ] Constraints mentioned
- [ ] Context/background included
- [ ] Success criteria defined
- [ ] Ambiguity eliminated

## Common Mistakes

### ❌ Mistake 1: Too Vague
```
"Tell me about AI"
```
**Problem:** Too broad, unclear what aspect to cover

✅ **Fix:**
```
"Explain how transformer models work in AI, 
focusing on the attention mechanism. 
Target audience: software engineers with basic ML knowledge.
Length: 300 words."
```

### ❌ Mistake 2: Assuming Context
```
"Update the function to handle this"
```
**Problem:** "this" is ambiguous, "update" is vague

✅ **Fix:**
```
"Update the `calculate_total()` function to handle 
null values in the items array by treating them as 0."
```

### ❌ Mistake 3: Multiple Tasks Mixed
```
"Analyze the data, fix the bugs, and write documentation"
```
**Problem:** Three separate tasks in one prompt

✅ **Fix:**
```
"First, analyze the data for anomalies and outliers. 
Report findings in a brief summary.

Then, in a separate response, I'll ask you to 
address the bugs and documentation."
```

## When Natural Language Excels

✅ **Best for:**
- Quick questions ("What is...?")
- Exploratory conversations
- Brainstorming sessions
- Explaining concepts
- Ad-hoc analysis requests
- Creative writing prompts

## When to Switch to Structured Formats

Switch from Natural Language to structured format when:
- Need machine parsing → JSON
- Complex hierarchy → XML (Claude) or JSON
- Team collaboration → YAML
- Consistent format across prompts → Templates
- Multiple similar tasks → Few-Shot examples

---

**Related:**
- [Zero-Shot](zero-shot.md) - Simple prompting with natural language
- [XML Format](xml-format.md) - Structured alternative for Claude
- [JSON Format](json-format.md) - Structured alternative for APIs
- [YAML Format](yaml-format.md) - Readable structured alternative

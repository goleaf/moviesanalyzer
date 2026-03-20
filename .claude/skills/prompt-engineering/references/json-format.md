# JSON Format for Structured Output

## When to Use JSON

✅ **Use JSON When:**
- Target is GPT, Gemini, or multi-LLM systems
- Output needs to be parsed by code/APIs
- API integration required
- Structured data with arrays/objects needed
- Multi-LLM compatibility important

❌ **Don't Use JSON When:**
- Humans will frequently edit (use YAML)
- Very complex hierarchies for Claude (consider XML)
- Simple explanation needed (use Natural Language)

## Best Practices

✓ Provide clear JSON schema
✓ Use consistent key naming (snake_case or camelCase)
✓ Include type hints in schema
✓ Validate JSON structure
✓ Keep nesting reasonable (<4 levels)

✗ Don't use ambiguous field names
✗ Avoid deeply nested structures (>5 levels)
✗ Don't mix naming conventions
✗ Avoid comments (not in standard JSON)

## Template: Basic Structure

```json
{
  "task": {
    "description": "[What to do]",
    "type": "[Task category]",
    "priority": "high|medium|low"
  },
  "context": {
    "background": "[Background info]",
    "constraints": [
      "[Constraint 1]",
      "[Constraint 2]"
    ]
  },
  "requirements": {
    "must_include": ["[Requirement 1]", "[Requirement 2]"],
    "must_avoid": ["[Thing to avoid 1]"],
    "output_format": "[Desired format]"
  },
  "examples": [
    {
      "input": "[Example input]",
      "output": "[Example output]",
      "explanation": "[Why this is correct]"
    }
  ]
}
```

## Template: With JSON Schema

```json
{
  "prompt": {
    "instruction": "[Main instruction]",
    "context": "[Context information]"
  },
  "output_schema": {
    "type": "object",
    "properties": {
      "result": {
        "type": "string",
        "description": "The main result"
      },
      "confidence": {
        "type": "number",
        "minimum": 0,
        "maximum": 1,
        "description": "Confidence score 0-1"
      },
      "reasoning": {
        "type": "array",
        "items": {
          "type": "string"
        },
        "description": "Step-by-step reasoning"
      }
    },
    "required": ["result", "confidence"]
  }
}
```

## Template: Function/Tool Calling

```json
{
  "task": "Analyze query and determine which tools to use",
  "available_tools": [
    {
      "name": "web_search",
      "description": "Search the web for current information",
      "parameters": {
        "query": "string (required)",
        "num_results": "integer (optional, default 5)"
      }
    },
    {
      "name": "calculator",
      "description": "Perform mathematical calculations",
      "parameters": {
        "expression": "string (required)"
      }
    }
  ],
  "user_query": "[User's question]",
  "expected_response": {
    "thoughts": "Brief reasoning about which tool(s) to use",
    "tool_calls": [
      {
        "tool": "tool_name",
        "parameters": {
          "param1": "value1"
        },
        "reason": "Why this tool is needed"
      }
    ]
  }
}
```

## Example: Sentiment Analysis

```json
{
  "task": "Analyze sentiment of customer reviews",
  "instructions": "For each review, extract sentiment, themes, confidence",
  "reviews": [
    "The product is amazing! Best purchase ever.",
    "Terrible quality, broke after one day."
  ],
  "output_format": {
    "results": [
      {
        "review_id": 1,
        "text": "original review text",
        "sentiment": "positive|negative|neutral",
        "confidence": 0.95,
        "key_themes": ["quality", "durability"],
        "emotional_tone": "enthusiastic"
      }
    ],
    "summary": {
      "overall_sentiment": "mixed",
      "positive_count": 1,
      "negative_count": 1,
      "neutral_count": 0
    }
  }
}
```

## Naming Conventions

### snake_case (Recommended for Python)
```json
{
  "user_name": "John",
  "email_address": "john@example.com",
  "is_verified": true
}
```

### camelCase (Recommended for JavaScript)
```json
{
  "userName": "John",
  "emailAddress": "john@example.com",
  "isVerified": true
}
```

**Rule:** Pick one and be consistent throughout the entire JSON.

## Handling Special Characters

### Escaping Required:
- `"` → `\"`
- `\` → `\\`
- `/` → `\/` (optional but recommended)
- Newline → `\n`
- Tab → `\t`

### Example:
```json
{
  "message": "He said \"Hello\" and left.\nNext line here."
}
```

## Common Patterns

### API Response Structure
```json
{
  "status": "success|error",
  "data": {
    // Your actual data
  },
  "metadata": {
    "timestamp": "ISO-8601",
    "version": "1.0"
  },
  "error": null
}
```

### Paginated Data
```json
{
  "items": [...],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

## Token Efficiency

JSON is more compact than XML:

**JSON:** `{"name": "John", "age": 30}` ≈ 15 tokens
**XML:** `<person><name>John</name><age>30</age></person>` ≈ 25 tokens

**Advantage:** ~40% fewer tokens than XML.

## Validation

Always specify expected structure:

```json
{
  "instruction": "Extract entities and return in this EXACT format",
  "expected_format": {
    "entities": [
      {
        "text": "entity text",
        "type": "PERSON|ORG|LOCATION",
        "confidence": 0.95
      }
    ]
  },
  "validation_rules": [
    "confidence must be between 0 and 1",
    "type must be one of: PERSON, ORG, LOCATION",
    "entities array cannot be empty"
  ]
}
```

---

**Related:**
- [XML Format](xml-format.md) - Alternative for Claude
- [YAML Format](yaml-format.md) - Human-readable alternative
- [Decision Matrix](decision_matrix.md) - Format selection guide

# YAML Format for Human-Readable Prompts

## When to Use YAML

✅ **Use YAML When:**
- Prompts maintained in version control by teams
- Configuration-driven prompt systems
- Templates that non-technical users will edit
- Multi-line content is common (examples, context, docs)
- Human readability is paramount
- Need comments for documentation

❌ **Don't Use YAML When:**
- LLM has limited YAML familiarity
- Output needs programmatic parsing (use JSON)
- Whitespace sensitivity causes issues
- Simple flat structure (use Natural Language)

## Best Practices

✓ Use consistent indentation (2 or 4 spaces)
✓ Add comments to explain complex sections
✓ Use anchors (&) and aliases (*) for reusable content
✓ Validate YAML syntax before use
✓ Multi-line strings with | or >

✗ Don't mix tabs and spaces (spaces only!)
✗ Avoid overly complex nesting (>4 levels)
✗ Don't use YAML-specific features LLMs may not understand
✗ Avoid inconsistent indentation

## Template: Basic Prompt

```yaml
# Prompt configuration for [task name]
prompt:
  name: "task_name"
  version: "1.0"
  
  system:
    role: "You are an expert in [domain]"
    constraints:
      - "Constraint 1"
      - "Constraint 2"
      - "Constraint 3"
  
  task:
    description: |
      Multi-line task description.
      Can span multiple lines naturally.
      No need for escaping or special characters.
    
    requirements:
      must_include:
        - "Requirement 1"
        - "Requirement 2"
      
      must_avoid:
        - "Thing to avoid 1"
        - "Thing to avoid 2"
  
  examples:
    - input: "Example input 1"
      output: "Example output 1"
      note: "Why this is a good example"
    
    - input: "Example input 2"
      output: "Example output 2"
      note: "Edge case handling"
  
  output_format:
    type: "structured"
    template: |
      Result: [result]
      Confidence: [score]
      Reasoning: [explanation]
```

## Multi-Line Strings

### Literal Style (|) - Preserves Line Breaks

```yaml
description: |
  First line
  Second line
  Third line
  
# Result: "First line\nSecond line\nThird line\n"
```

### Folded Style (>) - Folds to Single Line

```yaml
description: >
  This is a long
  paragraph that will
  be folded into a
  single line.
  
# Result: "This is a long paragraph that will be folded into a single line."
```

**Use | for:** Code examples, structured text
**Use > for:** Long descriptions, paragraphs

## Anchors & Aliases (Reusable Content)

```yaml
# Define reusable components with & anchor
constraints_common: &common_constraints
  - "Be concise and clear"
  - "Provide specific examples"
  - "Cite sources when possible"

quality_criteria: &quality
  accuracy: "Must be factually correct"
  clarity: "Easy to understand"
  completeness: "Cover all aspects"

# Reuse with * alias
code_review:
  constraints: *common_constraints  # Reuses above
  quality: *quality

documentation:
  constraints: *common_constraints  # Same constraints
  quality: *quality
```

## Template: Prompt Library/Repository

```yaml
# Prompt library for common tasks
library:
  metadata:
    version: "2.0"
    last_updated: "2025-01-03"
    maintainer: "team@example.com"
  
  prompts:
    summarization:
      id: "sum-001"
      name: "Document Summarization"
      category: "text-processing"
      
      variants:
        brief:
          max_length: 100
          style: "bullet points"
          template: |
            Summarize the following in {max_length} words or less.
            Use {style} format.
            
            Content: {content}
        
        detailed:
          max_length: 500
          style: "paragraphs"
          template: |
            Provide a comprehensive summary of the following.
            Maximum {max_length} words.
            Format: {style}
            Include key points, conclusions, and implications.
            
            Content: {content}
    
    translation:
      id: "trans-001"
      name: "Language Translation"
      category: "language"
      
      parameters:
        source_lang: "auto-detect"
        target_lang: "required"
        preserve_formatting: true
        tone: "formal"
      
      template: |
        Translate the following text from {source_lang} to {target_lang}.
        
        Requirements:
        - Preserve formatting: {preserve_formatting}
        - Maintain {tone} tone
        - Keep technical terms accurate
        
        Text: {content}
```

## Template: Configuration-Driven System

```yaml
# config/prompts.yaml - Used by application
application:
  name: "Customer Support AI"
  version: "1.0"
  environment: "production"

prompt_chain:
  step_1_classification:
    model: "gpt-4"
    temperature: 0.3
    max_tokens: 100
    prompt: |
      Classify the following customer message:
      
      Categories:
      - technical_issue
      - billing_question
      - feature_request
      - complaint
      - general_inquiry
      
      Message: {user_message}
      
      Return only the category name.
  
  step_2_response:
    model: "claude-3-sonnet"
    temperature: 0.7
    max_tokens: 500
    prompt: |
      You are a helpful customer support agent.
      
      Customer message category: {classification}
      Customer message: {user_message}
      
      Previous conversation:
      {conversation_history}
      
      Guidelines:
      - Be empathetic and professional
      - Provide specific solutions
      - Escalate to human if needed
      - Keep response under 200 words
      
      Respond to the customer:
  
  step_3_quality_check:
    model: "claude-3-opus"
    temperature: 0.2
    max_tokens: 300
    prompt: |
      Review this customer support response for quality:
      
      Customer message: {user_message}
      Proposed response: {generated_response}
      
      Check for:
      ✓ Accuracy
      ✓ Empathy
      ✓ Completeness
      ✓ Professionalism
      
      If issues found, suggest improvements.
      If acceptable, approve with "APPROVED"
```

## Comments for Documentation

```yaml
# Main configuration file for prompt system
# Updated: 2025-01-03
# Maintainer: Team Lead

system:
  # Model selection based on task complexity
  # - Simple tasks: gpt-3.5-turbo
  # - Complex tasks: gpt-4 or claude-3-opus
  default_model: "gpt-4"
  
  # Temperature controls randomness (0.0 - 2.0)
  # Lower = more deterministic
  # Higher = more creative
  temperature: 0.7
  
  constraints:
    # Maximum response length in tokens
    # Adjust based on use case
    max_tokens: 1000
    
    # Retry logic for failed requests
    max_retries: 3
    retry_delay_seconds: 2
```

## Lists and Nested Structures

### Inline Lists:
```yaml
tags: [python, tutorial, beginner]
```

### Block Lists:
```yaml
tags:
  - python
  - tutorial
  - beginner
```

### Nested Structures:
```yaml
project:
  name: "My Project"
  modules:
    - name: "auth"
      files:
        - "login.py"
        - "logout.py"
      tests:
        - "test_login.py"
    
    - name: "database"
      files:
        - "models.py"
        - "queries.py"
```

## Data Types

```yaml
# String
name: "John Doe"
name_unquoted: John Doe  # Also valid

# Number
age: 30
price: 19.99

# Boolean
is_active: true
is_deleted: false

# Null
middle_name: null
middle_name: ~  # Also null

# List
items:
  - item1
  - item2

# Dictionary
person:
  name: "John"
  age: 30
```

## Escaping and Special Characters

YAML handles most characters naturally:

```yaml
# No escaping needed for most cases
message: "He said: Let's go!"
path: "C:\Users\Documents"

# Use quotes for strings starting with special chars
special: "- starts with dash"
colon: ": starts with colon"

# Multi-line with special characters
code: |
  def hello():
      print("Hello, world!")
      return True
```

## Common YAML Pitfalls

### ❌ Pitfall 1: Inconsistent Indentation
```yaml
# BAD - Mixed indentation
task:
  name: "test"
   description: "wrong"  # Extra space!
```

✅ **Fix:**
```yaml
task:
  name: "test"
  description: "correct"
```

### ❌ Pitfall 2: Tabs Instead of Spaces
```yaml
# BAD - Uses tabs (invisible error!)
task:
→   name: "test"  # Tab character
```

✅ **Fix:**
```yaml
task:
  name: "test"  # Spaces only
```

### ❌ Pitfall 3: Unquoted Strings with Colons
```yaml
# BAD - Colon confuses parser
title: TODO: Fix this
```

✅ **Fix:**
```yaml
title: "TODO: Fix this"  # Quoted
```

## Validation

Always validate YAML before use:

```bash
# Python
python -c "import yaml; yaml.safe_load(open('prompt.yaml'))"

# Online validators
# yamllint.com
# yaml-online-parser.appspot.com
```

## YAML vs JSON vs XML

| Feature | YAML | JSON | XML |
|---------|------|------|-----|
| **Readability** | Excellent | Good | Fair |
| **Comments** | Yes | No | Yes |
| **Multi-line** | Native | Escaped | CDATA |
| **Verbosity** | Low | Medium | High |
| **Parsing** | Slower | Fast | Medium |
| **Human editing** | Easy | Medium | Hard |

**Use YAML when:** Humans frequently edit/read
**Use JSON when:** Machines parse/generate
**Use XML when:** Claude + complex hierarchy

## Token Efficiency

YAML is moderately efficient:

**Example comparison:**
```yaml
# YAML
person:
  name: John
  age: 30
```

```json
// JSON
{"person": {"name": "John", "age": 30}}
```

```xml
<!-- XML -->
<person>
  <name>John</name>
  <age>30</age>
</person>
```

**Tokens:**
- YAML: ~20 tokens
- JSON: ~25 tokens  
- XML: ~35 tokens

YAML is more efficient than XML, comparable to JSON.

## Quick Reference

| Aspect | Recommendation |
|--------|---------------|
| **Indentation** | 2 spaces (consistent) |
| **Multi-line** | Use `|` for literal, `>` for folded |
| **Comments** | Use `#` liberally |
| **Reuse** | Use anchors `&` and aliases `*` |
| **Validation** | Always validate before use |
| **Best for** | Human-editable configs |

---

**Related:**
- [JSON Format](json-format.md) - Machine-readable alternative
- [XML Format](xml-format.md) - Claude-optimized alternative
- [Tree of Thoughts](tree-of-thoughts.md) - Good use case for YAML
- [Decision Matrix](decision_matrix.md) - Format selection guide

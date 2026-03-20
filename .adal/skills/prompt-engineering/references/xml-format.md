# XML Format for Claude (Anthropic-Optimized)

## When to Use XML

✅ **Use XML When:**
- Target LLM is Claude (Anthropic models)
- Complex hierarchical structure needed
- Multiple nested contexts or sections
- Clear semantic boundaries required
- Preventing prompt injection is critical

❌ **Don't Use XML When:**
- Output will be parsed by code (use JSON)
- Token efficiency is critical (XML is verbose)
- Target is non-Claude LLM
- Simple flat structure (use Natural Language)

## Why XML Works Well with Claude

- **Officially recommended** by Anthropic for Claude
- **Proven to improve** prompt adherence
- **Clear structure** prevents prompt injection
- **Better handling** of nested contexts
- **Semantic tags** provide meaning

## Best Practices

✓ Use descriptive, semantic tag names
✓ Keep nesting to 3-4 levels max
✓ Close all tags properly
✓ Use attributes for metadata
✓ Use CDATA for code/special characters

✗ Don't use generic tags like <data>
✗ Avoid mixing XML with other formats
✗ Don't nest too deeply (>5 levels)
✗ Avoid inconsistent tag naming

## Template: Basic Structure

```xml
<prompt>
  <context>
    <background>
      [Background information]
    </background>
    <constraints>
      <constraint type="required">[Must do X]</constraint>
      <constraint type="forbidden">[Must not do Y]</constraint>
    </constraints>
  </context>

  <task>
    <description>
      [What needs to be done]
    </description>
    <steps>
      <step priority="1">[First step]</step>
      <step priority="2">[Second step]</step>
    </steps>
  </task>

  <examples>
    <example>
      <input>[Example input]</input>
      <output>[Example output]</output>
    </example>
  </examples>

  <output_requirements>
    <format>[Desired format]</format>
    <length>[Word/token limit]</length>
    <style>[Tone and style]</style>
  </output_requirements>
</prompt>
```

## Template: Code Review

```xml
<code_review_task>
  <context>
    <language>Python</language>
    <framework>Django</framework>
    <focus_areas>
      <area>Security vulnerabilities</area>
      <area>Performance issues</area>
      <area>Code style</area>
    </focus_areas>
  </context>

  <code>
    <![CDATA[
    def process_user_input(user_data):
        query = f"SELECT * FROM users WHERE name = '{user_data}'"
        return db.execute(query)
    ]]>
  </code>

  <review_criteria>
    <security weight="critical">
      Check for SQL injection, XSS, authentication issues
    </security>
    <performance weight="high">
      Check for N+1 queries, inefficient algorithms
    </performance>
    <style weight="medium">
      Check PEP 8 compliance, naming conventions
    </style>
  </review_criteria>

  <output_format>
    <section name="critical_issues">List blocking issues</section>
    <section name="warnings">List concerning patterns</section>
    <section name="suggestions">List improvements</section>
  </output_format>
</code_review_task>
```

## Using CDATA for Special Characters

When including code, scripts, or text with special characters:

```xml
<code>
  <![CDATA[
    // Your code here with <, >, &, etc.
    if (x < 10 && y > 5) {
        return "special chars work fine";
    }
  ]]>
</code>
```

## Attributes vs Nested Tags

### Use Attributes for Metadata:
```xml
<requirement priority="high" type="security">
  Use encrypted connections
</requirement>
```

### Use Nested Tags for Content:
```xml
<requirement>
  <priority>high</priority>
  <type>security</type>
  <description>Use encrypted connections</description>
</requirement>
```

**Rule:** Attributes for simple metadata, nested tags for complex content.

## Common Patterns

### Multi-Context Prompt
```xml
<system_context>
  <role>You are [role]</role>
  <expertise>[Areas]</expertise>
</system_context>

<task_context>
  <user_request>[Request]</user_request>
  <background>[Context]</background>
</task_context>

<execution_instructions>
  <approach>[Method]</approach>
  <constraints>[Limits]</constraints>
</execution_instructions>
```

### Hierarchical Data
```xml
<project>
  <metadata>
    <name>Project Name</name>
    <version>1.0</version>
  </metadata>
  
  <modules>
    <module name="auth">
      <files>
        <file>login.py</file>
        <file>logout.py</file>
      </files>
    </module>
  </modules>
</project>
```

## Token Overhead

XML is more verbose than JSON but provides better structure for Claude.

**Example comparison:**
```xml
<person>
  <name>John</name>
  <age>30</age>
</person>
```

vs

```json
{"person": {"name": "John", "age": 30}}
```

XML: ~50 tokens | JSON: ~25 tokens

**Trade-off:** 2x tokens for better Claude performance and clarity.

---

**Related:**
- [JSON Format](json-format.md) - Alternative for APIs/multi-LLM
- [Decision Matrix](decision_matrix.md) - XML vs JSON selection

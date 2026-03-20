# Zero-Shot Prompting

## When to Use
- Task is well-defined and common in LLM training data
- No examples available
- Simple, direct instructions sufficient
- Token budget is limited

## Best Practices
✓ Clear, specific instructions
✓ Define expected output format explicitly
✓ Include all constraints and requirements
✓ Specify edge case handling

✗ Avoid ambiguous language
✗ Don't assume implicit knowledge
✗ Don't leave format unspecified

## Template (Natural Language)
```
Task: [Clear description of what needs to be done]

Requirements:
- [Requirement 1]
- [Requirement 2]
- [Requirement 3]

Output format: [Specify exact format]

Constraints:
- [Constraint 1]
- [Constraint 2]

Please [specific action verb] based on the above requirements.
```

## Template (XML - Claude)
```xml
<task>
  <description>
    [Clear task description]
  </description>
  
  <requirements>
    <requirement priority="high">[Requirement 1]</requirement>
    <requirement priority="medium">[Requirement 2]</requirement>
  </requirements>
  
  <constraints>
    <constraint>[Constraint 1]</constraint>
  </constraints>
  
  <output_format>
    [Desired output structure]
  </output_format>
</task>
```

## Examples

### ❌ Bad Zero-Shot
```
Translate this to French.
```
Problems: No context, no format specified, ambiguous

### ✅ Good Zero-Shot
```
Translate the following English text to French.
Maintain formal tone, preserve technical terms.
Output only the translation without explanations.

Text: [input text]
```

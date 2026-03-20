# ReAct (Reasoning + Acting) Prompting

## Overview

ReAct interleaves reasoning traces with action execution, allowing LLMs to combine internal reasoning with external tool use and information retrieval.

## Structure

```
Thought → Action → Observation → Thought → Action → ... → Answer
```

## When to Use

✅ **Use ReAct When:**
- Task requires external tool/API use
- Need to retrieve information dynamically
- Multi-step tasks with actions between reasoning
- Combining LLM reasoning with external systems

❌ **Don't Use ReAct When:**
- No external tools needed
- Pure reasoning task (use CoT instead)
- Simple lookup (use direct API call)

## Template

```
Available Actions:
- SEARCH[query]: Search for information
- LOOKUP[term]: Find specific term in context
- CALCULATE[expression]: Perform calculation
- FINISH[answer]: Provide final answer

Task: [Your task]

Thought 1: [Initial reasoning about what to do]
Action 1: [Specific action to take]
Observation 1: [Result of action - filled after executing]

Thought 2: [Reasoning based on observation]
Action 2: [Next action]
Observation 2: [Result]
...
Thought N: [Final reasoning]
Action N: FINISH[final answer]
```

## JSON Format (for tool calling)

```json
{
  "task": "[Task description]",
  "available_actions": ["SEARCH", "CALCULATE", "FINISH"],
  "trace": [
    {
      "step": 1,
      "thought": "[What I need to do]",
      "action": "SEARCH[query]",
      "observation": "[Search result]"
    },
    {
      "step": 2,
      "thought": "[Next reasoning]",
      "action": "CALCULATE[expression]",
      "observation": "[Calculation result]"
    }
  ]
}
```

## Example

```
Task: What is the population of the capital of the country with largest GDP?

Thought 1: I need to find which country has the largest GDP
Action 1: SEARCH[country with largest GDP 2025]
Observation 1: United States has the largest GDP

Thought 2: Now I need the capital of United States
Action 2: SEARCH[capital of United States]
Observation 2: Washington, D.C.

Thought 3: Now I need the population of Washington, D.C.
Action 3: SEARCH[Washington DC population 2025]
Observation 3: Approximately 750,000

Thought 4: I have all information needed
Action 4: FINISH[Population of Washington, D.C. is approximately 750,000]
```

## Best Practices

✓ Define available actions upfront
✓ Separate Thought/Action/Observation clearly
✓ Specify action formats precisely
✓ Include FINISH action as stopping criteria

✗ Don't mix reasoning and actions
✗ Avoid undefined/ambiguous actions
✗ Don't continue after FINISH

---

**Related:**
- [Chain of Thought](chain-of-thought.md) - Pure reasoning without tools
- [JSON Format](json-format.md) - Best format for tool calling

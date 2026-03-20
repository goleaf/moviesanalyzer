# Prompt Chaining

## Overview

Prompt chaining breaks complex tasks into sequential prompts, where each prompt's output feeds into the next. This creates a pipeline of focused, manageable steps.

## When to Use Prompt Chaining

✅ **Use Chaining When:**
- Task is too complex for single prompt
- Natural sequential steps exist
- Each step has different requirements
- Intermediate outputs need validation
- Different models/techniques optimal for different steps
- Need to inspect/modify intermediate results

❌ **Don't Use Chaining When:**
- Task is simple enough for single prompt
- Steps can't be cleanly separated
- Latency is critical (chaining is slower)
- No need to inspect intermediate results

## Basic Structure

```
User Input 
  → Prompt 1 → Output 1 
  → Prompt 2 (uses Output 1) → Output 2
  → Prompt 3 (uses Output 2) → Output 3
  → Final Result
```

## Template: 3-Step Chain (YAML)

```yaml
# Prompt Chain Configuration
chain:
  - step: 1
    name: "Extract Information"
    model: "gpt-4"
    temperature: 0.3
    prompt: |
      Extract key information from this document:
      {document}
      
      Extract:
      - Main topics
      - Key entities (people, organizations, dates)
      - Action items
    
    output_to: "extracted_info"
  
  - step: 2
    name: "Analyze Sentiment"
    model: "claude-3-sonnet"
    temperature: 0.5
    prompt: |
      Based on this extracted information:
      {extracted_info}
      
      Analyze the sentiment and tone of the original document.
      Provide:
      - Overall sentiment (positive/negative/neutral)
      - Emotional tone
      - Key sentiment drivers
    
    output_to: "sentiment_analysis"
  
  - step: 3
    name: "Generate Summary"
    model: "claude-3-opus"
    temperature: 0.7
    prompt: |
      Create a comprehensive summary using:
      
      Extracted information: {extracted_info}
      Sentiment analysis: {sentiment_analysis}
      
      Summary should be 200 words, highlighting:
      - Key points
      - Overall tone
      - Action items
    
    output_to: "final_summary"
```

## Common Chain Patterns

### Pattern 1: Extract → Transform → Generate

**Use case:** Document processing

```
Step 1: Extract data from unstructured text
  Input: Raw document
  Output: Structured data (JSON)
  
Step 2: Transform/clean data
  Input: Structured data
  Output: Cleaned, normalized data
  
Step 3: Generate report
  Input: Cleaned data
  Output: Human-readable report
```

**Example:**
```
1. Extract customer feedback → JSON with issues
2. Categorize issues → Grouped by category
3. Generate executive summary → Final report
```

---

### Pattern 2: Research → Analyze → Recommend

**Use case:** Decision support

```
Step 1: Research/gather information
  Tools: Web search, database queries
  Output: Relevant information
  
Step 2: Analyze information
  Method: Compare, evaluate, synthesize
  Output: Analysis with pros/cons
  
Step 3: Generate recommendations
  Input: Analysis
  Output: Actionable recommendations
```

**Example:**
```
1. Search for competitor features → Feature list
2. Compare with our product → Gap analysis
3. Recommend prioritized roadmap → Action plan
```

---

### Pattern 3: Generate → Validate → Refine

**Use case:** Content creation with quality control

```
Step 1: Generate initial content
  Temperature: 0.8 (creative)
  Output: Draft content
  
Step 2: Validate content
  Temperature: 0.2 (analytical)
  Check: Accuracy, tone, completeness
  Output: Validation report
  
Step 3: Refine based on validation
  Temperature: 0.5 (balanced)
  Input: Draft + validation
  Output: Final polished content
```

**Example:**
```
1. Generate blog post → Draft
2. Check facts and tone → Issues list
3. Revise and polish → Final post
```

---

### Pattern 4: Decompose → Solve → Combine

**Use case:** Complex problem solving

```
Step 1: Decompose problem
  Break into sub-problems
  Output: List of sub-problems
  
Step 2: Solve each sub-problem
  Parallel or sequential
  Output: Solutions for each
  
Step 3: Combine solutions
  Integrate and synthesize
  Output: Complete solution
```

**Example:**
```
1. Break system design into components → Component list
2. Design each component → Component designs
3. Integrate into full architecture → System design
```

---

## Advanced: Conditional Chaining

**Branching based on intermediate results:**

```yaml
chain:
  - step: 1
    name: "Classify Query"
    prompt: "Classify this user query: {query}"
    output_to: "classification"
  
  - step: 2
    condition: "{classification} == 'technical'"
    name: "Technical Response"
    prompt: "Provide technical answer for: {query}"
    output_to: "response"
  
  - step: 2_alt
    condition: "{classification} == 'general'"
    name: "General Response"
    prompt: "Provide general answer for: {query}"
    output_to: "response"
  
  - step: 3
    name: "Quality Check"
    prompt: "Review this response: {response}"
    output_to: "final_response"
```

---

## Advanced: Loop/Iteration

**Repeat steps until condition met:**

```yaml
chain:
  - step: 1
    name: "Generate Code"
    prompt: "Write function for: {requirement}"
    output_to: "code"
  
  - step: 2
    name: "Test Code"
    prompt: "Test this code with: {test_cases}"
    output_to: "test_results"
  
  - step: 3
    name: "Check If Passed"
    condition: "{test_results} != 'all passed'"
    action: "loop_to_step_1"
    max_iterations: 3
    
  - step: 4
    name: "Return Final Code"
    output: "{code}"
```

---

## Benefits of Chaining

### 1. Focused Prompts
Each prompt handles one clear task:
```
❌ Single complex prompt (1000 tokens, confusing)
✅ 3 focused prompts (300 tokens each, clear)
```

### 2. Different Models per Step
Optimize cost/quality:
```
Step 1 (Extract): GPT-3.5 (cheap, fast)
Step 2 (Analyze): GPT-4 (quality needed)
Step 3 (Format): GPT-3.5 (simple formatting)
```

### 3. Intermediate Validation
Catch errors early:
```
Extract → [Validate extraction] → Analyze → [Validate analysis] → Generate
```

### 4. Easier Debugging
Identify which step failed:
```
Step 1: ✓ Success
Step 2: ✗ Failed (fix this specific step)
Step 3: Not reached
```

---

## Implementation Patterns

### Pattern A: Manual Chaining (Simple)

```python
# Step 1
prompt_1 = f"Extract data from: {document}"
output_1 = llm.generate(prompt_1)

# Step 2
prompt_2 = f"Analyze this data: {output_1}"
output_2 = llm.generate(prompt_2)

# Step 3
prompt_3 = f"Summarize: {output_2}"
final_output = llm.generate(prompt_3)
```

### Pattern B: Pipeline Function

```python
def chain_prompts(steps, input_data):
    context = {"input": input_data}
    
    for step in steps:
        prompt = step["prompt"].format(**context)
        output = llm.generate(prompt, 
                             model=step.get("model"),
                             temperature=step.get("temperature"))
        context[step["output_to"]] = output
    
    return context["final_output"]
```

### Pattern C: LangChain (Framework)

```python
from langchain import PromptTemplate, LLMChain

# Define chain
chain = (
    LLMChain(prompt=extract_prompt) 
    | LLMChain(prompt=analyze_prompt)
    | LLMChain(prompt=summarize_prompt)
)

# Execute
result = chain.run(document=doc)
```

---

## Error Handling in Chains

### Strategy 1: Retry Failed Step

```python
def execute_step(step, context, max_retries=3):
    for attempt in range(max_retries):
        try:
            output = llm.generate(step["prompt"].format(**context))
            return output
        except Exception as e:
            if attempt == max_retries - 1:
                raise
            time.sleep(2 ** attempt)  # Exponential backoff
```

### Strategy 2: Fallback to Alternative

```python
def execute_with_fallback(primary_step, fallback_step, context):
    try:
        return execute_step(primary_step, context)
    except:
        return execute_step(fallback_step, context)
```

### Strategy 3: Skip Optional Steps

```python
for step in chain:
    if step.get("optional") and previous_failed:
        continue  # Skip optional steps after failure
    execute_step(step, context)
```

---

## Cost Optimization

### Use Cheaper Models for Simple Steps

```yaml
# Expensive: All steps use GPT-4
total_cost: $0.15

# Optimized: GPT-3.5 where possible
step_1: gpt-3.5-turbo  # Extract (simple)
step_2: gpt-4          # Analyze (complex)
step_3: gpt-3.5-turbo  # Format (simple)
total_cost: $0.08  # 47% savings
```

### Cache Intermediate Results

```python
cache = {}

def execute_with_cache(step, context):
    cache_key = hash(step["prompt"].format(**context))
    
    if cache_key in cache:
        return cache[cache_key]
    
    output = llm.generate(...)
    cache[cache_key] = output
    return output
```

---

## Real-World Example: Customer Support Chain

```yaml
customer_support_chain:
  - step: 1
    name: "Classify Intent"
    model: "gpt-3.5-turbo"
    temperature: 0.2
    prompt: |
      Classify customer message intent:
      {customer_message}
      
      Categories: technical_issue, billing, feature_request, general
    output_to: "intent"
  
  - step: 2
    name: "Extract Key Info"
    model: "gpt-3.5-turbo"
    temperature: 0.3
    prompt: |
      Extract from message:
      {customer_message}
      
      Extract: account_id, product, issue_description
    output_to: "extracted_info"
  
  - step: 3
    name: "Search Knowledge Base"
    action: "tool_call"
    tool: "vector_search"
    query: "{extracted_info.issue_description}"
    output_to: "relevant_articles"
  
  - step: 4
    name: "Generate Response"
    model: "claude-3-sonnet"
    temperature: 0.7
    prompt: |
      Generate customer support response:
      
      Intent: {intent}
      Customer info: {extracted_info}
      Relevant help: {relevant_articles}
      
      Tone: Empathetic and professional
      Length: 100-150 words
    output_to: "draft_response"
  
  - step: 5
    name: "Quality Check"
    model: "claude-3-opus"
    temperature: 0.2
    prompt: |
      Review this support response:
      {draft_response}
      
      Check:
      - Addresses customer issue
      - Professional tone
      - Includes next steps
      
      If issues: suggest improvements
      If good: approve with "APPROVED"
    output_to: "final_response"
```

---

## Quick Reference

| Aspect | Recommendation |
|--------|---------------|
| **Chain length** | 2-5 steps (optimal) |
| **Step complexity** | Each step = 1 clear task |
| **Model selection** | Match model to step complexity |
| **Error handling** | Retry + fallback strategies |
| **Cost optimization** | Use cheaper models where possible |
| **Best for** | Complex multi-step workflows |

---

**Related:**
- [ReAct](react.md) - Tool use within prompts
- [Advanced Combinations](advanced-combinations.md) - Combining techniques
- [Decision Matrix](decision_matrix.md) - When to use chaining

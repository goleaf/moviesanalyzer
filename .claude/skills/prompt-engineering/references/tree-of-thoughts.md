# Tree of Thoughts (ToT) Prompting

## Overview

Tree of Thoughts explores multiple reasoning paths in a tree structure, evaluating alternatives before selecting the best solution. Useful for complex planning and problems requiring exploration.

## When to Use

✅ **Use ToT When:**
- Complex planning requiring exploration of alternatives
- Multiple solution paths need evaluation
- Strategic games, puzzles, or optimization problems
- Creative tasks benefiting from exploring options
- Need to backtrack if a path fails
- Problem has no single obvious solution

❌ **Don't Use ToT When:**
- Simple, straightforward tasks
- Single clear solution path
- Token budget is tight (ToT is expensive)
- Time is critical (ToT is slower)
- Problem is well-defined with known approach

## Structure

```
Initial State 
  → Generate Multiple Thoughts (Level 1)
  → Evaluate Each Thought
  → Select Best Thought(s)
  → Generate Sub-Thoughts (Level 2)
  → Evaluate
  → Select Best
  → ... Continue
  → Final Solution
```

## Best Practices

✓ Generate 3-5 alternative thoughts per node
✓ Define clear evaluation criteria upfront
✓ Allow backtracking to previous states
✓ Prune obviously bad paths early
✓ Set depth limit (typically 3-4 levels)

✗ Don't explore infinitely (set limits)
✗ Avoid bias toward first generated option
✗ Don't use identical evaluation for all options
✗ Don't forget to compare alternatives

## Template: Natural Language

```
Problem: [Complex problem requiring exploration]

Approach: I will explore multiple solution paths using Tree of Thoughts

Initial State: [Starting condition]

Level 1 - Generate Possible Approaches:
Option A: [First approach]
Option B: [Second approach]
Option C: [Third approach]

Evaluate Level 1:
- Option A: [Score/reasoning]
  Pros: [advantages]
  Cons: [disadvantages]
  
- Option B: [Score/reasoning]
  Pros: [advantages]
  Cons: [disadvantages]
  
- Option C: [Score/reasoning]
  Pros: [advantages]
  Cons: [disadvantages]

Select Best: [Option X] because [reasoning]

Level 2 - Expand Option X:
Path X.1: [Sub-approach 1]
Path X.2: [Sub-approach 2]
Path X.3: [Sub-approach 3]

Evaluate Level 2:
[Score each path...]

Final Solution: [Best path found through tree]
```

## Template: YAML Format (Human-Readable)

```yaml
problem: "[Problem statement]"

tree_of_thoughts:
  root:
    state: "[Initial state]"
    
  level_1:
    thoughts:
      - id: "A"
        description: "[First approach]"
        evaluation:
          score: 0.8
          reasoning: "[Why this might work]"
          pros: ["Pro 1", "Pro 2"]
          cons: ["Con 1"]
          
      - id: "B"
        description: "[Second approach]"
        evaluation:
          score: 0.6
          reasoning: "[Why this might work]"
          pros: ["Pro 1"]
          cons: ["Con 1", "Con 2"]
          
      - id: "C"
        description: "[Third approach]"
        evaluation:
          score: 0.9
          reasoning: "[Why this might work]"
          pros: ["Pro 1", "Pro 2", "Pro 3"]
          cons: []
          
    selected: "C"
    reason: "[Why C is best]"
    
  level_2:
    parent: "C"
    thoughts:
      - id: "C.1"
        description: "[Refined approach]"
        evaluation:
          score: 0.85
          
      - id: "C.2"
        description: "[Alternative refinement]"
        evaluation:
          score: 0.95
          
    selected: "C.2"
    
solution:
  path: ["C", "C.2"]
  final_answer: "[Detailed solution]"
  rationale: "[Why this path is optimal]"
```

## Template: XML Format (Claude-Optimized)

```xml
<tree_of_thoughts>
  <problem>[Complex planning problem]</problem>
  
  <exploration_config>
    <max_depth>3</max_depth>
    <branches_per_level>3</branches_per_level>
    <evaluation_criteria>
      <criterion weight="0.4">Feasibility</criterion>
      <criterion weight="0.3">Cost</criterion>
      <criterion weight="0.3">Time</criterion>
    </evaluation_criteria>
  </exploration_config>
  
  <level_1>
    <thought id="A">
      <description>[Approach A]</description>
      <evaluation>
        <feasibility>0.9</feasibility>
        <cost>0.6</cost>
        <time>0.7</time>
        <total_score>0.75</total_score>
      </evaluation>
    </thought>
    
    <thought id="B">
      <description>[Approach B]</description>
      <evaluation>
        <feasibility>0.7</feasibility>
        <cost>0.8</cost>
        <time>0.6</time>
        <total_score>0.70</total_score>
      </evaluation>
    </thought>
    
    <selected>A</selected>
    <reason>[Why A is selected]</reason>
  </level_1>
  
  <level_2>
    <parent>A</parent>
    <!-- Continue expansion -->
  </level_2>
  
  <final_solution>
    <path>A → A.2 → A.2.1</path>
    <answer>[Solution details]</answer>
  </final_solution>
</tree_of_thoughts>
```

## Real-World Example: Trip Planning

```
Problem: Plan a 7-day trip to Japan with $3000 budget, 
focusing on cultural sites and food experiences.

Tree of Thoughts Approach:

Level 1 - City Combinations:
Option A: Tokyo only (7 days)
  Score: 7/10
  Pros: Deep dive, no travel time between cities
  Cons: Less variety, misses Kyoto temples
  Budget: Under budget (~$2500)
  
Option B: Tokyo (4 days) + Kyoto (3 days)
  Score: 9/10
  Pros: Great culture balance, iconic sites
  Cons: 1 travel day lost
  Budget: Fits budget (~$2900)
  
Option C: Tokyo (3) + Osaka (2) + Kyoto (2)
  Score: 6/10
  Pros: Maximum variety
  Cons: Too rushed, over budget, constant packing
  Budget: Over budget (~$3200)
  
Selected: Option B (Tokyo 4 + Kyoto 3)

Level 2 - Tokyo Allocation (4 days):
B.1: Shibuya/Shinjuku (1) + Asakusa/temples (1) + 
     Tokyo Disney (1) + Akihabara (1)
  Score: 7/10
  
B.2: Shibuya/Shinjuku (1) + Asakusa (1) + 
     Tsukiji/food tour (1) + Museums (1)
  Score: 9/10
  Aligns better with "cultural + food" focus
  
B.3: Mix modern + traditional split evenly
  Score: 8/10
  
Selected: B.2

Level 3 - Kyoto Allocation (3 days):
B.2.1: Temples (2 days) + Arashiyama (1 day)
B.2.2: Temples (1.5) + Gion (1) + Food tour (0.5)
  Score: 9/10 - Better balance
  
Final Solution:
Path: B → B.2 → B.2.2
Days 1-4: Tokyo (Shibuya, Asakusa, Tsukiji food, Museums)
Days 5-7: Kyoto (Temples 1.5 days, Gion district, Food tour)
Budget: $2,850 (within limit)
```

## Evaluation Criteria Examples

### For Planning Tasks:
- **Feasibility:** Can it actually be done?
- **Cost:** Financial constraints
- **Time:** Time requirements
- **Risk:** What could go wrong?
- **Impact:** Expected benefit

### For Technical Solutions:
- **Performance:** Speed/efficiency
- **Maintainability:** Long-term code quality
- **Scalability:** Growth potential
- **Security:** Vulnerability assessment
- **Complexity:** Implementation difficulty

### For Creative Tasks:
- **Originality:** Uniqueness of idea
- **Coherence:** Internal consistency
- **Engagement:** Audience appeal
- **Feasibility:** Can it be executed?
- **Impact:** Desired effect achieved

## Pruning Strategies

### Early Pruning:
```
If thought scores < 0.3 on any critical criterion:
  → Prune immediately, don't explore further
  
If multiple thoughts score > 0.8:
  → Explore top 2-3 only (save tokens)
```

### Mid-Pruning:
```
After Level 2:
  → Review paths from root to current leaves
  → Prune paths with cumulative score < threshold
  → Focus resources on promising branches
```

## Backtracking Example

```
Level 1: Selected Option A (score 0.9)

Level 2: Expanded A
  A.1: Score 0.6
  A.2: Score 0.5
  A.3: Score 0.4
  
All sub-options score poorly!

BACKTRACK to Level 1
Select Option B (second-best, score 0.8)

Level 2: Expanded B
  B.1: Score 0.9 ✓
  B.2: Score 0.85 ✓
  
Continue with B.1 path
```

## Comparing ToT to Other Methods

| Aspect | Zero-Shot | Few-Shot | CoT | ToT |
|--------|-----------|----------|-----|-----|
| **Paths explored** | 1 | 1 | 1 | Multiple |
| **Backtracking** | No | No | No | Yes |
| **Evaluation** | No | Implicit | No | Explicit |
| **Token cost** | Low | Medium | Medium | High |
| **Best for** | Simple | Format | Reasoning | Planning |

## Token Cost

ToT is expensive due to exploring multiple paths:

**Typical costs:**
- Level 1 (3 options): ~200 tokens
- Level 2 (3 options per branch): ~400 tokens
- Level 3: ~600 tokens
- Evaluation overhead: ~300 tokens
- **Total: 500-2000+ tokens**

**Optimization:**
- Limit breadth (3 options per level, not 5)
- Limit depth (2-3 levels, not 5)
- Prune early and aggressively
- Use compact format (not verbose)

## When ToT is Overkill

❌ **Don't use ToT for:**
```
- "What is 2+2?" (use Zero-Shot)
- "Translate this to Spanish" (use Zero-Shot)
- "Extract email from text" (use Few-Shot)
- "Explain recursion" (use CoT)
```

✅ **Use ToT for:**
```
- "Design a scalable architecture for..."
- "Plan a marketing campaign with..."
- "Optimize supply chain considering..."
- "Choose technology stack for..."
```

## Quick Reference

| Aspect | Recommendation |
|--------|---------------|
| **Levels** | 2-3 (rarely >4) |
| **Branches per level** | 3-5 options |
| **Evaluation criteria** | 3-5 weighted factors |
| **Pruning** | Aggressive (score <0.4) |
| **Format** | YAML (readable) or XML (Claude) |
| **Token cost** | 500-2000+ tokens |
| **Best for** | Planning, strategy, optimization |

---

**Related:**
- [Chain of Thought](chain-of-thought.md) - Single path reasoning
- [Self-Consistency](self-consistency.md) - Multiple paths, same method
- [YAML Format](yaml-format.md) - Human-readable format for ToT
- [Decision Matrix](decision_matrix.md) - When to use ToT

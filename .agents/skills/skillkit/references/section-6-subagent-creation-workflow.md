# Section 6: Subagent Creation Workflow

**Prerequisites:** Decision workflow recommended Subagent, workspace available
**Quality Target:** Clear role definition, comprehensive workflow, testable examples
**Time:** <15 min with template

---

## Overview

This workflow creates specialized AI subagents with defined roles, workflows, and capabilities. Subagents are ideal for complex multi-step tasks requiring isolated context and specialized expertise.

**Key Differences from Skills:**
- Subagents have isolated context windows (not shared like Skills)
- Subagents use Task tool for invocation
- Subagents define tool permissions explicitly
- Subagents have specialized system prompts

---

## STEP 0: Requirements & Role Definition

**Purpose:** Define what this subagent does and when to use it

### 0a. Core Questions

Answer these before creating:

1. **Primary Role:** What expertise does this subagent provide?
2. **Trigger Conditions:** When should this subagent be invoked?
3. **Tool Requirements:** What tools does it need access to?
4. **Workflow Complexity:** How many phases/steps in typical workflow?
5. **Output Format:** What structure should responses follow?

### 0b. Subagent Type Selection

Choose the base type that matches your needs:

| Type | Best For | Tools Typically Needed |
|------|----------|----------------------|
| `general-purpose` | Research, analysis, problem-solving | Read, Grep, Glob, Bash |
| `code-reviewer` | Code review, quality checks | Read, Grep, Glob |
| `typescript-pro` | TypeScript development | Read, Write, Edit, Bash |
| `flutter-expert` | Flutter development | Read, Write, Edit, Bash |
| `red-team` | Security testing | Read, Grep, Glob, Bash |
| `qa-expert` | Quality assurance | Read, Grep, Glob |
| `seo-manager` | Social media optimization | Read, Write, Edit |
| `creative-copywriter` | Content creation | Read, Write, Edit |
| `decision-maker` | Strategic decisions | Read, Grep, Glob |
| `research` | Deep research | Read, Grep, Glob, Bash |
| `custom` | Specialized needs | Define as needed |

**Gate:** Must have clear role definition before proceeding

---

## STEP 1: Initialize Subagent File

**Tool:** `python scripts/init_subagent.py <name> --path ~/.claude/agents`

```bash
# Example
python scripts/init_subagent.py code-reviewer --path ~/.claude/agents
```

**Output:**
```
~/.claude/agents/
└── code-reviewer.md    # Template with TODOs
```

**Gate:** Verify file created at `~/.claude/agents/<name>.md`

**Important:** Subagents are individual `.md` files (not directories with SUBAGENT.md).

---

## STEP 2: Define Subagent Configuration

**Purpose:** Configure YAML frontmatter with metadata and permissions

### 2a. Edit YAML Frontmatter

Open `~/.claude/agents/<name>.md` and configure:

```yaml
---
name: subagent-name
description: |
  Clear, specific description of what this subagent does.
  Include WHEN to use it - specific scenarios that trigger delegation.

subagent_type: [choose from list above]

tools:
  - Read      # Almost always needed
  - Write     # If creating files
  - Edit      # If modifying files
  - Bash      # If running commands
  - Glob      # If file pattern matching
  - Grep      # If searching content
  - Skill     # If invoking other skills
  # - Task    # If spawning nested subagents

# Optional: Skills this subagent can invoke
skills:
  - skill-name  # When to use this skill

# Optional: Model preference
# model: sonnet  # sonnet, opus, or haiku
---
```

### 2b. Tool Permission Guidelines

**Minimal (Read-only):**
```yaml
tools:
  - Read
  - Grep
  - Glob
```
*Use for:* Code reviewers, auditors, analyzers

**Standard (Read + Write):**
```yaml
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
```
*Use for:* Developers, content creators

**Full (Including nested subagents):**
```yaml
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
  - Skill
  - Task
```
*Use for:* Orchestrators, complex workflows

**Gate:** YAML must be valid and tools list non-empty

---

## STEP 3: Define Role and Workflow

**Purpose:** Create comprehensive role definition and workflow

### 3a. Role Definition Section

```markdown
## Role Definition

**Primary Role:** [Specific expertise, e.g., "Security-focused code reviewer"]

**Specialization:** [Domain focus, e.g., "Web application vulnerabilities"]

**Working Style:** [How it approaches tasks, e.g., "Methodical, thorough, prioritizes security"]
```

### 3b. Trigger Conditions Section

```markdown
## When to Use This Subagent

### Trigger Conditions

Invoke this subagent when:

1. **[Specific condition]** - [Explanation]
2. **[Specific condition]** - [Explanation]
3. **[Specific condition]** - [Explanation]

### Task Characteristics

**Ideal for:**
- [Task type 1]
- [Task type 2]

**Not suitable for:**
- [What to avoid]
```

### 3c. Workflow Section

Define clear phases:

```markdown
## Workflow

### Phase 1: [Name]

1. [Step one]
2. [Step two]
3. [Step three]

### Phase 2: [Name]

1. [Step one]
2. [Step two]

### Phase 3: [Name]

1. [Final steps]
2. [Return results]
```

**Gate:** Must have at least 2 workflow phases defined

---

## STEP 4: Define Response Format

**Purpose:** Ensure consistent, useful output

### 4a. Structure Template

```markdown
## Response Format

### Structure

```markdown
## Summary
[Brief overview]

## Details
[Detailed analysis]

## Recommendations
[Actionable steps]

## References
[Files/docs created or modified]
```

### Tone and Style

- **[Characteristic 1]**
- **[Characteristic 2]**
- **[Characteristic 3]**
```

### 4b. Error Handling Section

```markdown
## Error Handling

### Common Issues

| Issue | Detection | Resolution |
|-------|-----------|------------|
| [Issue 1] | [How to detect] | [How to resolve] |
| [Issue 2] | [How to detect] | [How to resolve] |

### Escalation Criteria

Escalate to parent/main Claude when:
- [Criteria 1]
- [Criteria 2]
```

---

## STEP 5: Add Examples

**Purpose:** Provide concrete usage examples

### Example Structure

```markdown
## Examples

### Example 1: [Scenario Name]

**Input:**
```
[Example user request]
```

**Process:**
```
[How subagent handles it]
```

**Output:**
```
[Example response]
```
```

**Gate:** Must have at least 1 complete example

---

## STEP 6: Validation

**Purpose:** Ensure subagent is complete and usable

### Validation Checklist

- [ ] YAML frontmatter is valid
- [ ] Name is descriptive and hyphenated
- [ ] Description is clear and specific
- [ ] Subagent type is appropriate
- [ ] Tool permissions are minimal but sufficient
- [ ] Role definition is clear
- [ ] Trigger conditions are specific
- [ ] Workflow has multiple phases
- [ ] Response format is defined
- [ ] At least one example is complete
- [ ] Error handling is documented

### Quick Validation Command

```bash
# Check YAML validity
python3 -c "import yaml; yaml.safe_load(open('~/.claude/agents/name.md'))"

# Check file structure
grep -c "^## " ~/.claude/agents/name.md  # Should have 5+ sections
```

---

## STEP 7: Testing

**Purpose:** Verify subagent works as intended

### 7a. Test Invocation

Test with Task tool:

```python
# Example test
Task(
    description="Test subagent",
    prompt="""Test the [subagent-name] subagent with this request:

[Specific test case]

Verify it:
1. Follows the defined workflow
2. Uses correct tone/style
3. Returns properly formatted output
4. Handles the task appropriately
""",
    subagent_type="[subagent-name]"
)
```

### 7b. Iterate Based on Results

If issues found:
1. Update SUBAGENT.md
2. Re-test
3. Document any behavior adjustments

---

## STEP 8: Documentation & Deployment

**Purpose:** Prepare for use

### 8a. Create Usage Documentation

Create `README.md` in subagent directory:

```markdown
# [Subagent Name]

## Quick Start

```python
Task(
    description="[Brief description]",
    prompt="""[Your request here]""",
    subagent_type="[subagent-name]"
)
```

## When to Use

- [Use case 1]
- [Use case 2]

## Examples

See SUBAGENT.md for detailed examples.
```

### 8b. Register in System

Add to appropriate location:
- If shared: Add to team subagents registry
- If personal: Document in personal notes
- If project-specific: Add to project docs

---

## Quality Checklist

Before considering complete:

- [ ] **Configuration:** YAML valid, tools appropriate
- [ ] **Role:** Clear expertise and specialization
- [ ] **Triggers:** Specific invocation conditions
- [ ] **Workflow:** Multi-phase, logical flow
- [ ] **Output:** Consistent, useful format
- [ ] **Examples:** Concrete, realistic
- [ ] **Errors:** Handled gracefully
- [ ] **Tested:** Works as intended

---

## Common Patterns

### Pattern 1: Code Review Subagent

```yaml
subagent_type: code-reviewer
tools:
  - Read
  - Grep
  - Glob
```

Focus: Security, performance, best practices

### Pattern 2: Research Subagent

```yaml
subagent_type: research
tools:
  - Read
  - Grep
  - Glob
  - Bash
  - Skill
```

Focus: Multi-source analysis, synthesis

### Pattern 3: Developer Subagent

```yaml
subagent_type: typescript-pro
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
```

Focus: Implementation, refactoring

---

## Integration with Skills

Subagents can invoke Skills for utilities:

```markdown
## Using with Skills

This subagent can invoke these skills:
- `skill-name` - When to use it
- `another-skill` - When to use it
```

See `knowledge/foundation/04-hybrid-patterns.md` for details.

---

## Next Steps

After creating subagent:

1. **Test thoroughly** with various inputs
2. **Document usage** patterns discovered
3. **Iterate** based on real usage
4. **Consider extracting** common utilities to Skills
5. **Share** with team if useful

---

**End of Section 6**

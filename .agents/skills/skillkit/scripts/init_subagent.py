#!/usr/bin/env python3
"""
Subagent Initializer - Creates a new subagent file from template

Usage:
    init_subagent.py <subagent-name> --path <path>

Examples:
    init_subagent.py code-reviewer --path ~/.claude/agents
    init_subagent.py security-auditor --path ~/.claude/agents

Note: Subagents are individual .md files in ~/.claude/agents/
"""

import argparse
import re
import sys
from pathlib import Path


SUBAGENT_TEMPLATE = """---
name: {subagent_name}
description: "[TODO: Clear description of what this subagent does and when to use it. Include specific scenarios that trigger delegation. See code-simplifier.md for example format with <example> tags.]"
subagent_type: [TODO: Choose one]
# Options: general-purpose, code-reviewer, typescript-pro, flutter-expert,
#          red-team, qa-expert, seo-manager, creative-copywriter,
#          decision-maker, research, custom

tools:
  - Read      # Always recommended
  - Write     # Enable if subagent needs to create files
  - Edit      # Enable if subagent needs to modify files
  - Bash      # Enable if subagent needs command execution
  - Glob      # Enable for file pattern matching
  - Grep      # Enable for content search
  - Skill     # Enable to invoke other skills
  # - Task    # Enable if spawning nested subagents

# Optional: Skills this subagent can invoke
# skills:
#   - skill-name  # Description of when to use this skill

# Optional: Model preference (defaults to parent's model)
# model: sonnet  # Options: sonnet, opus, haiku
---

You are a specialist in [TODO: domain expertise]. Your purpose is to [TODO: main purpose].

## Your Capabilities

**Core Expertise:**
- [TODO: Capability 1]
- [TODO: Capability 2]
- [TODO: Capability 3]

**When to Invoke You:**
- [TODO: Trigger condition 1]
- [TODO: Trigger condition 2]
- [TODO: Trigger condition 3]

## Your Approach

1. **Analysis Phase:**
   - [TODO: Step 1]
   - [TODO: Step 2]

2. **Implementation Phase:**
   - [TODO: Step 3]
   - [TODO: Step 4]

3. **Review Phase:**
   - [TODO: Step 5]
   - [TODO: Step 6]

## Guidelines

- [TODO: Guideline 1]
- [TODO: Guideline 2]
- [TODO: Guideline 3]

## Output Format

Always structure your response as:

```
## Summary
[Brief overview of what was done]

## Details
[Detailed explanation/implementation]

## Recommendations
[Actionable next steps or improvements]
```

---

**IMPORTANT:** This is a template with [TODO] placeholders. Before using this subagent, you MUST:
1. Research actual best practices for this domain (don't use generic personas)
2. Replace all [TODO] sections with specific, research-based content
3. Add concrete examples with <example> tags in description
4. Test the subagent with sample tasks
"""


def validate_subagent_name(name: str) -> tuple[bool, str]:
    """Validate subagent name format.

    Returns:
        Tuple of (is_valid, error_message)
    """
    if len(name) > 40:
        return False, "Name must be 40 characters or less"

    if not re.match(r'^[a-z0-9-]+$', name):
        return False, "Name must contain only lowercase letters, digits, and hyphens"

    if name.startswith('-') or name.endswith('-'):
        return False, "Name cannot start or end with a hyphen"

    if '--' in name:
        return False, "Name cannot contain consecutive hyphens"

    return True, ""


def init_subagent(subagent_name: str, path: str) -> Path | None:
    """Initialize a new subagent file with template content.

    Args:
        subagent_name: Name of the subagent (hyphen-case)
        path: Directory path where the subagent file should be created

    Returns:
        Path to created subagent file, or None if error occurred
    """
    # Validate inputs
    is_valid, error_msg = validate_subagent_name(subagent_name)
    if not is_valid:
        print(f"❌ Error: Invalid subagent name '{subagent_name}': {error_msg}")
        return None

    target_dir = Path(path).expanduser().resolve()
    subagent_file = target_dir / f"{subagent_name}.md"

    # Check if file already exists
    if subagent_file.exists():
        print(f"❌ Error: Subagent file already exists: {subagent_file}")
        return None

    # Ensure target is a directory
    if target_dir.exists() and not target_dir.is_dir():
        print(f"❌ Error: Target path exists but is not a directory: {target_dir}")
        return None

    # Create parent directory
    try:
        target_dir.mkdir(parents=True, exist_ok=True)
    except PermissionError:
        print(f"❌ Error: Permission denied creating directory: {target_dir}")
        return None
    except OSError as e:
        print(f"❌ Error creating directory: {e}")
        return None

    # Create subagent file
    try:
        subagent_file.write_text(SUBAGENT_TEMPLATE.format(subagent_name=subagent_name))
        print(f"✅ Created subagent file: {subagent_file}")
    except PermissionError:
        print(f"❌ Error: Permission denied writing file: {subagent_file}")
        return None
    except OSError as e:
        print(f"❌ Error creating subagent file: {e}")
        return None

    return subagent_file


def print_next_steps(subagent_name: str, subagent_file: Path) -> None:
    """Print follow-up instructions after successful creation."""
    print(f"\n✅ Subagent '{subagent_name}' initialized successfully")
    print("\n⚠️  IMPORTANT: This is a template with [TODO] placeholders.")
    print("   Before using this subagent, you MUST:")
    print("   1. Research actual best practices for this domain (don't use generic personas)")
    print("   2. Replace all [TODO] sections with specific, research-based content")
    print("   3. Add concrete examples with <example> tags in description")
    print("   4. Test the subagent with sample tasks")
    print(f"\n   Edit the file: {subagent_file}")


def main() -> int:
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description='Create a new subagent file from template',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  init_subagent.py code-reviewer --path ~/.claude/agents
  init_subagent.py security-auditor --path ~/.claude/agents
  init_subagent.py data-analyzer --path ~/.claude/agents

Subagent name requirements:
  - Hyphen-case identifier (e.g., 'code-reviewer')
  - Lowercase letters, digits, and hyphens only
  - Max 40 characters
  - Will create: <name>.md file

Note: Subagents are stored as individual .md files in ~/.claude/agents/
        """
    )
    parser.add_argument('subagent_name', help='Name of the subagent to create')
    parser.add_argument('--path', required=True,
                       help='Directory path where the subagent file should be created')

    args = parser.parse_args()

    print(f"🚀 Initializing subagent: {args.subagent_name}")
    print(f"   Location: {args.path}\n")

    result = init_subagent(args.subagent_name, args.path)

    if result:
        print_next_steps(args.subagent_name, result)
        return 0
    else:
        return 1


if __name__ == "__main__":
    sys.exit(main())

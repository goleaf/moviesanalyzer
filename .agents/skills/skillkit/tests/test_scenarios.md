# Test Scenarios: skillkit

**Generated:** Auto-generated from SKILL.md
**Coverage:** standard


## P0 Tests (11)

### Test basic skill invocation
- **Category:** functional
- **Expected:** Skill loads and responds to trigger
- **Test Data:** minimal_valid

### Test creating and validating skills and subagents with
- **Category:** functional
- **Expected:** Skill successfully handles: creating and validating skills and subagents with
- **Test Data:** valid_input

### Test consistent high-quality outputs with
- **Category:** functional
- **Expected:** Skill successfully handles: consistent high-quality outputs with
- **Test Data:** valid_input

### Test multiple routes match, or intent is ambiguous, agent MUST stop and ask user to choose one route.
- **Category:** functional
- **Expected:** Skill successfully handles: multiple routes match, or intent is ambiguous, agent MUST stop and ask user to choose one route.
- **Test Data:** valid_input

### Test workflow mode before running the creation flow.
- **Category:** functional
- **Expected:** Skill successfully handles: workflow mode before running the creation flow.
- **Test Data:** valid_input

### Test mode is not explicitly provided by user, agent MUST stop and ask:
- **Category:** functional
- **Expected:** Skill successfully handles: mode is not explicitly provided by user, agent MUST stop and ask:
- **Test Data:** valid_input

### Test mode is not explicitly known.
- **Category:** functional
- **Expected:** Skill successfully handles: mode is not explicitly known.
- **Test Data:** valid_input

### Test `.skillkit-mode` contains `fast` or marker does not exist.
- **Category:** functional
- **Expected:** Skill successfully handles: `.skillkit-mode` contains `fast` or marker does not exist.
- **Test Data:** valid_input

### Test `.skillkit-mode` contains `full`.
- **Category:** functional
- **Expected:** Skill successfully handles: `.skillkit-mode` contains `full`.
- **Test Data:** valid_input

### Test each step listed in that file, then follow them in order.**
- **Category:** functional
- **Expected:** Skill successfully handles: each step listed in that file, then follow them in order.**
- **Test Data:** valid_input

### Test still unknown: stop and ask user to choose `fast` or `full`
- **Category:** functional
- **Expected:** Skill successfully handles: still unknown: stop and ask user to choose `fast` or `full`
- **Test Data:** valid_input


## P1 Tests (2)

### Test with minimal input
- **Category:** edge_case
- **Expected:** Graceful handling of minimal valid input
- **Test Data:** minimal

### Test with maximum/complex input
- **Category:** edge_case
- **Expected:** Proper handling of complex scenarios
- **Test Data:** complex


## Setup

1. Install dependencies: `pip install pytest` (or unittest)
2. Review test scenarios above
3. Implement test logic in test files
4. Run tests: `pytest tests/`

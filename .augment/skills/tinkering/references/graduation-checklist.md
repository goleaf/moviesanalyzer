# Graduation Checklist

How to safely move a successful experiment into production code.

## Pre-Graduation

Before touching production code, confirm:

- [ ] Experiment clearly answered the hypothesis question
- [ ] Success criteria from HYPOTHESIS.md are met
- [ ] You understand WHY the experiment worked (not just that it did)
- [ ] Edge cases discovered during experimentation are documented

## Graduation Steps

### 1. Plan the Integration

Don't just copy-paste. The experiment was quick and dirty - production needs to be clean.

```
Map out:
- Which production files need changes?
- What's the minimal changeset to achieve the result?
- Are there dependencies to add/update?
- Do existing tests need updating?
```

### 2. Implement Incrementally

Apply changes to production code in small, reviewable steps:

```
Bad:  Copy entire _experiments/websocket-poc/ into src/
Good: Implement websocket module in src/ informed by experiment learnings
```

**Key differences from experiment code:**
- Proper error handling (experiment probably skipped this)
- Type safety and validation (experiment used `any` everywhere)
- Code style compliance (experiment ignored linting)
- Edge case handling (experiment only covered happy path)
- Documentation (experiment had none or minimal)

### 3. Write Tests

The experiment proved the concept works. Now prove it with proper tests:

- Unit tests for new functions/modules
- Integration tests for changed interactions
- Edge case tests for issues discovered during experimentation
- Regression tests if modifying existing behavior

### 4. Validate

```bash
# Run full test suite
npm test  # or pytest, cargo test, etc.

# Run linter
npm run lint

# Build check
npm run build

# Manual smoke test of the feature
```

### 5. Commit with Context

Reference the experiment in your commit for traceability:

```
feat: replace moment.js with date-fns for 34% bundle reduction

Spike in _experiments/date-fns-migration/ confirmed date-fns handles
all our formatting cases with 3x smaller footprint. Timezone edge case
in formatRelative() resolved by explicit timezone config.
```

### 6. Clean Up Experiment

After the production implementation is merged:

```bash
# Remove experiment directory
rm -rf _experiments/{experiment-name}/

# Update MANIFEST.md
# Move entry to "Completed" with reference to commit/PR
```

## Common Graduation Mistakes

| Mistake | Why It's Bad | Do This Instead |
|---------|-------------|-----------------|
| Copy-paste experiment code | Brings technical debt into production | Re-implement with proper standards |
| Skip tests because "it worked in experiment" | Experiment tested manually, production needs automated tests | Write proper test suite |
| Graduate partial experiment | Incomplete features cause confusion | Finish the experiment or graduate only the complete parts |
| Forget to clean up sandbox | Stale experiments accumulate, mislead future developers | Delete after graduation |
| No commit reference to experiment | Context lost, future devs don't know why decision was made | Reference experiment in commit message |

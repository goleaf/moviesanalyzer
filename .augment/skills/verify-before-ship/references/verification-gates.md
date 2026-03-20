# Verification Gates — Full Specifications

Complete reference for each of the 7 production safety gates.
Each gate specifies: what to check, how to collect evidence, and what constitutes a pass.

## Table of Contents

- [G1: Tests Pass](#g1-tests-pass)
- [G2: Security Scan Clean](#g2-security-scan-clean)
- [G3: No Breaking Changes](#g3-no-breaking-changes)
- [G4: Environment Config Validated](#g4-environment-config-validated)
- [G5: Staging Deployment Verified](#g5-staging-deployment-verified)
- [G6: Rollback Plan Documented](#g6-rollback-plan-documented)
- [G7: Code Review Approved](#g7-code-review-approved)

---

## G1: Tests Pass

**What to check:**
- All unit tests pass
- All integration tests pass
- Any e2e tests covering the changed functionality pass
- No test is skipped or marked xfail without a pre-existing, documented reason

**How to collect evidence:**
```bash
# CI output (preferred)
# Navigate to CI run → copy the final test summary

# Local run
npm test          # Node/JS
pytest -v         # Python
go test ./...     # Go
./gradlew test    # Java/Kotlin
bundle exec rspec # Ruby
```

**Pass criteria:**
- All tests show PASS/GREEN
- Zero unexpected failures
- Zero unexplained skips

**Fail criteria (DO NOT SHIP if any are true):**
- Any test failing
- CI run not triggered (no output = no evidence)
- "Tests will pass, I'm sure" without running them

**Evidence template:**
```
GATE G1 Tests: CLEARED
Evidence:
  [paste CI summary or local output here]
  Example: ✓ 247 passed, 0 failed — CI run #4521
```

---

## G2: Security Scan Clean

**What to check:**
- No critical or high severity CVEs in dependencies
- No hardcoded secrets, tokens, or credentials in diff
- No new SQL injection / XSS / command injection patterns

**How to collect evidence:**
```bash
# Dependency vulnerability scan
npm audit --audit-level=high        # Node
safety check                        # Python
snyk test                           # Any (Snyk CLI)
trivy fs .                          # Any (Trivy)

# Secret detection
git diff HEAD~1 | grep -i "password\|secret\|token\|api_key"
trufflehog git --since-commit HEAD~1 .
```

**Pass criteria:**
- Zero critical/high CVEs
- No secrets detected in diff
- Medium CVEs reviewed and accepted or mitigated

**Fail criteria (DO NOT SHIP if any are true):**
- Any critical or high CVE present
- Unreviewed secrets detected in diff
- Scan was not run ("it's fine, no dependencies changed")

**Evidence template:**
```
GATE G2 Security: CLEARED
Evidence:
  Dependency scan: 0 critical, 0 high (2 low — accepted)
  Secret scan: clean
  Tool: npm audit — 2026-03-13
```

---

## G3: No Breaking Changes

**What to check:**
- API contracts not broken (no removed endpoints, no changed required fields)
- Database schema migrations are backwards compatible or coordinated
- No removed/renamed env vars that consumers depend on
- Public interfaces (exported functions, types) not broken

**How to collect evidence:**
```bash
# API diff
git diff HEAD~1 -- "**/*.openapi.*" "**/*swagger*" "**/*schema*"

# Database migration review
cat migrations/latest.sql  # Confirm no DROP TABLE, no NOT NULL without default

# Contract tests (if present)
npm run test:contract
pact verify
```

**Pass criteria:**
- No uncoordinated breaking changes
- Any breaking changes have a migration plan and all consumers updated

**Fail criteria (DO NOT SHIP if any are true):**
- Removed API endpoints with active consumers
- Additing NOT NULL column without default to existing table
- Removed env var that dependent services use

**Evidence template:**
```
GATE G3 Breaking Changes: CLEARED
Evidence:
  API diff: no breaking changes in openapi.yaml
  DB migration: adds nullable column only
  Contract tests: 12/12 passing
```

---

## G4: Environment Config Validated

**What to check:**
- All required env vars for production are set
- Config diff between staging and production is intentional
- Feature flags, secrets, and infrastructure config reviewed
- No staging-only config leaking to production

**How to collect evidence:**
```bash
# Compare env files (sanitize secrets before pasting)
diff .env.staging .env.production | grep "^[<>]" | sed 's/=.*/=***/'

# Kubernetes/Docker: review configmap/secret diff
kubectl diff -f k8s/production/

# Terraform: plan output
terraform plan -var-file=production.tfvars
```

**Pass criteria:**
- All required vars confirmed present in production config
- Any diff between staging and production is expected and documented

**Fail criteria (DO NOT SHIP if any are true):**
- New env var added in code but not set in production
- Unknown config difference between staging and production
- "It's the same config as staging" without checking

**Evidence template:**
```
GATE G4 Config: CLEARED
Evidence:
  New vars added: FEATURE_X_ENABLED (set to true in prod config ✓)
  Config diff: 1 intentional difference — DEBUG=false in prod, true in staging
```

---

## G5: Staging Deployment Verified

**What to check:**
- Code was deployed to staging before production
- Smoke tests pass on staging (critical user paths work)
- No error spike in staging logs after deploy

**How to collect evidence:**
```bash
# Staging deploy confirmation
curl -s https://staging.yourapp.com/health | jq .

# Check staging logs for errors
kubectl logs -n staging deployment/api --since=10m | grep -i error | tail -20

# Smoke test
curl -s https://staging.yourapp.com/api/v1/ping
```

**Pass criteria:**
- Staging health endpoint returns 200
- Critical user path (login, checkout, etc.) works manually
- No new error pattern in logs

**Fail criteria (DO NOT SHIP if any are true):**
- Code never deployed to staging ("we'll test in prod")
- Staging shows errors that haven't been investigated
- "Staging and prod are different so staging tests don't count"

**Evidence template:**
```
GATE G5 Staging: CLEARED
Evidence:
  Staging deploy: 2026-03-13 14:32 UTC — build #891
  Health: {"status":"ok","version":"1.4.2"}
  Smoke test: login ✓, checkout ✓, API /ping ✓
  Logs: 0 new errors in last 10 min
```

---

## G6: Rollback Plan Documented

**What to check:**
- Rollback steps are written down and specific
- Rollback has been validated (at minimum, reviewed for feasibility)
- Who executes the rollback and how to trigger it is clear

**Rollback plan minimum requirements:**
1. Command or action to revert (specific, not "undo the change")
2. Expected time to complete rollback
3. How to verify rollback succeeded
4. Who to notify

**How to collect evidence:**
```bash
# Git rollback
git revert HEAD --no-edit && git push origin main

# Kubernetes rollback
kubectl rollout undo deployment/api -n production
kubectl rollout status deployment/api -n production

# Feature flag toggle
curl -X POST https://flags.yourapp.com/api/FEATURE_X/disable
```

**Pass criteria:**
- Specific commands documented, not "revert the commit"
- Steps are executable by someone other than the author
- Database rollback steps included if schema changed

**Fail criteria (DO NOT SHIP if any are true):**
- No rollback plan written ("we'll figure it out if needed")
- Rollback plan is "we can just redeploy the old version" without specific commands
- DB migration with no rollback migration

**Evidence template:**
```
GATE G6 Rollback: CLEARED
Evidence:
  Rollback steps:
  1. kubectl rollout undo deployment/api -n production
  2. Verify: curl https://api.yourapp.com/health → {"version":"1.4.1"}
  3. Notify: #incidents Slack channel
  Estimated rollback time: ~3 minutes
```

---

## G7: Code Review Approved

**What to check:**
- At least one reviewer (not the author) approved the PR
- Review was substantive (not rubber-stamp)
- Security-sensitive code had appropriate reviewer

**How to collect evidence:**
```bash
# GitHub
gh pr view <PR_NUMBER> --json reviews,reviewDecision

# GitLab
glab mr view <MR_IID>
```

**Pass criteria:**
- Minimum 1 approval from non-author
- Approval is on the current commit (no force-push after approval)
- PR has no blocking comments unresolved

**Fail criteria (DO NOT SHIP if any are true):**
- No PR created ("it's a small fix")
- PR approved by the author themselves
- Unresolved blocking comments
- Approved on an older commit, force-pushed after

**Evidence template:**
```
GATE G7 Code Review: CLEARED
Evidence:
  PR: github.com/org/repo/pull/847
  Approvals: @alice (2026-03-13 13:15 UTC)
  Blocking comments: 0
  Commit approved: a3f9d21 (current HEAD ✓)
```

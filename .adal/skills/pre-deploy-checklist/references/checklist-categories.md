# Checklist Categories & Item Templates

Reference for generating domain-specific checklist items.
Use these as a guide — adapt to actual project stack and findings from subagents.

---

## Universal Items (Every Project)

### Security (Always 🔴 unless noted)
- [ ] 🔴 No secrets/API keys committed to git (`git log --all -S "SECRET"` or check .env)
- [ ] 🔴 All environment variables documented in `.env.example`
- [ ] 🔴 Dependencies scanned for known vulnerabilities (`npm audit` / `cargo audit` / `pip-audit`)
- [ ] 🔴 HTTP headers include security headers (CSP, X-Frame-Options, HSTS)
- [ ] 🟡 Rate limiting enabled on public endpoints
- [ ] 🟡 CORS configured to whitelist only known origins
- [ ] 🟢 Security headers score A on securityheaders.com

### Performance (Always)
- [ ] 🔴 Core user flow completes in <3s on slow 3G (use Chrome DevTools throttling)
- [ ] 🟡 Largest Contentful Paint (LCP) <2.5s on desktop
- [ ] 🟡 Database queries <100ms on expected data volume (check query explain plan)
- [ ] 🟡 No N+1 queries on list pages (check ORM query logs)
- [ ] 🟢 Assets compressed (gzip/brotli enabled on server)
- [ ] 🟢 Images optimized and served in WebP/AVIF format

---

## Frontend Checklist Items

### Critical Flows
- [ ] 🔴 [Main user action] — describe exact expected behavior and timing
- [ ] 🔴 Form submission with valid data → success state shown, data saved correctly
- [ ] 🔴 Form submission with invalid data → inline error messages appear near relevant field
- [ ] 🔴 Network error during form submit → error message shown, data NOT lost
- [ ] 🔴 Navigation to protected routes while logged out → redirects to login

### UI/UX
- [ ] 🟡 Responsive layout at mobile (375px), tablet (768px), desktop (1280px)
- [ ] 🟡 Loading states shown for all async operations (spinner/skeleton)
- [ ] 🟡 Empty states handled (no blank pages when lists are empty)
- [ ] 🟡 Toast/notification messages visible and dismissible
- [ ] 🟢 No console errors in browser DevTools on main pages
- [ ] 🟢 Favicon and page titles set correctly for each page

### Accessibility
- [ ] 🟡 Keyboard navigation works through main form flows (Tab, Enter, Escape)
- [ ] 🟡 Interactive elements have accessible labels (inputs, buttons, icons)
- [ ] 🟡 Color contrast ratio meets WCAG AA (4.5:1 for text) — use axe DevTools
- [ ] 🟢 Screen reader announces page changes correctly (aria-live regions)

---

## Backend/API Checklist Items

### Core Functionality
- [ ] 🔴 All critical API endpoints return correct HTTP status codes
  - 200 OK for success
  - 201 Created for new resources
  - 400 Bad Request for invalid input (with error details in body)
  - 401 Unauthorized for missing auth
  - 403 Forbidden for insufficient permissions
  - 404 Not Found for missing resources
  - 500 Internal Server Error logged but not exposed to client
- [ ] 🔴 Request input validated and sanitized before processing
- [ ] 🔴 Auth middleware applied to all protected endpoints (test without token → 401)

### Error Handling
- [ ] 🔴 Server errors return generic message to client (no stack traces in production)
- [ ] 🔴 All errors logged with enough context (user ID, request ID, timestamp)
- [ ] 🟡 Graceful shutdown handles in-flight requests

### Integration Points
- [ ] 🔴 External API integrations have timeout configured (<10s default)
- [ ] 🔴 External API failures handled (fallback behavior or clear error to user)
- [ ] 🟡 Webhook endpoints validate signatures

---

## Database Checklist Items

### Migrations
- [ ] 🔴 All pending migrations applied to staging environment and tested
- [ ] 🔴 Migration rollback script tested on staging
- [ ] 🔴 New columns have appropriate NOT NULL constraints or defaults
- [ ] 🟡 No migration drops columns that production data depends on (check data first)

### Data Integrity
- [ ] 🔴 Foreign key constraints in place where relationships exist
- [ ] 🟡 Indexes exist on columns used in WHERE/JOIN clauses of frequent queries
- [ ] 🟡 Unique constraints on email, usernames, or other unique fields
- [ ] 🟢 Database connection pool size appropriate for expected concurrent users

### Backup & Recovery
- [ ] 🔴 Database backup configured and tested (can restore from backup?)
- [ ] 🟡 Backup frequency matches data criticality (daily minimum for user data)

---

## Authentication Checklist Items

- [ ] 🔴 Login with valid credentials → access granted, redirect correct
- [ ] 🔴 Login with invalid password → error shown, account NOT locked after 1 attempt
- [ ] 🔴 Login with non-existent email → same error message as invalid password (no user enumeration)
- [ ] 🔴 Brute force protection: account locked or rate limited after N failed attempts
- [ ] 🔴 Password reset flow → token sent to email, token expires (24h max), one-time use
- [ ] 🔴 JWT/session expires after configured timeout
- [ ] 🔴 Logout clears all tokens/sessions (including refresh tokens in DB)
- [ ] 🔴 Passwords hashed with bcrypt (cost ≥12) or argon2id
- [ ] 🟡 OAuth callback validates `state` parameter (CSRF protection)
- [ ] 🟡 Remember me / refresh tokens stored as httpOnly cookies

---

## Infrastructure Checklist Items

- [ ] 🔴 All production environment variables set in deployment platform
- [ ] 🔴 Health check endpoint responds with 200 (e.g., `GET /health`)
- [ ] 🔴 Application starts successfully with production env vars (no missing config)
- [ ] 🔴 Rollback procedure documented and tested
- [ ] 🟡 CI/CD pipeline runs: lint → test → build → deploy (all passing)
- [ ] 🟡 Deployment window communicated to team
- [ ] 🟡 On-call/responsible person available during and after deployment
- [ ] 🟡 Monitoring/alerting configured (error rate spike alerts)
- [ ] 🟢 Resource limits set (container memory/CPU limits)
- [ ] 🟢 Auto-scaling configured if expecting variable load

---

## Output Template

```markdown
# Pre-Deploy Checklist — [Project Name]

> **Generated:** [date]
> **Stack:** [detected stack]
> **Domains analyzed:** [list of domains]
> **Total items:** [count] (🔴 [n] Critical, 🟡 [n] Important, 🟢 [n] Nice-to-have)

---

## How to Use This Checklist

Mark each item as you test it. **All 🔴 Critical items MUST pass before deploying.**
Items include exact steps — no context needed beyond this document.

---

## 🔴 Critical Items Summary
[Quick list of all critical items across all domains, for easy scanning]

---

## [Domain 1] — [e.g., Authentication]
[checklist items]

## [Domain 2] — [e.g., Frontend]
[checklist items]

## [Domain N] — [e.g., Infrastructure]
[checklist items]

---

## Pre-Deploy Sign-off

- [ ] All 🔴 Critical items checked and passing
- [ ] At least 80% of 🟡 Important items checked
- [ ] Performance baseline measured and documented
- [ ] Rollback plan confirmed and person responsible identified
- [ ] Team deployment window communicated

**Signed off by:** _________________ **Date:** _________________
```

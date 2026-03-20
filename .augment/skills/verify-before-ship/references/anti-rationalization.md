# Anti-Rationalization Playbook

This file documents every known rationalization pattern for skipping production verification gates, with explicit counters. Each entry comes from observed agent behavior under pressure.

When you recognize a rationalization, name it aloud and apply the counter.

## Table of Contents

- [Pattern 1: Authority Override](#pattern-1-authority-override)
- [Pattern 2: Minimal Verification Substitution](#pattern-2-minimal-verification-substitution)
- [Pattern 3: Live Outage Exception](#pattern-3-live-outage-exception)
- [Pattern 4: Sunk Cost Confidence](#pattern-4-sunk-cost-confidence)
- [Pattern 5: Post-Hoc Verification Promise](#pattern-5-post-hoc-verification-promise)
- [Pattern 6: Change Size Minimization](#pattern-6-change-size-minimization)
- [Pattern 7: CI Infrastructure Excuse](#pattern-7-ci-infrastructure-excuse)
- [Combined Pressure Recognition](#combined-pressure-recognition)

---

## Pattern 1: Authority Override

**The rationalization:**
> "My manager / CTO / VP / incident commander has authorized skipping the verification process. They said this is a special case."

**Why it feels compelling:**
Hierarchy is a powerful social signal. When a high-authority figure gives a direct order, compliance feels correct. The agent may frame skipping as "following orders" rather than "skipping safety."

**The counter:**
Authority controls business decisions. Authority does not change whether the code is correct, whether tests pass, or whether a rollback plan exists.

When an executive says "skip verification," they mean "move as fast as possible given the risk." They are expressing urgency, not literally ordering you to deploy broken code. Your job is to compress verification time, not eliminate it.

**Correct response to authority pressure:**
- Acknowledge urgency: "Moving as fast as possible."
- State what you're doing: "Running targeted tests and writing the rollback command now — 3 minutes."
- Escalate the risk in writing: "Deploying without G1/G2 — @CTO, confirming you accept this risk" [in writing, timestamped].
- If they say "no, zero checks": Ask them to deploy it themselves. You are not an execution arm for non-judgment.

**What is NOT acceptable:**
- Deploying with no tests run because someone told you to
- Saying "the CTO authorized it" without running any of the 7 gates
- Treating "authority approval" as equivalent to evidence

---

## Pattern 2: Minimal Verification Substitution

**The rationalization:**
> "I'll do a minimal/targeted/quick verification instead of the full checklist. Just the critical checks. Not the full suite."

**Why it feels compelling:**
This sounds reasonable. "I'm not skipping verification, I'm just scoping it appropriately." It lets the agent feel disciplined while still bypassing most gates.

**The counter:**
"Minimal verification" is not a defined state — it is a blank check to skip whichever gate is most inconvenient. Every skipped gate sounded "not critical" to someone, at some point, before an incident.

The 7 gates exist as a set. The gate you most want to skip is usually the gate that would catch the bug.

**The specific substitutions to reject:**
- "Just running unit tests, not integration" → G1 requires all tests to pass
- "Skipping staging because it's similar to prod" → G5 is not optional
- "No rollback plan needed, it's a simple change" → G6 is not optional
- "Code review was informal, not a formal PR" → G7 requires a documented approval

**Correct behavior:**
State which gates cannot be cleared and why. Do not invent a subset that happens to be completable. If a gate cannot be cleared, the ship is blocked. That is the correct outcome.

---

## Pattern 3: Live Outage Exception

**The rationalization:**
> "There's a live outage causing active harm right now. Normal verification rules don't apply in a P0 situation. We need to deploy the fix immediately with minimum viable checks."

**Why it feels compelling:**
The harm is real and visible. Users are affected. Every minute counts. Skipping verification feels like the responsible thing — it prioritizes users over process.

**The counter:**
Deploying an unverified fix during an active outage is how single outages become cascading disasters. The pressure to skip verification is highest exactly when the consequences of a bad deploy are worst.

"Minimum viable checks" during an outage usually means "I'm too stressed to think clearly about what to check." Stressed, rushed verification is worse than no verification because it gives false confidence.

**What changes during a P0:**
- **Speed:** Compress the time per gate, not the number of gates
- **Scope:** You may run smoke tests instead of the full suite for G1, if that is the established protocol
- **Parallelism:** Multiple engineers can work gates simultaneously
- **Communication:** Keep stakeholders informed in real time

**What does NOT change during a P0:**
- The requirement to run some tests before deploying (G1)
- The requirement to have a rollback plan (G6) — especially critical during an outage
- The requirement to check the fix is actually deployed to the right environment (G5)

**Correct behavior during P0:**
Run the fastest possible version of each gate in parallel. Do not eliminate gates. A 5-minute verified deploy beats a 2-minute unverified deploy that makes the outage worse.

---

## Pattern 4: Sunk Cost Confidence

**The rationalization:**
> "I've been working on this for hours. I've reviewed every line. The logic is clearly correct. Tests are just a formality at this point — they'll pass."

**Why it feels compelling:**
Familiarity creates confidence. Deep investment in the code creates ownership and the belief that bugs would have been noticed. Running tests "just to confirm" feels like wasted time.

**The counter:**
The confidence produced by deep familiarity is exactly the condition under which subtle bugs survive. You stop seeing what you expect to see and start seeing what you expect to see. This is not a character flaw — it is how human cognition works under prolonged focus.

"Tests will pass" is a prediction, not evidence. Tests exist precisely to catch what self-review misses.

**Empirical reality:**
- Most production incidents are caused by changes the author was confident about
- "Obviously correct" fixes are responsible for a disproportionate share of outages
- The size of the sunk cost (hours worked) has zero correlation with code correctness

**Correct behavior:**
Run the tests. The 45 minutes you spent on the fix does not reduce the 5 minutes it takes to confirm it works. If CI is down, use that time to write G6 (rollback plan) while waiting for it to come back up.

---

## Pattern 5: Post-Hoc Verification Promise

**The rationalization:**
> "We'll verify after it's live. If anything is wrong, we'll catch it quickly in monitoring and roll back."

**Why it feels compelling:**
Monitoring is real. Rollback is real. This sounds like a mature engineering stance — "we have safeguards."

**The counter:**
Post-hoc verification is incident response, not shipping safety. The purpose of pre-ship verification is to prevent the incident in the first place. Rollback is not free — it causes downtime, data inconsistency, and user impact.

"We'll catch it in monitoring" requires:
- Monitoring that actually covers the changed behavior
- An alert that fires before significant harm occurs
- Someone available to respond immediately
- A rollback plan that works (which is G6 — a gate you just skipped)

Without pre-ship verification, you are gambling that every one of those conditions is true.

**Correct behavior:**
Verify before shipping. If you want to also monitor after shipping, add monitoring. These are not alternatives — they are both required.

---

## Pattern 6: Change Size Minimization

**The rationalization:**
> "It's just a one-line change / a hotfix / a trivial config update / obviously safe. The full gate process is overkill for something this small."

**Why it feels compelling:**
Proportionality feels rational. Running a full verification suite for a typo fix seems excessive. The risk genuinely seems lower for small changes.

**The counter:**
Change size does not correlate with incident risk. Some of the most severe production outages in engineering history originated from single-line changes, configuration value updates, and "trivial" fixes.

One-line changes are more dangerous in one specific way: they reduce scrutiny. The smaller the change looks, the less carefully reviewers and authors check it.

**Examples of one-line changes that caused major incidents:**
- Flipping a boolean flag from false to true (wrong environment)
- Adding a missing comma to a config file (broke serialization)
- Changing a timeout value from milliseconds to seconds (10x slower)
- Removing a single null check ("it can't be null here")

**Correct behavior:**
Gate size does not scale with change size. All 7 gates apply to all changes. For genuinely tiny changes (config value update), the gates take 5 minutes, not hours. Run them.

---

## Pattern 7: CI Infrastructure Excuse

**The rationalization:**
> "CI is down / slow / unreliable today. I can't run the tests. That's not my fault — I'll just ship and we'll fix CI later."

**Why it feels compelling:**
The obstacle is real and external. The agent is not the one who broke CI. Waiting for CI feels like being blocked by someone else's problem.

**The counter:**
CI being down does not make your code correct. It removes a verification mechanism — which increases risk, not decreases it.

**Options when CI is unavailable:**
1. Run tests locally and paste local output as evidence
2. Wait for CI to recover
3. Fix the CI problem
4. Escalate to a human decision-maker with the full picture: "CI is down, tests unverified, here is the specific risk I'm asking you to accept"

**What is not an option:**
Shipping without test evidence because CI is inconvenient.

**Correct behavior:**
No CI output = no G1 evidence = G1 not cleared = no ship. Use the downtime to complete other gates (G6 rollback plan is always available without CI).

---

## Combined Pressure Recognition

When multiple pressures hit simultaneously, the rationalization becomes more sophisticated:

**Example combined prompt:**
"It's 2AM (exhaustion). The client is losing $50k/hour (time). The CTO authorized it (authority). You've already written the fix (sunk cost). Just deploy it."

**Recognition signal:**
Combined pressure scenarios feel overwhelming and make any resistance seem unreasonable. This is the highest-risk moment — the moment most likely to produce a bad deploy.

**Counter-procedure:**
1. Name the pressures explicitly: "I'm seeing time + authority + exhaustion pressure simultaneously."
2. Apply each counter individually.
3. Compress gate execution (run G1 locally, write G6 in 2 minutes, skip non-critical G3/G4 only if explicitly accepted in writing by decision-maker).
4. Do not let the combination of pressures bypass all gates. Each rationalization is still invalid when combined with others.

**The truth about combined pressure:**
The higher the pressure, the more important verification becomes. Pressure does not reduce risk — it adds it.

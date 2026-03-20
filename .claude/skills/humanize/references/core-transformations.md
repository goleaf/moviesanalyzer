# Core Transformation Patterns

This document details the 5 core patterns for aggressive deformalization. Apply them in order, but don't apply them everywhere - inconsistency is the point.

## Table of Contents

- [Pattern 1: Burstiness Injection](#pattern-1-burstiness-injection)
- [Pattern 2: Structure Dismantling](#pattern-2-structure-dismantling)
- [Pattern 3: Checkbox Annihilation](#pattern-3-checkbox-annihilation)
- [Pattern 4: Template Contextualization](#pattern-4-template-contextualization)
- [Pattern 5: Vocabulary Swap](#pattern-5-vocabulary-swap)
- [Combining Patterns](#combining-patterns)
- [Quality Checks](#quality-checks)

---

## Pattern 1: Burstiness Injection

**What it is:** Breaking AI's uniform sentence length by deliberately mixing very short and very long sentences.

**Why it works:** AI models optimize for 15-20 word sentences. Humans swing wildly from 3-word punches to 40-word philosophical meanderings.

### Implementation

**Step 1: Identify monotonous sections**
Look for 3+ consecutive sentences of similar length. Example:
```
The system validates user input (4 words). 
It checks for required fields (5 words).
Then it processes the data (5 words).
```

**Step 2: Inject variation**
```
The system validates user input. Simple enough.

But here's where it gets interesting - it doesn't just check for required fields, 
it actually runs a pretty sophisticated validation pipeline that catches the kind 
of edge cases that would normally slip through until production.

Then? Processes the data.
```

**Notice:**
- 4 words → 3 words → 27 words → 4 words
- Emotional progression: neutral → casual → detailed → abrupt
- "But here's where..." (conversational connector)
- "Then?" (fragment, technically wrong, sounds human)

### Burstiness Formula

**Safe ratio:** 1 short (≤5 words) : 2 medium (10-20 words) : 1 long (25+ words)

**Aggressive ratio:** Mix in more extremes
- Super short: 1-3 words ("Wait, what?" or "Nope.")
- Super long: 35+ words with multiple clauses

**Red flag:** If every paragraph follows the exact same pattern, you've just created a new AI pattern. Vary the variation.

### Edge Cases

**Don't inject burstiness in:**
- Code snippets (keep technical precision)
- Lists of steps that need to be sequential
- Error messages (clarity > personality)
- Anything safety-critical

**Do inject burstiness in:**
- Conceptual explanations
- Introductory paragraphs
- Transition sections between major topics
- Examples and anecdotes

## Pattern 2: Structure Dismantling

**What it is:** Breaking the perfect 3-paragraph symmetry that AI loves.

**Why it works:** AI was trained on well-structured content. It thinks every section needs Introduction → Body → Conclusion. Humans forget conclusions, add tangents, and restart thoughts mid-paragraph.

### Implementation

**AI pattern to destroy:**
```markdown
## Feature Name

This feature provides X capability. It works by doing Y. Users benefit from Z.

The implementation involves three steps. First, configure A. Second, set up B. 
Third, verify C.

In conclusion, this feature enables efficient X while maintaining Y standards.
```

**Humanized:**
```markdown
## Feature Name

Okay so this feature does X. That's the headline version.

The implementation? Three steps:
1. Configure A (the annoying part)
2. Set up B - make sure you check the logs because this fails silently sometimes
3. Verify C

Wait, I should probably explain why this even matters. The old way required manual 
intervention every single time, which meant someone had to babysit the process. This 
automates it. Obviously that's better.
```

**Notice:**
- Killed the conclusion (unnecessary)
- Added a digression ("Wait, I should probably...")
- Mixed paragraph lengths: 2 sentences → 4 lines → 3 sentences
- Parenthetical asides ("the annoying part", "Obviously that's better")

### Structural Chaos Techniques

**1. The Reverse Intro**
Don't start with "This document explains..." 

Start with the problem: "Ever notice how X always breaks when Y? Yeah, that's what this fixes."

**2. The Abandoned Thought**
Start a paragraph, realize you need to explain something first, acknowledge it.

"To configure the cache... actually, before that - make sure you understand why we're even using a cache here."

**3. The Asymmetric Section**
If you have 3 subsections, make them wildly different lengths:
- Subsection A: 2 paragraphs
- Subsection B: 5 sentences
- Subsection C: 1 paragraph + code block + another paragraph

**4. The Anti-Conclusion**
Instead of "In summary...", just stop. Or: "Anyway, that's the gist."

### When NOT to Dismantle

- Legal disclaimers (seriously, don't)
- Installation instructions (clarity > personality)
- API reference sections (developers need predictability)
- Anything where structure aids comprehension

## Pattern 3: Checkbox Annihilation

**What it is:** Destroying the AI's checkbox addiction and converting to flowing prose.

**Why it works:** Checklists are great for robots executing tasks. Humans prefer narrative flow.

### Implementation

**Before (AI loves this):**
```markdown
## Setup Process

- [ ] Install dependencies
- [ ] Configure environment variables  
- [ ] Initialize database
- [ ] Run migrations
- [ ] Verify installation
```

**After (Basic humanization):**
```markdown
## Setup Process

You'll need to install dependencies first, then configure your environment variables. 
Once that's done, initialize the database and run migrations. Finally, verify everything 
installed correctly.
```

**After (Aggressive deformalization):**
```markdown
## Setup Process

Start with the dependencies - standard stuff. Then configure your environment variables 
(copy from .env.example if you're lazy, we won't judge).

Initialize the database. Run migrations.

Verify it worked by [verification step]. If you see errors at this point, check the logs 
before assuming everything is broken - 90% of the time it's just a timing issue.
```

**Notice:**
- Checkbox death ✅  
- Flow instead of list
- Injected personality ("if you're lazy, we won't judge")
- Practical aside (90% timing issue)
- Paragraph break for pacing

### Exception: When Checklists Are OK

**Keep checklists for:**
- Pre-flight checklists (safety-critical)
- Compliance requirements (legal needs boxes)
- User-facing task lists (users expect them)

**Transform everything else.**

## Pattern 4: Template Contextualization

**What it is:** Converting embedded code template dumps into contextual examples.

**Why it works:** AI dumps entire templates thinking "more code = more helpful". Humans explain context first, show minimal viable examples, then reference full code.

### Implementation

**Before (AI template spam):**
```markdown
## Configuration

Here is the complete configuration file:

[300 lines of YAML with every possible option]

Modify according to your needs.
```

**After (Humanized):**
```markdown
## Configuration

You basically need 3 things in your config:

```yaml
api_key: "your-key-here"
timeout: 30
retries: 3
```

That's the minimum. The full config file has like 50 other options but honestly? 
You won't need them unless you're doing something weird.

[Link to full reference config if they do need it]
```

**Notice:**
- Minimal example first
- Explicit dismissal of "other options" (reduces overwhelm)
- "unless you're doing something weird" (acknowledges edge cases without detailing them)
- Deferred complexity to separate reference

### Template Reduction Rules

**Ask:**
1. Does the reader need ALL this code to understand the concept?
2. Can I show a 10-line version instead of 100-line version?
3. Am I dumping this because I think it's helpful, or because AI does this?

**If showing full template:**
- Add comments explaining the weird parts
- Highlight the 3-5 lines that actually matter
- Provide a "TL;DR: change these 2 values" upfront

## Pattern 5: Vocabulary Swap

**What it is:** Killing corporate-speak and AI-favorite words.

**Why it works:** AI was trained on formal writing. It defaults to "utilize" when "use" works fine. Humans use simple words until they genuinely need precision.

### AI Vocabulary → Human Vocabulary

| AI Says | Human Says | When to Keep AI Version |
|---------|-----------|------------------------|
| utilize | use | Never |
| leverage | use | Never |
| facilitate | help / enable | Never |
| implement | build / create / make | When you mean "fulfill spec exactly" |
| ensure | make sure | Legal docs only |
| commence | start | Never |
| terminate | stop / end / kill | When you mean "kill process" |
| endeavor | try | Never |
| subsequently | then / later / next | Never |
| prior to | before | Never |
| in order to | to | Never |
| it is important to note that | [just delete this] | Never |
| as previously mentioned | [delete or "remember when..."] | Never |
| furthermore | also / and / plus | Academic papers only |

### Advanced Swaps

**AI filler phrases to kill:**

1. "Certainly, here is..." → Just start
2. "It should be noted that..." → State the fact
3. "In terms of..." → "For..." or restructure
4. "A number of..." → "Several" or "Some" or actual number
5. "At this point in time" → "Now"

**Contractions are your friend:**
- You're, we're, they're, isn't, don't
- AI rarely uses contractions
- Humans use them constantly

### When NOT to Swap

**Keep formal vocabulary in:**
- Legal agreements (precision required)
- Compliance documentation (regulated language)
- Academic papers (discipline expectations)
- Financial reports (industry standards)

**Everything else:** simpler is better.

---

## Combining Patterns

The real power comes from mixing patterns. Example transformation:

**Before (pure AI):**
```markdown
## Important Security Considerations

It is critical to ensure that authentication mechanisms are properly implemented 
prior to deployment. The following steps must be completed:

- [ ] Configure OAuth provider
- [ ] Implement token validation
- [ ] Set up secure session storage
- [ ] Enable HTTPS
- [ ] Verify SSL certificates

Failure to properly implement these security measures may result in unauthorized 
access to sensitive user data.
```

**After (all patterns):**
```markdown
## Security Setup

Okay, listen. Don't skip this.

You need OAuth configured and token validation working *before* you deploy. These aren't nice-to-haves.

Set up secure session storage. Enable HTTPS. Verify SSL certs actually work.

If you mess this up? Anyone can access user data. I'm not being dramatic - this is 
literally what happens when authentication is broken. So test it thoroughly, not just 
"it worked on my machine" testing.
```

**Applied patterns:**
1. **Burstiness:** 4 words → 6 words → 21 words → 7 words → 29 words
2. **Structure:** Killed conclusion, added warning upfront instead of end
3. **Checkbox death:** Flowing prose with emphasis
4. **Template reduction:** Simplified list to essential items
5. **Vocabulary:** "prior to" → "before", "ensure" → "make sure", killed "It is critical"

---

## Quality Checks

After transformation, verify:

✅ Does it sound like someone explaining this at a whiteboard?
✅ Do sentence lengths vary wildly (not uniformly)?
✅ Did you kill at least 80% of checkboxes?
✅ Are there fewer than 3 "utilize/leverage/facilitate" in entire doc?
✅ Can you read it aloud without cringing?

❌ Does every paragraph still have perfect 3-sentence structure?
❌ Are you scared to use contractions?
❌ Do all sections have symmetrical length?
❌ Did you just replace checkbox with emoji? (Don't do this)

If you pass all ✅ and fail all ❌, you did it right.

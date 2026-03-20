# Examples Gallery

Before/after transformations across different document types. Use these to calibrate your transformation intensity.

## Table of Contents

- [Example 1: Technical Guide (Flutter Documentation)](#example-1-technical-guide-flutter-documentation)
- [Example 2: API Documentation](#example-2-api-documentation)
- [Example 3: README.md (Open Source Project)](#example-3-readmemd-open-source-project)
- [Example 4: Tutorial/Onboarding](#example-4-tutorialonboarding-high-personality-ok)
- [Example 5: Error Message Documentation](#example-5-error-message-documentation)
- [Intensity Calibration Guide](#intensity-calibration-guide)
- [Common Transformation Mistakes](#common-transformation-mistakes-anti-patterns)

---

## Example 1: Technical Guide (Flutter Documentation)

### Before (AI-generated):

```markdown
## Task 2: Setup folder structure (AC: #2)

**CRITICAL**: The following directory structure MUST be created exactly as specified.

- [x] Create all directories as specified in "Complete Folder Structure" section
- [x] Add `.gitkeep` files to empty directories
- [x] Create barrel files (`auth.dart`, `home.dart`, `weather.dart`) for each feature

It is important to note that failure to create the complete directory structure will result 
in import errors and build failures in subsequent tasks.
```

### After (Humanized):

```markdown
## Setting Up the Folder Structure

You need the full directory tree set up before you start coding. I know, boring, but it 
saves headaches later.

Create all the directories from the "Complete Folder Structure" section. Drop `.gitkeep` 
files in the empty ones so Git actually tracks them. Create barrel files for each feature 
(auth.dart, home.dart, weather.dart).

Skip this and your imports will break. Not "might break" - *will* break. Don't skip this.
```

**Changes applied:**
- ✅ Checkbox death
- ✅ CAPS LOCK → emphasis via italics
- ✅ "I know, boring" - acknowledge the tedium
- ✅ Burstiness: 14 words → 16 words → 26 words → 9 words
- ✅ "Not 'might break' - *will* break" - human emphasis pattern

---

## Example 2: API Documentation

### Before (AI-generated):

```markdown
## Authentication Endpoint

**Endpoint**: POST /api/v1/auth/login

**Description**: This endpoint facilitates user authentication by validating credentials 
and returning an access token.

**Request Parameters**:
- email (string, required): User email address
- password (string, required): User password

**Response**:
- Success (200): Returns authentication token
- Failure (401): Invalid credentials
- Failure (500): Server error

It is important to ensure that all requests utilize HTTPS to maintain security.
```

### After (Humanized):

```markdown
## Login Endpoint

**POST** `/api/v1/auth/login`

Send email + password, get back an access token. Standard stuff.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Response:**
- `200` → You get a token
- `401` → Wrong email/password
- `500` → Something broke on our end

**Important:** Use HTTPS. Please. Don't send passwords over plain HTTP, that's just asking 
for trouble.
```

**Changes applied:**
- ✅ "Standard stuff" - acknowledges simplicity
- ✅ Showed actual JSON instead of describing fields
- ✅ Vocabulary: "facilitate" → removed, "utilize" → "use"
- ✅ Arrows instead of verbose labels
- ✅ "that's just asking for trouble" - conversational warning

---

## Example 3: README.md (Open Source Project)

### Before (AI-generated):

```markdown
# Project Name

## Overview

This project provides a comprehensive solution for X. It leverages modern technologies 
to facilitate Y and ensure optimal Z performance.

## Features

The following features are currently implemented:

- Feature A: Provides capability for X
- Feature B: Enables users to perform Y
- Feature C: Facilitates integration with Z

## Installation

To install this package, utilize the following command:

```bash
npm install package-name
```

Subsequently, import the package into your project:

```javascript
import { Component } from 'package-name';
```

It is important to note that this package requires Node.js version 16 or higher.
```

### After (Humanized):

```markdown
# Project Name

Does X, basically. Uses Y to accomplish Z without being slow.

## What it does

- Feature A: Does the X thing
- Feature B: Lets you Y
- Feature C: Connects to Z APIs

Honestly those three cover 95% of use cases.

## Installation

```bash
npm install package-name
```

Then in your code:

```javascript
import { Component } from 'package-name';
```

**Requires Node 16+** (because we use modern JS features that older versions don't support)
```

**Changes applied:**
- ✅ Killed verbose overview
- ✅ "basically" - casual marker
- ✅ Direct feature descriptions (no "facilitates")
- ✅ "Honestly those three cover 95%" - adds perspective
- ✅ Parenthetical explanation for Node requirement
- ✅ "Then in your code:" instead of "Subsequently, import"

---

## Example 4: Tutorial/Onboarding (High Personality OK)

### Before (AI-generated):

```markdown
## Step 3: Configure Database Connection

In this step, you will configure the database connection parameters.

Navigate to the configuration file and locate the database section. Modify the following 
values according to your setup:

- host: Database server address
- port: Database server port  
- username: Database user credentials
- password: Database password

After completing these modifications, save the file and proceed to the next step.
```

### After (Humanized):

```markdown
## Step 3: Hook Up The Database

Open your config file and find the database section. Should look something like this:

```yaml
database:
  host: localhost
  port: 5432
  username: ???
  password: ???
```

Fill in your actual database info. The `???` is us being cheeky - obviously use real values.

If you're running PostgreSQL locally, `localhost:5432` is probably right. If you're using 
something else or a remote database, update accordingly.

Save it. Next step.
```

**Changes applied:**
- ✅ "Hook Up" instead of "Configure Connection"
- ✅ "Should look something like this" - shows example immediately
- ✅ "us being cheeky" - acknowledges the placeholder format
- ✅ "obviously use real values" - sarcastic aside
- ✅ Much shorter conclusion (not "save file and proceed to next step")

---

## Example 5: Error Message Documentation

### Before (AI-generated):

```markdown
## Error Code: AUTH_001

**Description**: This error indicates that authentication has failed due to invalid credentials.

**Cause**: The provided username or password does not match the records in the database.

**Resolution**: 
1. Verify that the username is correct
2. Ensure the password is entered accurately
3. Check for case sensitivity issues
4. Confirm account is not locked

If the problem persists, contact system administrator.
```

### After (Humanized - GENTLE touch, this is error docs):

```markdown
## AUTH_001: Login Failed

**What happened:** Wrong username or password.

**How to fix:**
1. Double-check your email and password (typos happen)
2. Remember passwords are case-sensitive
3. If you've been locked out after too many attempts, wait 15 minutes

Still broken? Your account might be locked. Contact support at support@example.com
```

**Changes applied:**
- ✅ Plain language error name
- ✅ "What happened" instead of "Description"
- ✅ "(typos happen)" - empathy
- ✅ Specific timeframe for lockout (15 minutes vs vague "wait")
- ✅ Direct contact instead of "system administrator"
- ⚠️ Kept it relatively formal - errors need clarity

---

## Intensity Calibration Guide

Use these examples to match transformation intensity to context:

| Document Type | Intensity | Example From |
|---------------|-----------|--------------|
| Open source README | High | Example 3 |
| Tutorial/Onboarding | High | Example 4 |
| Technical guide | Medium-High | Example 1 |
| API documentation | Medium | Example 2 |
| Error messages | Low-Medium | Example 5 |
| Legal/Compliance | Very Low | [Don't transform] |

**Rule of thumb:**
- Teaching/explaining → More personality OK
- Reference/lookup → Keep it professional  
- Safety/legal → Minimal transformation

---

## Common Transformation Mistakes (Anti-Patterns)

### Mistake 1: Too Much Personality

**Over-transformed:**
```markdown
OMG so you're gonna love this next part!!! 🎉 We're setting up the database and honestly 
it's super easy, like SO easy you won't believe it!!! Just follow these steps and boom 💥 
you're done! #blessed
```

**Right amount:**
```markdown
Setting up the database is pretty straightforward. Follow these steps:
```

**Problem:** Emojis, excessive enthusiasm, trying too hard.

### Mistake 2: Not Enough Transformation

**Under-transformed:**
```markdown
## Database Configuration

To configure the database connection, you must update the configuration file with the 
appropriate parameters. Please ensure all required fields are populated with correct values.
```

**Problem:** Still sounds like AI. "you must", "appropriate parameters", "please ensure"

**Actually transformed:**
```markdown
## Database Setup

Update your config file with your database connection details. All fields are required.
```

### Mistake 3: Losing Technical Precision

**Wrong:**
```markdown
## API Rate Limits

Don't call the API too much or it'll get mad at you lol. Like maybe 100 times per hour? 
Or was it per minute? Anyway just don't spam it.
```

**Problem:** Lost actual rate limit numbers, unclear specification

**Right:**
```markdown
## API Rate Limits

**100 requests per minute, 5000 per hour.** Exceed this and you'll get 429 errors.

If you need higher limits, email api@example.com with your use case.
```

**Fixed:** Kept precise numbers, added concrete error code, clear escalation path.

---

Use these examples as templates, not scripts. Every document is different - trust your 
instinct on how much personality fits the context.

# Tree Insertion Logic

Defines how to insert a new entry at the correct position in been-there-done-that.md.
This is not append-at-bottom. Position is determined by session.end date.

## Table of Contents
- [File Tree Structure](#file-tree-structure)
- [Insertion Algorithm](#insertion-algorithm)
- [Month Name Mapping](#month-name-mapping)
- [Example: Inserting 2026-02-20](#example-inserting-2026-02-20-into-existing-file)
- [Edge Case: Same Repo Multiple Sessions](#edge-case-same-repo-multiple-sessions-in-same-month)

---

## File Tree Structure

```markdown
# Been There Done That

## 2026
### January
#### 2026-01-15 · ProjectA
...entry content...

#### 2026-01-22 · ProjectB
...entry content...

### February
#### 2026-02-10 · ProjectC
...entry content...

## 2025
### December
#### 2025-12-03 · ProjectD
...entry content...
```

**Rules (ASCENDING — oldest at top, newest at bottom):**
- Years: ascending (2024 above 2025 above 2026)
- Months within a year: ascending (January above December)
- Dates within a month: ascending (01 above 15 above 28)
- Multiple entries on same date: append BELOW existing same-date entry
- New entries always go toward the END of their section

---

## Insertion Algorithm

```
INPUT: file content (string), new_entry (string), anchor_date (YYYY-MM-DD)

PARSE anchor_date:
  year  = anchor_date[0:4]   e.g. "2026"
  month = month_name(anchor_date[5:7])  e.g. "February"
  date  = anchor_date        e.g. "2026-02-20"

STEP 1: Find or create year block
  Search for "## <year>" in file.
  IF NOT FOUND:
    Scan all existing year blocks (e.g. "## 2024", "## 2025")
    IF anchor_year > all existing years: append AFTER last year block (newer year at bottom)
    IF anchor_year < some existing year: insert BEFORE that year block (older year stays above)
    Add: "\n## <year>\n"

STEP 2: Find or create month block within year
  Search for "### <month>" within the year block boundaries.
  IF NOT FOUND:
    Find correct position within year block by month number (ascending)
    January=1 ... December=12; insert so months are oldest-first
    Add: "\n### <month>\n"

STEP 3: Find or create date node within month
  Search for "#### <date> ·" within the month block boundaries.
  IF FOUND:
    Append new_entry AFTER existing same-date entry (later on same day = lower)
  IF NOT FOUND:
    Find correct position within month block (dates ascending)
    Insert new_entry at that position

STEP 4: Write updated content back to file
```

---

## Month Name Mapping

```
"01" → "January"    "07" → "July"
"02" → "February"   "08" → "August"
"03" → "March"      "09" → "September"
"04" → "April"      "10" → "October"
"05" → "May"        "11" → "November"
"06" → "June"       "12" → "December"
```

---

## Example: Inserting 2026-02-20 into existing file

**Before:**
```markdown
# Been There Done That

## 2026
### January
#### 2026-01-15 · Yagura
...

### February
#### 2026-02-10 · WooMaps
...
```

**After inserting 2026-02-20 · Yagura:**
```markdown
# Been There Done That

## 2026
### January
#### 2026-01-15 · Yagura
...

### February
#### 2026-02-10 · WooMaps
...

#### 2026-02-20 · Yagura    ← appended after 2026-02-10 (newer = lower)
...new entry...
```

---

## Edge Case: Same Repo, Multiple Sessions in Same Month

If user logs 2 separate sessions from the same repo in the same month,
they appear as 2 separate date entries. They are NOT merged.

```markdown
### February
#### 2026-02-20 · Yagura    ← session B
...

#### 2026-02-05 · Yagura    ← session A (earlier)
...
```

# Version Detection Patterns

Detailed patterns for detecting and updating version strings across ecosystems.

**Contents:** [Node.js](#nodejs) | [Python](#python) | [Rust](#rust) | [Go](#go) | [Generic](#generic) | [Semver](#semver-calculation) | [Multi-file](#multi-file-projects) | [Edge Cases](#edge-cases)

## Node.js

**File:** `package.json`
```bash
# Read
node -p "require('./package.json').version"

# Update (preferred - handles package-lock.json too)
npm version <type> --no-git-tag-version

# Fallback: manual edit via Edit tool on "version" field
```

Also check: `package-lock.json` (auto-updated by npm)

## Python

**File (modern):** `pyproject.toml`
```toml
[project]
version = "1.2.3"

# OR dynamic with setuptools-scm
[tool.setuptools_scm]
```
```bash
# Read
grep -Po '(?<=^version = ")[^"]+' pyproject.toml
```

**File (legacy):** `setup.cfg`
```ini
[metadata]
version = 1.2.3
```

**File (legacy):** `setup.py`
```python
setup(version="1.2.3")
```

**File:** `<package>/__init__.py`
```python
__version__ = "1.2.3"
```

## Rust

**File:** `Cargo.toml`
```toml
[package]
version = "1.2.3"
```
```bash
grep -Po '(?<=^version = ")[^"]+' Cargo.toml
```

Workspace: also check `Cargo.lock` gets regenerated via `cargo check`.

## Go

Go has no standard version file. Check in order:
1. `VERSION` file (plain text)
2. `version.go` with `const Version = "1.2.3"`
3. Latest git tag (primary source for Go modules)

## Generic

**File:** `VERSION` or `VERSION.txt`
```bash
cat VERSION
```
Plain semver string, no quotes.

## Semver Calculation

```
Given current X.Y.Z:
  patch -> X.Y.(Z+1)
  minor -> X.(Y+1).0
  major -> (X+1).0.0

Pre-release:
  prepatch -> X.Y.(Z+1)-beta.1
  preminor -> X.(Y+1).0-beta.1
  premajor -> (X+1).0.0-beta.1
```

## Multi-file Projects

Some projects store version in multiple places. Common combos:
- `package.json` + `package-lock.json` (Node.js)
- `pyproject.toml` + `<pkg>/__init__.py` (Python)
- `Cargo.toml` + `Cargo.lock` (Rust)

When multiple version files exist, update ALL of them to keep consistency.

## Edge Cases

- **Monorepo:** Ask user which package to release. Check for `lerna.json`, `pnpm-workspace.yaml`, or `packages/` directory.
- **No version file found:** Fall back to latest git tag. If no tags exist, ask user for initial version (default: `0.1.0`).
- **Version mismatch:** If git tag and version file differ, warn user and ask which is authoritative.

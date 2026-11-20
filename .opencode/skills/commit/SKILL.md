---
name: commit
description: >-
  Creates git commits following project conventions. Activate when the user
  asks to commit changes, uses /commit, or says something like "commit this",
  "make a commit", or "let's commit".
---

# Commit Workflow

## When to Apply

Activate this skill when:
- The user asks to commit changes
- The user runs `/commit`
- The user says "commit this", "make a commit", "let's commit"

## Commit Rules

1. **Single-line subject only** — no body, no description, no co-author.
2. **Format**: `<type>: <short description>`
   - Types: `feat`, `fix`, `refactor`, `chore`, `docs`, `style`, `test`
3. **Split logically** — if changes span multiple concerns, create separate commits for each. Examples:
   - Feature implementation vs test additions
   - Refactoring consumers vs removing old infrastructure
   - UI changes vs backend changes
4. **Subject line** should be concise, imperative mood, under 72 characters.

## Steps

1. Run `git status` and `git diff --stat` to understand the changes.
2. Group changes by concern and determine commit boundaries.
3. Stage and commit each group with an appropriate single-line message.
4. Run `git status` after to verify clean state.

## What NOT to Include

- No `Co-Authored-By` trailers
- No commit body or description lines
- No `Signed-off-by` lines
- No emojis in messages

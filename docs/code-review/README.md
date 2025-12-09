# Code Review Exchange

Purpose: A compact space for AI and human reviewers to share code-review notes for this plugin. Keep entries terse, actionable, and dated.

File layout
- Place reviews in `docs/code-review/YYYY-MM-DD-topic.md` (use ISO date). One file per review session.
- Keep supporting artifacts (screenshots, logs) alongside the review file in a subfolder with the same stem if needed (e.g., `docs/code-review/2025-12-09-review/assets/`).

Preferred sections for each review
- Summary: 2-3 bullets on scope and overall risk.
- Findings: Ordered by severity (High/Med/Low). Use `path:line` references and clear impact/mitigation notes.
- Questions/Assumptions: Items needing follow-up or clarification.
- Suggested Tests: Steps or commands to validate fixes.

Style guidelines
- Be direct and specific to this repo (WordPress plugin for Stripe CLI demo). Avoid generic advice.
- Use code fences for commands and payload examples.
- If a finding is informational, prefix with `Info:`. If it is actionable, state the fix in the same bullet when obvious.

# Update Progress - AI-Agent Memory System Workflow

**Trigger phrases:** "update progress", "update documentation", "update status"

## Objective

Update the AI-Agent Memory System files (STATUS.md, ROADMAP.md, DECISIONS.md, JOURNAL.md) to capture current session progress, decisions, and learnings.

## Workflow

### 1. Analyze Current Session

First, review what was accomplished in this session:
- Read recent git commits (last 5-10 commits)
- Review conversation context
- Identify completed work, active work, and next steps

### 2. Update STATUS.md

Update the following sections:

**Current Focus:**
- What is the main focus now?
- What phase/milestone are we on?

**Active Work:**
- What tasks are currently in progress?
- Any work started but not finished?
- If nothing active, state "None - All current tasks complete"

**Recently Completed (Last 48 Hours):**
- Add new completions with dates
- Include key metrics/results
- Keep this section fresh (remove items older than 48 hours to "Historical" if needed)

**Blockers:**
- Any current blockers?
- If none, state "NONE - All systems operational"

**Next Steps:**
- Immediate (this/next session)
- Short term (next few days)
- Medium term (next week)

**Key Metrics:**
- Update production/staging versions if changed
- Update memory metrics if changed
- Update uptime/stability status

### 3. Update ROADMAP.md (if applicable)

Check if any milestones were completed:
- Mark checkboxes as complete: `- [x]`
- Move completed phases from "In Progress" to "Completed Milestones"
- Add any new planned work to appropriate phase
- Update completion percentage if milestones changed

### 4. Check for DECISIONS.md Entries

Ask yourself: Were any significant architectural decisions made this session?

**Add entry if:**
- Database/architecture choices made
- Major optimization strategies selected
- Library/framework selections
- Security/performance tradeoffs decided
- Infrastructure changes

**Entry format:**
```markdown
## N. Decision Title

**Date:** YYYY-MM-DD
**Status:** ✅ Active
**Category:** Infrastructure/AI/Database/etc.

### Context
Why did we need to decide?

### Decision
What did we choose?

### Rationale
Why did we choose it?

### Implementation
How was it implemented? (code snippets if helpful)

### Alternatives Considered
1. **Option A** - Why rejected
2. **Option B** - Why rejected

### Outcome
Results after implementation (add retrospectively)
```

### 5. Check for JOURNAL.md Entries

Ask yourself: Did we discover any quick lessons or insights?

**Add entry if:**
- Non-obvious debugging technique that worked
- Performance insight with measurements
- "Wish I knew this earlier" moment
- Pattern or anti-pattern discovered
- Tool/command that saved time

**Entry format (KEEP IT SHORT):**
```markdown
### Lesson Title
**Date:** YYYY-MM-DD

[2-5 sentence explanation of the lesson]

**Lesson:** [One sentence takeaway]

[Optional: Small code snippet if helpful]

---
```

### 6. Git Commit

If any files were updated:

```bash
git add STATUS.md ROADMAP.md DECISIONS.md JOURNAL.md
git commit -m "docs: update AI-Agent Memory System - [brief description]"
```

Consider pushing to origin if appropriate.

## Guidelines

**STATUS.md:**
- Update EVERY session (even if minimal changes)
- Keep "Recently Completed" to last 48 hours
- Be specific with metrics

**ROADMAP.md:**
- Update only when milestones change
- Don't check boxes prematurely
- Keep completion percentage accurate

**DECISIONS.md:**
- Quality over quantity (significant decisions only)
- Full context is critical
- Update "Outcome" section retrospectively

**JOURNAL.md:**
- Keep entries SHORT (user specifically requested this)
- 2-5 sentences max
- Focus on non-obvious learnings
- Include numbers/metrics when relevant

## Output

After completing updates, provide a summary:

```
✅ AI-Agent Memory System Updated

**Files Modified:**
- STATUS.md: [what changed]
- ROADMAP.md: [what changed, or "No changes"]
- DECISIONS.md: [entries added, or "No new decisions"]
- JOURNAL.md: [lessons added, or "No new lessons"]

**Committed:** [Yes/No]
**Next Session Context:** [1-sentence summary of where we are]
```

## Important Notes

- Don't create decisions/journal entries just to fill space
- Be honest about what's complete vs in-progress
- Keep STATUS.md as source of truth for current state
- Future sessions will rely on STATUS.md accuracy

This skill ensures the AI-Agent Memory System stays current and valuable for continuation sessions.

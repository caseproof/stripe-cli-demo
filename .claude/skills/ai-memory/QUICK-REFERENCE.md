# AI Memory System - Quick Reference Card

**One-page guide for AI-assisted development**

---

## 📁 The 5 Files

| File | Purpose | When to Update | Keep It |
|------|---------|----------------|---------|
| **STATUS.md** | Current state | Every session | Current |
| **ROADMAP.md** | Task checklist | Weekly | Active milestones |
| **DECISIONS.md** | Decision log | When deciding | Historical |
| **JOURNAL.md** | Learning log | When discovering | Growing |
| **CLAUDE.md** | Project context | Rarely | Foundational |

---

## ⚡ Quick Workflow

### Session Start (2 min)
```
1. Read STATUS.md     → "Where are we?"
2. Read ROADMAP.md    → "What's next?"
3. Start coding       → You have full context
```

### During Session
```
- Make decision?      → Note for DECISIONS.md
- Discover something? → Note for JOURNAL.md
- Complete task?      → Mark in ROADMAP.md
```

### Session End (5 min)
```
1. Update STATUS.md:
   - What completed?
   - What's in progress?
   - What's next?

2. Update ROADMAP.md:
   - Mark [x] completed tasks

3. Commit everything:
   git add .
   git commit -m "feat: completed X

   Updated STATUS.md with progress"
```

---

## 📝 STATUS.md Template

```markdown
# Project Status

**Last Updated:** YYYY-MM-DD HH:MM
**Current Phase:** Milestone X - Name
**Current Task:** What you're doing (X% complete)

## Recent Progress
- ✅ Completed item
- ⏳ In progress (X%)

## Current Blockers
None / List blockers

## Next Steps
1. Next thing to do
2. Then this
3. After that
```

**Update every session!**

---

## 🗺️ ROADMAP.md Format

```markdown
## Milestone 1: Name ⏳ (X% complete)

**Goal:** What this achieves

### Tasks
- [x] Completed
- ⏳ In progress (X%)
- [ ] Pending
- [ ] Pending

---

## Milestone 2: Name (Not started)

**BLOCKED UNTIL:** Milestone 1 complete
```

**Mark tasks complete as you go!**

---

## 🎯 DECISIONS.md Entry

```markdown
## Decision NNN: Title

**Date:** YYYY-MM-DD
**Status:** ✅ Active

**Decision:** What was chosen

**Alternatives:**
1. Option A - pros/cons
2. Option B (chosen) - pros/cons
3. Option C - pros/cons

**Rationale:** Why B was chosen

**Tradeoffs:**
- ❌ What we gave up
- ✅ What we gained
```

**Log significant decisions only!**

---

## 📚 JOURNAL.md Entry

```markdown
## YYYY-MM-DD: Title

**Discovery:** What you found

**Problem:** What was the issue

**Solution:** How you fixed it

**Code:**
```php
// Example
```

**Learning:** Key takeaway
```

**Capture non-obvious learnings!**

---

## ✅ DO

- ✅ Update STATUS.md every session (5 min)
- ✅ Read files at session start (2 min)
- ✅ Log significant decisions
- ✅ Mark ROADMAP.md tasks complete
- ✅ Commit memory files with code

## ❌ DON'T

- ❌ Skip STATUS.md updates (next session suffers)
- ❌ Log trivial decisions (variable names)
- ❌ Over-document (keep concise)
- ❌ Let files get stale (update before commit)
- ❌ Forget to commit memory files

---

## 🎨 Status Indicators

**Use consistently:**
- ✅ Complete
- ⏳ In Progress
- ❌ Blocked
- ⚠️ Deprecated
- 📍 Current/Active

**Checkboxes:**
- `[x]` Completed
- `⏳` In progress (add %)
- `[ ]` Not started

---

## 🚀 Session Commands

**Start:**
```bash
# Read memory files
cat STATUS.md
cat ROADMAP.md
```

**End:**
```bash
# Update and commit
vim STATUS.md    # Update progress
vim ROADMAP.md   # Mark tasks
git add .
git commit -m "feat: description

Updated STATUS.md with progress"
```

---

## 📊 What to Log

### Log in DECISIONS.md:
✅ Architecture choices
✅ Technology selections
✅ Design patterns
✅ Major tradeoffs
❌ Variable names
❌ Code formatting

### Log in JOURNAL.md:
✅ Non-obvious discoveries
✅ Edge cases found
✅ Performance insights
✅ "Aha!" moments
❌ Routine implementation
❌ Obvious fixes

---

## ⏱️ Time Investment

**Setup (one-time):**
- 20 min: Create files
- 10 min: Fill initial content
- **Total: 30 min**

**Maintenance (per session):**
- 2 min: Read at start
- 5 min: Update at end
- **Total: 7 min/session**

**ROI:**
- Saves 20+ min next session
- **Break-even after 2-3 sessions**

---

## 🎯 Success Metrics

**You're doing it right if:**
- ✅ Next session starts in < 5 min (no catching up)
- ✅ No "what did we decide?" questions
- ✅ STATUS.md updated every session
- ✅ AI picks up where you left off seamlessly

**You need to improve if:**
- ❌ Still spending 15+ min catching up
- ❌ Re-debating settled decisions
- ❌ STATUS.md outdated by weeks
- ❌ AI doesn't understand context

---

## 🔗 Resources

**Full Documentation:**
- `.claude/skills/ai-memory/skill.md` - Skill docs
- `.claude/skills/ai-memory/README.md` - Overview

**Templates:**
- `.claude/skills/ai-memory/templates/STATUS.md.template`
- `.claude/skills/ai-memory/templates/ROADMAP.md.template`
- `.claude/skills/ai-memory/templates/DECISIONS.md.template`
- `.claude/skills/ai-memory/templates/JOURNAL.md.template`

---

## 💡 Pro Tips

**Tip 1:** Link STATUS.md to DECISIONS.md
```markdown
## What I'm Working On
Implementing OAuth (see Decision 002 for why we chose Google first)
```

**Tip 2:** Include percentages for in-progress items
```markdown
- ⏳ Cookie tracking (40% complete)
```

**Tip 3:** Keep "Next Steps" specific
❌ "Continue development"
✅ "Complete cookie tracking, then test with MemberPress signup hook"

**Tip 4:** Update timestamp every edit
```markdown
**Last Updated:** 2025-01-21 11:00  ← Always current
```

**Tip 5:** Read STATUS.md before asking AI anything
Gives AI instant context without you explaining

---

## 🎓 Remember

**This system:**
- Saves time (20+ min per session)
- Preserves knowledge (never forget decisions)
- Helps AI (persistent memory)
- Helps humans (resume after breaks)
- Requires discipline (must update)

**The secret:** Treat memory files as important as code

**The payoff:** Seamless sessions, consistent decisions, preserved knowledge

---

**Print this and keep at your desk! 📄**

**Version:** 1.0 | **Updated:** 2025-01-21

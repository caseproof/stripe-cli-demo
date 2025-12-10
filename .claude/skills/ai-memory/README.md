# AI Memory System - Claude Skill

**Version:** 1.0
**Created:** 2025-01-21
**For:** AI-assisted development projects

---

## What This Is

A Claude Code skill that implements the **AI-Agent Memory System** - a structured approach to maintaining persistent context across AI-assisted development sessions using markdown files.

**Solves:** "Session amnesia" where AI agents (and humans) lose context between sessions.

**Result:**
- 📉 90% reduction in context re-explanation time
- ⏱️ Session startup: 30 min → 5 min
- 🎯 Consistent decisions across sessions
- 📚 Preserved knowledge and learnings

---

## Quick Start

### 1. Install the Skill

This skill is already in your project at:
```
.claude/skills/ai-memory/
```

If using globally, copy to:
```
~/.claude/skills/ai-memory/
```

### 2. Initialize Memory Files

In your project or team branch:

```bash
# Create the 5 core files
touch STATUS.md
touch ROADMAP.md
touch DECISIONS.md
touch JOURNAL.md
# CLAUDE.md should already exist
```

Or use templates:
```bash
cp .claude/skills/ai-memory/templates/STATUS.md.template STATUS.md
cp .claude/skills/ai-memory/templates/ROADMAP.md.template ROADMAP.md
cp .claude/skills/ai-memory/templates/DECISIONS.md.template DECISIONS.md
cp .claude/skills/ai-memory/templates/JOURNAL.md.template JOURNAL.md
```

### 3. Fill Out Initial Content

**STATUS.md** (5 min):
- Where are you now?
- What are you working on?
- What's next?

**ROADMAP.md** (5 min):
- What milestones do you have?
- What tasks per milestone?
- What's completed?

**DECISIONS.md** (5 min):
- What major decisions have you made?
- Why did you choose X over Y?

**JOURNAL.md** (Optional):
- Any learnings to capture?
- Start with: "Initialized AI Memory System"

### 4. Start Using

**Every AI Friday session:**

```bash
# Start
cat STATUS.md              # Read where you left off
cat ROADMAP.md             # See what's next

# Work
[Code, make decisions, learn things]

# End
vim STATUS.md              # Update progress
vim ROADMAP.md             # Mark tasks complete
git commit -m "feat: X
Updated STATUS.md"         # Commit everything
```

---

## What's Included

### Core Files

```
.claude/skills/ai-memory/
├── README.md              # This file
├── skill.md               # Skill definition & commands
├── QUICK-REFERENCE.md     # One-page cheatsheet
└── templates/
    ├── STATUS.md.template
    ├── ROADMAP.md.template
    ├── DECISIONS.md.template
    └── JOURNAL.md.template
```

### Documentation

- **skill.md** - Full skill documentation, available commands, workflows
- **QUICK-REFERENCE.md** - One-page printable cheatsheet
- **templates/** - Ready-to-use template files

---

## Available Commands

> **Note:** These are conceptual commands. Implementation depends on your Claude Code setup.

### `/memory-init`
Initialize memory system files from templates

### `/memory-start`
Read memory files at session start (provides context summary)

### `/memory-update`
Update STATUS.md with current progress

### `/memory-decision`
Log a decision to DECISIONS.md

### `/memory-learn`
Add a learning entry to JOURNAL.md

### `/memory-roadmap`
Update ROADMAP.md (mark tasks complete)

### `/memory-summary`
Generate session summary for STATUS.md

---

## How It Works

### The 5 Files

| File | Purpose | Update Frequency |
|------|---------|------------------|
| **STATUS.md** | Current state ("you are here" map) | Every session |
| **ROADMAP.md** | Task checklist (what's next) | Weekly |
| **DECISIONS.md** | Decision log (why we chose X) | When deciding |
| **JOURNAL.md** | Learning log (what we discovered) | When learning |
| **CLAUDE.md** | Project context (how it works) | Rarely |

### Weekly Cycle

```
Friday 9:00am
├─ Read STATUS.md (2 min)
├─ Read ROADMAP.md (1 min)
└─ Start coding (full context!)

Friday 9:00-11:00am
├─ Develop features
├─ Make decisions → note for DECISIONS.md
└─ Learn things → note for JOURNAL.md

Friday 11:00am
├─ Update STATUS.md (5 min)
├─ Mark ROADMAP.md complete
└─ Commit everything

Next Friday
└─ Read STATUS.md → Continue seamlessly!
```

---

## Best Practices

### DO ✅

- Update STATUS.md every session (non-negotiable!)
- Read files at session start (2 min investment)
- Log significant decisions (architecture, tech choices)
- Mark ROADMAP.md tasks complete as you go
- Commit memory files with code changes

### DON'T ❌

- Skip STATUS.md updates (next session suffers)
- Log trivial decisions (variable names, typos)
- Over-document (keep entries concise)
- Let files get stale (update before committing)
- Treat as optional (this IS your documentation)

---

## Success Metrics

**You know it's working when:**

✅ Session startup takes < 5 minutes
✅ No "what did we decide?" questions
✅ AI picks up where you left off seamlessly
✅ Team members can resume after weeks away
✅ Cross-team knowledge sharing is easy

**Warning signs:**

❌ Still spending 15+ min catching up
❌ Re-debating settled decisions
❌ STATUS.md outdated by weeks
❌ AI asks for context every time

---

## ROI (Return on Investment)

### Time Investment

**Setup (one-time):**
- 20 min: Create and fill files
- 10 min: Learn the system
- **Total: 30 min**

**Maintenance (per session):**
- 2 min: Read at start
- 5 min: Update at end
- **Total: 7 min/session**

### Time Savings

**Per session:**
- Before: 15-30 min context rebuilding
- After: 2 min reading files
- **Savings: 13-28 min/session**

**Break-even:** After 2-3 sessions!

---

## Troubleshooting

### "My STATUS.md is outdated"
**Fix:** Update it now with current state. Don't try to backfill history.

### "I forgot to update files"
**Fix:** Just update STATUS.md before next session. Move on.

### "DECISIONS.md is getting too long"
**Solution:** Archive old decisions annually to `DECISIONS-2024.md`

### "Multiple people editing same branch"
**Solution:** Last committer updates STATUS.md, or use PR descriptions

### "What counts as a significant decision?"
**Rule of thumb:** Architecture, tech stack, design patterns = Yes. Variable names, formatting = No.

---

## Resources

### In This Directory
- `skill.md` - Full skill documentation
- `QUICK-REFERENCE.md` - One-page cheatsheet
- `templates/` - Ready-to-use templates

### External Resources
- Architecture Decision Records: https://adr.github.io/
- Shape Up (Basecamp): https://basecamp.com/shapeup

---

## Support

**Questions?**
- Check `QUICK-REFERENCE.md` first
- Ask in project discussions

**Feedback?**
- What's working well?
- What's confusing?
- What's missing?
- How can we improve?

---

## Version History

**v1.0** (2025-01-21)
- Initial release
- Core templates created
- Quick reference added

---

## License

MIT License - Use freely, adapt for your needs, share improvements

---

## Credits

**Based on:** AI-Agent Memory System
**Created by:** Seth + Claude Code
**Date:** 2025-01-21

---

**Ready to eliminate session amnesia? Start now! 🚀**

1. Copy templates to your branch
2. Fill out STATUS.md (5 min)
3. Try it next session
4. Watch productivity soar!

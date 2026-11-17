# AGENTS.md

This project keeps its agent rules in **CLAUDE.md** (same content applies to Codex, Copilot, Cursor and any AGENTS.md-aware tool).
Read, in order: `CLAUDE.md` → `SPEC.md` → `docs/ARCHITECTURE.md` → `docs/DECISIONS.md`.

Quick reference:
- Tests: `php tests/run.php` must end with `FAILED: 0` before any change is accepted.
- SQL only in `src/Repositories`, always prepared statements.
- Output only through `e()` in `views/`.
- Scope is SPEC.md §2; §3 lists what not to build.

---
description: Implement one acceptance criterion from SPEC.md with explore → plan → test → implement → verify.
argument-hint: <criterion id, e.g. AC7.2>
---
Implement $ARGUMENTS from SPEC.md.
1. Explore: read SPEC.md for $ARGUMENTS, docs/ARCHITECTURE.md, and only the files that layer needs. Change nothing yet.
2. Plan: list the files you will touch and the test(s) you will add. Wait for my approval.
3. Test first: add the failing test in tests/Unit, tests/Integration or tests/Http. Run `php tests/run.php` and show it failing.
4. Implement following CLAUDE.md layer rules. Run `php tests/run.php` until `FAILED: 0`; show the output.
5. Update docs/TRACEABILITY.md (requirement → module → test). Propose a commit message starting with $ARGUMENTS.

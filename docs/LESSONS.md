# LESSONS.md — recurring mistakes and fixes
- Test files share PHP's global scope: a test variable named `$start` overwrote the runner's timer. Fix: each test file is required inside a closure.
- A POST test failed with 419 because the page it came from had no form (no CSRF token). Tests must take the token from the page that really contains the form — like a user.
- Claude Code hook matchers match the tool name (`Bash`), not its arguments; the secret-scan script filters `git commit` itself.
- Booleans to PostgreSQL through PDO: pass `'true'/'false'` explicitly for clarity.
- Weather is external and unreliable: design the "unavailable" state first (ADR-004) and test it before the happy path.

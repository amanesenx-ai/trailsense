#!/usr/bin/env bash
# Blocks a commit if staged changes look like they contain credentials (course: hooks, slide 20).
# Claude Code passes the tool call as JSON on stdin; only act on `git commit`.
input=$(cat)
echo "$input" | grep -q 'git commit' || exit 0
if git diff --cached --name-only | grep -qx 'config/config.php'; then
  echo "BLOCKED: config/config.php holds secrets and must never be committed." >&2; exit 2
fi
if git diff --cached -U0 | grep -E "^\+" | grep -Eiq "(password|passwd|secret|api[_-]?key|token)['\"]?\s*(=>|=|:)\s*['\"][^'\"\$]{6,}['\"]" ; then
  echo "BLOCKED: staged changes look like they contain a credential. Move it to config/config.php." >&2; exit 2
fi
exit 0

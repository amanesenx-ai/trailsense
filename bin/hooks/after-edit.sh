#!/usr/bin/env bash
# Deterministic check after every agent edit: lint changed PHP, then run the unit suite (fast).
set -e
for f in $(git diff --name-only -- '*.php' 2>/dev/null); do php -l "$f" >/dev/null; done
php tests/run.php unit | tail -1

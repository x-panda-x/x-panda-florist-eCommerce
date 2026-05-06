#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

for file in \
  "$PROJECT_ROOT/.codex/PROJECT_MEMORY.md" \
  "$PROJECT_ROOT/.codex/PROJECT_STATUS.md" \
  "$PROJECT_ROOT/.codex/DEPLOYMENT_STATE.md" \
  "$PROJECT_ROOT/.codex/RUNBOOK.md" \
  "$PROJECT_ROOT/.codex/NEXT_ACTIONS.md"
do
  [[ -f "$file" ]] || {
    echo "Missing project memory file: $file" >&2
    exit 1
  }
done

echo "Smoke test memory summary:"
awk -F': ' '
  /^- Main live domain:/ {print "  live-url: " $2; next}
  /^- Main domain:/ && !seen_domain {print "  live-url: " $2; seen_domain=1; next}
  /^- Payment state:/ {print "  payment: " $2; next}
  /^- Current Operational State/ {print "  status: " $2; next}
' \
  "$PROJECT_ROOT/.codex/PROJECT_MEMORY.md" \
  "$PROJECT_ROOT/.codex/PROJECT_STATUS.md" \
  "$PROJECT_ROOT/.codex/DEPLOYMENT_STATE.md" | head -n 4

php "$PROJECT_ROOT/tests/smoke_test_live.php" "$@"

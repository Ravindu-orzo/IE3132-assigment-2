#!/usr/bin/env bash
set -euo pipefail

ZIP="${1:?usage: $0 ZIPFILE WORDLIST}"
WORDLIST="${2:?usage: $0 ZIPFILE WORDLIST}"

ZIP2JOHN_BIN="$(command -v zip2john || true)"
JOHN_BIN="$(command -v john || true)"
[ -n "$ZIP2JOHN_BIN" ] || { echo "zip2john not found - install the jumbo build of John the Ripper" >&2; exit 1; }
[ -n "$JOHN_BIN" ]     || { echo "john not found - install John the Ripper" >&2; exit 1; }

HASH_FILE="$(mktemp)"
trap 'rm -f "$HASH_FILE"' EXIT

"$ZIP2JOHN_BIN" "$ZIP" > "$HASH_FILE" 2>/dev/null

"$JOHN_BIN" --wordlist="$WORDLIST" "$HASH_FILE" >/dev/null

# --show always prints a final "N password hashes cracked" summary line;
# only a real result line has the hash-file's own colon-separated fields,
# so drop the summary before reading the password.
password="$("$JOHN_BIN" --show "$HASH_FILE" | grep -v 'password hash' | head -n1 | cut -d: -f2)"

if [ -z "$password" ]; then
    echo "no match found in $WORDLIST for $ZIP" >&2
    exit 1
fi

echo "the matching password is: $password"

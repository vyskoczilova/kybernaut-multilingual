#!/usr/bin/env bash
# Build dist/kybernaut-multilingual.zip containing a kybernaut-multilingual/ folder, ready for a GitHub Release.
set -euo pipefail

SLUG=kybernaut-multilingual
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BUILD="$(mktemp -d)"
trap 'rm -rf "$BUILD"' EXIT

mkdir -p "$BUILD/$SLUG" "$ROOT/dist"
rsync -a \
	--exclude '.git*' --exclude '.github' --exclude 'bin' --exclude 'dist' --exclude 'docs' \
	--exclude 'CONTEXT.md' --exclude '.DS_Store' \
	"$ROOT/" "$BUILD/$SLUG/"

rm -f "$ROOT/dist/$SLUG.zip"
(cd "$BUILD" && zip -qr "$ROOT/dist/$SLUG.zip" "$SLUG")
echo "Built dist/$SLUG.zip"

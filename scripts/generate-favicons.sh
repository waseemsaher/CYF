#!/usr/bin/env bash
# ==============================================================================
# Favicon Generator Script
# Reads frontend/static/logo/app-icon.svg and generates:
# - favicon.ico
# - favicon-32x32.png
# - favicon.png
# - apple-touch-icon.png (180x180)
# - android-chrome-192x192.png
# - android-chrome-512x512.png
# ==============================================================================

set -euo pipefail

SVG_PATH="frontend/static/logo/app-icon.svg"
OUT_DIR="frontend/static"

if [[ ! -f "${SVG_PATH}" ]]; then
    echo "Notice: ${SVG_PATH} does not exist yet. Please place the SVG logo file at ${SVG_PATH} before running this script."
    exit 1
fi

echo "Generating favicon set from ${SVG_PATH}..."

# Use ImageMagick / Inkscape or Node / Sharp via Docker
docker run --rm -v "$(pwd)/frontend/static:/data" node:20-alpine sh -c "
npm install --no-save sharp;
node -e '
const sharp = require(\"sharp\");
const fs = require(\"fs\");
const svgBuffer = fs.readFileSync(\"/data/logo/app-icon.svg\");

async function run() {
  await sharp(svgBuffer).resize(32, 32).png().toFile(\"/data/favicon-32x32.png\");
  await sharp(svgBuffer).resize(32, 32).png().toFile(\"/data/favicon.png\");
  await sharp(svgBuffer).resize(180, 180).png().toFile(\"/data/apple-touch-icon.png\");
  await sharp(svgBuffer).resize(192, 192).png().toFile(\"/data/android-chrome-192x192.png\");
  await sharp(svgBuffer).resize(512, 512).png().toFile(\"/data/android-chrome-512x512.png\");
  await sharp(svgBuffer).resize(32, 32).toFile(\"/data/favicon.ico\");
  console.log(\"Favicons successfully generated.\");
}
run();
'
"

echo "Favicon generation complete."

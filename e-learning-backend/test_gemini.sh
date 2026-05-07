#!/bin/bash
API_KEY="REDACTED"

echo "=== Testing gemini-flash-latest ==="
curl -s -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{"contents":[{"parts":[{"text":"Say hello in one word"}]}]}' | head -n 15

echo ""
echo "=== Testing gemini-flash-lite-latest ==="
curl -s -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{"contents":[{"parts":[{"text":"Say hello in one word"}]}]}' | head -n 15

echo ""
echo "=== Testing gemini-2.5-flash-lite ==="
curl -s -X POST \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{"contents":[{"parts":[{"text":"Say hello in one word"}]}]}' | head -n 15

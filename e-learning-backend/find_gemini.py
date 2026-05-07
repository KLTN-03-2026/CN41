import json

with open('/tmp/models.json', 'r') as f:
    data = json.load(f)

for m in data.get('models', []):
    if 'gemini' in m['name']:
        print(m['name'])

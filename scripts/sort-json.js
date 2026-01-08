#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const input = process.argv[2];
if (!input) {
    console.error('Usage: node sort-json.js <file.json>');
    process.exit(1);
}

const baseDir = process.cwd();
const resolvedPath = path.resolve(baseDir, input);

if (!resolvedPath.startsWith(baseDir + path.sep) || path.extname(resolvedPath).toLowerCase() !== '.json') {
    console.error('Error: Invalid file path or file type.');
    process.exit(1);
}

try {
    const data = JSON.parse(fs.readFileSync(resolvedPath, 'utf8'));
    const sorted = Object.fromEntries(
        Object.entries(data).sort(([keyA], [keyB]) => keyA.localeCompare(keyB))
    );
    fs.writeFileSync(resolvedPath, JSON.stringify(sorted, null, 2) + '\n');
} catch (err) {
    console.error('Error processing JSON file:', err.message);
    process.exit(1);
}

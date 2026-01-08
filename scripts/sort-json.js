#!/usr/bin/env node
const fs = require("fs");

const file = process.argv[2];
if (!file) {
  console.error("Usage: node sort-json.js <file.json>");
  process.exit(1);
}

const data = JSON.parse(fs.readFileSync(file, "utf8"));

const sorted = Object.keys(data)
  .sort((a, b) => a.localeCompare(b))
  .reduce((acc, key) => {
    acc[key] = data[key];
    return acc;
  }, {});

fs.writeFileSync(file, JSON.stringify(sorted, null, 2) + "\n");

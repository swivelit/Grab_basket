#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const taxonomyPath = path.resolve(__dirname, '../src/constants/analytics-taxonomy.js');
const source = fs.readFileSync(taxonomyPath, 'utf8');

function fail(message) {
  console.error(`analytics-taxonomy validation failed: ${message}`);
  process.exit(1);
}

const versionMatch = source.match(/ANALYTICS_TAXONOMY_VERSION\s*=\s*['"]([^'"]+)['"]/);
if (!versionMatch) {
  fail('ANALYTICS_TAXONOMY_VERSION is missing.');
}

const version = versionMatch[1];
if (!/^v\d+$/.test(version)) {
  fail(`ANALYTICS_TAXONOMY_VERSION must follow v<number>, got '${version}'.`);
}

const eventRegex = /:\s*['"]([a-z0-9_]+)['"]/g;
const events = [];
let match;
while ((match = eventRegex.exec(source)) !== null) {
  events.push(match[1]);
}

if (!events.length) {
  fail('No analytics events found.');
}

const duplicates = [...new Set(events.filter((value, index) => events.indexOf(value) !== index))];
if (duplicates.length) {
  fail(`Duplicate event names: ${duplicates.join(', ')}`);
}

for (const eventName of events) {
  if (!/^[a-z][a-z0-9_]+$/.test(eventName)) {
    fail(`Event '${eventName}' must be snake_case and start with a letter.`);
  }
}

console.log(`analytics-taxonomy validation passed (${events.length} events, version ${version})`);

const fs = require('fs');
const path = require('path');

const resultsDirs = [
    path.join(__dirname, 'client', 'allure-results'),
    path.join(__dirname, 'server-laravel', 'build', 'allure-results')
];

const outputFile = path.join(__dirname, 'allure_results_summary.csv');

function getLabelValue(labels, name) {
    const label = labels.find(l => l.name === name);
    return label ? label.value : '';
}

function formatForSheets(isoString) {
    if (!isoString) return '';
    return isoString.replace('T', ' ').split('.')[0];
}

const allResults = [];

resultsDirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
        console.log(`Directory not found: ${dir}`);
        return;
    }

    const files = fs.readdirSync(dir);
    files.forEach(file => {
        if (file.endsWith('-result.json')) {
            const filePath = path.join(dir, file);
            try {
                const content = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

                const startTimestamp = content.start || 0;
                const start = startTimestamp ? formatForSheets(new Date(startTimestamp).toISOString()) : '';
                const stop = content.stop ? formatForSheets(new Date(content.stop).toISOString()) : '';
                const duration = (content.start && content.stop) ? (content.stop - content.start) : 0;

                let framework = getLabelValue(content.labels || [], 'framework');
                if (!framework) {
                    if (filePath.includes('server-laravel')) {
                        framework = 'pest';
                    } else if (filePath.includes('client')) {
                        framework = getLabelValue(content.labels || [], 'language') === 'javascript' ? 'vitest' : '';
                    }
                }

                let friendlyName = content.name || '';
                if (framework === 'pest') {
                    if (content.description) {
                        friendlyName = content.description;
                    } else if (friendlyName.includes('__pest_evaluable_')) {
                        friendlyName = friendlyName.split('__pest_evaluable_')[1].replace(/_/g, ' ').trim();
                    }
                }

                allResults.push({
                    startTimestamp: startTimestamp, // Temporary for sorting
                    uuid: content.uuid || '',
                    name: friendlyName.replace(/"/g, '""'),
                    status: content.status || '',
                    start: start,
                    stop: stop,
                    duration_ms: duration,
                    parentSuite: getLabelValue(content.labels || [], 'parentSuite').replace(/"/g, '""'),
                    suite: (getLabelValue(content.labels || [], 'suite') || getLabelValue(content.labels || [], 'testClass') || '').replace(/"/g, '""'),
                    subSuite: getLabelValue(content.labels || [], 'subSuite').replace(/"/g, '""'),
                    package: getLabelValue(content.labels || [], 'package').replace(/"/g, '""'),
                    framework: framework,
                    host: getLabelValue(content.labels || [], 'host').replace(/"/g, '""')
                });
            } catch (err) {
                console.error(`Error parsing ${file}:`, err);
            }
        }
    });
});

if (allResults.length === 0) {
    console.log("No results found to convert.");
    process.exit(0);
}

// Sort by timestamp to identify runs
allResults.sort((a, b) => a.startTimestamp - b.startTimestamp);

// Group into runs based on time gaps (e.g., 4 minutes = 240,000 ms)
let currentRun = 1;
const RUN_GAP_MS = 240000;

for (let i = 0; i < allResults.length; i++) {
    if (i > 0 && (allResults[i].startTimestamp - allResults[i - 1].startTimestamp) > RUN_GAP_MS) {
        currentRun++;
    }
    allResults[i].test_run = `Ejecución ${currentRun}`;
}

const headers = Object.keys(allResults[0]).filter(h => h !== 'startTimestamp');
const csvRows = [
    headers.join(','),
    ...allResults.map(row => headers.map(header => `"${row[header]}"`).join(','))
];

const csvContent = '\ufeff' + csvRows.join('\n');
fs.writeFileSync(outputFile, csvContent, 'utf-8');
console.log(`Successfully created ${outputFile} with ${allResults.length} records across ${currentRun} runs.`);

const fs = require('fs');
const path = require('path');

const resultsDirs = [
    path.join(__dirname, 'client', 'allure-results'),
    path.join(__dirname, 'server-laravel', 'build', 'allure-results')
];

const outputFile = path.join(__dirname, 'allure_results_summary.csv');
const categoriesFile = path.join(__dirname, 'categories.json');

// Cargar definiciones de categorías de Allure para clasificar fallos
let categories = [];
if (fs.existsSync(categoriesFile)) {
    try {
        categories = JSON.parse(fs.readFileSync(categoriesFile, 'utf-8'));
    } catch (e) {
        console.error("⚠️ Error al cargar categories.json:", e.message);
    }
}

function getLabelValue(labels, name) {
    const label = (labels || []).find(l => l.name === name);
    return label ? label.value : '';
}

function formatForSheets(isoString) {
    if (!isoString) return '';
    return isoString.replace('T', ' ').split('.')[0];
}

/**
 * Clasifica un test según las reglas de categories.json
 */
function matchCategory(status, message) {
    if (status === 'passed') return '✅ Éxito';
    if (!message) return 'N/A';

    for (const cat of categories) {
        // Verificar si el estado coincide
        if (cat.matchedStatuses && cat.matchedStatuses.includes(status)) {
            // Verificar si el mensaje coincide con el regex
            if (cat.messageRegex) {
                try {
                    const regex = new RegExp(cat.messageRegex, 'i');
                    if (regex.test(message)) {
                        return cat.name;
                    }
                } catch (e) {
                    // Ignorar regex inválidos
                }
            }
        }
    }
    return '📝 Otros Defectos';
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

                const labels = content.labels || [];
                let framework = getLabelValue(labels, 'framework');
                if (!framework) {
                    if (filePath.includes('server-laravel')) {
                        framework = 'pest';
                    } else if (filePath.includes('client')) {
                        framework = getLabelValue(labels, 'language') === 'javascript' ? 'vitest' : '';
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

                // Obtener mensaje de error si existe
                const statusMessage = content.statusDetails ? (content.statusDetails.message || '') : '';
                const category = matchCategory(content.status, statusMessage);

                allResults.push({
                    startTimestamp: startTimestamp, // Temporal para ordenamiento
                    uuid: content.uuid || '',
                    name: friendlyName.replace(/"/g, '""'),
                    status: content.status || '',
                    category: category,
                    test_mode: getLabelValue(labels, 'test_mode') || 'Predeterminado',
                    db_connection: getLabelValue(labels, 'db_connection') || '-',
                    queue_connection: getLabelValue(labels, 'queue_connection') || '-',
                    start: start,
                    stop: stop,
                    duration_ms: duration,
                    parentSuite: getLabelValue(labels, 'parentSuite').replace(/"/g, '""'),
                    suite: (getLabelValue(labels, 'suite') || getLabelValue(labels, 'testClass') || '').replace(/"/g, '""'),
                    subSuite: getLabelValue(labels, 'subSuite').replace(/"/g, '""'),
                    package: getLabelValue(labels, 'package').replace(/"/g, '""'),
                    framework: framework,
                    host: getLabelValue(labels, 'host').replace(/"/g, '""')
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

// Ordenar por timestamp para mantener coherencia cronológica
allResults.sort((a, b) => a.startTimestamp - b.startTimestamp);

const runNumber = process.env.GITHUB_RUN_NUMBER || Date.now();
const testRunLabel = `Ejecución ${runNumber}`;

for (let i = 0; i < allResults.length; i++) {
    allResults[i].test_run = testRunLabel;
}

// Generar encabezados dinámicamente basándose en el primer objeto
const headers = Object.keys(allResults[0]).filter(h => h !== 'startTimestamp');
const csvRows = [
    headers.join(','),
    ...allResults.map(row => headers.map(header => `"${row[header]}"`).join(','))
];

const csvContent = '\ufeff' + csvRows.join('\n');
fs.writeFileSync(outputFile, csvContent, 'utf-8');
console.log(`✅ CSV creado exitosamente: ${outputFile}`);
console.log(`📊 Total registros: ${allResults.length} | Run: ${runNumber}`);

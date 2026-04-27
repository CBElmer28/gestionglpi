const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

/**
 * Script para orquestar la generación de reportes Allure con historial de tendencias.
 */

const rootDir = process.cwd();
const resultsDirs = [
    path.join(rootDir, 'server-laravel', 'allure-results'),
    path.join(rootDir, 'client', 'allure-results')
];
const reportDir = path.join(rootDir, 'allure-report');
const historyDir = path.join(reportDir, 'history');

console.log('\n📊 Iniciando orquestación de reporte Allure...\n');

// Función para limpiar resultados antiguos (evita que Allure sume tiempos de ejecuciones pasadas)
function cleanOldResults(dir) {
    if (fs.existsSync(dir)) {
        const files = fs.readdirSync(dir);
        files.forEach(file => {
            // Solo borrar resultados y contenedores, NO la carpeta history que inyectaremos
            if (file.endsWith('.json') || file.endsWith('.xml') || file.endsWith('.txt')) {
                fs.unlinkSync(path.join(dir, file));
            }
        });
    }
}

// 1. Preparar metadatos de entorno
const envContent = `
OS=${process.platform}
Node=${process.version}
Project=ReadOut Library System
Backend=Laravel + Pest PHP
Frontend=Vue.js + Vitest
E2E=Playwright
Environment=Local Quality Assurance
`;

resultsDirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
    
    // Limpiar resultados previos para evitar acumulación
    cleanOldResults(dir);
    
    // Escribir environment.properties
    fs.writeFileSync(path.join(dir, 'environment.properties'), envContent);
    
    // 2. Inyectar Categorías y Historial
    const categoriesFile = path.join(rootDir, 'categories.json');
    if (fs.existsSync(categoriesFile)) {
        fs.copyFileSync(categoriesFile, path.join(dir, 'categories.json'));
    }

    if (fs.existsSync(historyDir)) {
        const targetHistory = path.join(dir, 'history');
        if (!fs.existsSync(targetHistory)) {
            fs.mkdirSync(targetHistory, { recursive: true });
        }
        
        const files = fs.readdirSync(historyDir);
        files.forEach(file => {
            fs.copyFileSync(path.join(historyDir, file), path.join(targetHistory, file));
        });
        console.log(`✅ Historial inyectado en: ${path.relative(rootDir, dir)}`);
    }
});

// 3. Generar el reporte consolidado
console.log('\n🔨 Generando reporte consolidado...');
try {
    execSync(`npx allure-commandline generate ${resultsDirs.map(d => `"${d}"`).join(' ')} --clean -o "${reportDir}"`, { stdio: 'inherit' });
    
    // 4. Inyectar Personalización Visual (CSS)
    console.log('\n🎨 Aplicando mejoras visuales y leyendas descriptivas...');
    const customCss = `
        /* Leyenda informativa para los gráficos */
        .graphs__content::before {
            content: "📊 GUÍA DE EJES: El eje horizontal (X) representa la Línea de Tiempo (Ejecuciones). El eje vertical (Y) representa el valor de la Métrica (Tiempo, Cantidad, etc.)";
            display: block;
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        /* Personalización del título del reporte */
        .side-nav__brand-text { font-size: 0 !important; }
        .side-nav__brand-text::after {
            content: "ReadOut QA Dashboard";
            font-size: 18px !important;
            font-weight: bold;
            color: #fff;
        }
    `;
    
    const cssPath = path.join(reportDir, 'styles.css');
    if (fs.existsSync(cssPath)) {
        fs.appendFileSync(cssPath, customCss);
        console.log('✅ Etiquetas de ejes e identidad visual aplicadas.');
    }

    console.log('\n✨ Reporte generado con éxito en /allure-report');
} catch (error) {
    console.error('\n❌ Error al generar el reporte:', error.message);
}

// 4. Sugerencia de apertura
console.log('\n🚀 Para ver el reporte con tendencias, ejecuta:');
console.log('   npx allure-commandline open\n');

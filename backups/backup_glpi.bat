@echo off
setlocal

:: --- CONFIGURACIÓN ---
set CONTAINER_NAME=glpi-db
set DB_USER=root
set DB_PASS=root_pass
set DB_NAME=glpi_db
set BACKUP_DIR=%~dp0

:: --- GENERAR NOMBRE DE ARCHIVO CON TIMESTAMP ---
set TIMESTAMP=%DATE:~10,4%%DATE:~7,2%%DATE:~4,2%_%TIME:~0,2%%TIME:~3,2%
set TIMESTAMP=%TIMESTAMP: =0%
set FILENAME=glpi_backup_%TIMESTAMP%.sql

echo ======================================================
echo   ReadOut QA - Automatizador de Backup GLPI
echo ======================================================
echo [*] Conectando con el contenedor: %CONTAINER_NAME%...
echo [*] Base de datos: %DB_NAME%
echo [*] Destino: %BACKUP_DIR%%FILENAME%

:: --- EJECUTAR BACKUP DESDE DOCKER ---
docker exec %CONTAINER_NAME% mysqldump -u %DB_USER% -p%DB_PASS% --default-character-set=utf8mb4 %DB_NAME% > "%BACKUP_DIR%%FILENAME%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [OK] Backup generado con exito: %FILENAME%
) else (
    echo.
    echo [ERROR] Hubo un problema al generar el backup. 
    echo Verifica que Docker este corriendo y el contenedor %CONTAINER_NAME% este activo.
)

echo ======================================================
pause

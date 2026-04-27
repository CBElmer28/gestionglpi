@echo off
setlocal

:: --- CONFIGURACIÓN ---
set CONTAINER_NAME=glpi-db
set DB_USER=root
set DB_PASS=root_pass
set DB_NAME=glpi_db
set BACKUP_DIR=%~dp0
set TEMP_FILE=/tmp/glpi_internal_backup.sql

:: --- GENERAR NOMBRE DE ARCHIVO CON TIMESTAMP (Robusto) ---
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set "dt=%%I"
set "FILENAME=glpi_safe_backup_%dt:~0,8%_%dt:~8,6%.sql"

echo ======================================================
echo   ReadOut QA - Automatizador de Backup GLPI (Safe)
echo ======================================================
docker exec %CONTAINER_NAME% sh -c "mysqldump -u %DB_USER% -p%DB_PASS% --default-character-set=utf8mb4 --result-file=%TEMP_FILE% %DB_NAME%"

if %ERRORLEVEL% EQU 0 (
    echo [*] Copiando archivo exacto a la carpeta de backups...
    docker cp %CONTAINER_NAME%:%TEMP_FILE% "%BACKUP_DIR%%FILENAME%"
    
    echo [*] Limpiando archivos temporales del contenedor...
    docker exec %CONTAINER_NAME% rm %TEMP_FILE%
    
    echo.
    echo [OK] Backup generado con exito: %FILENAME%
) else (
    echo.
    echo [ERROR] Hubo un problema al generar el backup.
)

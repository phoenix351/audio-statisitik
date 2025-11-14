@echo off
setlocal ENABLEDELAYEDEXPANSION

rem =========================
rem Config (edit to taste)
rem =========================
set APP_NAME=laravel-app
set OUT_DIR=dist
set STAGE_DIR=.stage_release
set DATESTAMP=%DATE:~-4%%DATE:~4,2%%DATE:~7,2%-%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
set DATESTAMP=%DATESTAMP: =0%
set RELEASE_NAME=%APP_NAME%-release-%DATESTAMP%
set ZIP_FILE=%OUT_DIR%\%RELEASE_NAME%.zip
set DEPLOY_NOTE=%OUT_DIR%\%RELEASE_NAME%-DEPLOY.txt

rem =========================
rem Tooling checks
rem =========================
where php >nul 2>nul || (echo [ERROR] php not found in PATH & exit /b 1)
where composer >nul 2>nul || (echo [ERROR] composer not found in PATH & exit /b 1)
where npm >nul 2>nul || (echo [ERROR] npm not found in PATH & exit /b 1)
where powershell >nul 2>nul || (echo [ERROR] powershell not found in PATH & exit /b 1)

rem Optional tools
where git >nul 2>nul && (set HAS_GIT=1) || (set HAS_GIT=0)

rem =========================
rem Clean output/stage
rem =========================
if exist "%STAGE_DIR%" rmdir /s /q "%STAGE_DIR%"
if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"

rem =========================
rem PHP deps (prod)
rem =========================
echo.
echo === Composer install (prod) ===
@REM composer install --no-dev --prefer-dist --optimize-autoloader || (echo [ERROR] Composer failed & exit /b 1)

rem =========================
rem Frontend build (Vite)
rem =========================
echo.
echo === NPM build ===
@REM call npm ci || (echo [ERROR] npm ci failed & exit /b 1)
@REM call npm run build || (echo [ERROR] npm run build failed & exit /b 1)

rem =========================
rem Warm Laravel caches
rem =========================
echo.
echo === Artisan cache warmup ===
php artisan config:cache || (echo [ERROR] config:cache failed & exit /b 1)
php artisan route:cache  || (echo [ERROR] route:cache failed & exit /b 1)
php artisan view:cache   || (echo [ERROR] view:cache failed & exit /b 1)
php artisan event:cache  || (echo [WARN] event:cache not available? continuing)

rem =========================
rem Stage files we want
rem (whitelist approach = safer)
rem =========================
echo.
echo === Staging release files ===
mkdir "%STAGE_DIR%"
mkdir "%STAGE_DIR%\public"

rem Copy directories (mirrors content, excludes node_modules/tests/.git/storage)
robocopy app "%STAGE_DIR%\app" /MIR /XD .git
robocopy bootstrap "%STAGE_DIR%\bootstrap" /MIR
robocopy config "%STAGE_DIR%\config" /MIR
robocopy database "%STAGE_DIR%\database" /MIR
robocopy public "%STAGE_DIR%\public" /MIR
robocopy resources "%STAGE_DIR%\resources" /MIR
robocopy routes "%STAGE_DIR%\routes" /MIR
robocopy vendor "%STAGE_DIR%\vendor" /MIR
rem (OPTIONAL) If you keep lang in resources/lang, it’s already covered.

rem Copy root files
for %%F in (artisan composer.json composer.lock package.json vite.config.* .htaccess server.php) do (
  if exist "%%F" copy /y "%%F" "%STAGE_DIR%\"
)

rem =========================
rem Critical excludes
rem =========================
echo.
echo === Removing sensitive/unneeded items from stage ===
rem Never ship your real .env
if exist "%STAGE_DIR%\.env" del /f /q "%STAGE_DIR%\.env" >nul 2>nul

rem Don’t ship node_modules or tests (shouldn’t be staged via whitelist anyway)
if exist "%STAGE_DIR%\node_modules" rmdir /s /q "%STAGE_DIR%\node_modules"

rem Storage: typically NOT shipped; created on server and symlinked.
if exist "%STAGE_DIR%\storage" rmdir /s /q "%STAGE_DIR%\storage"

rem Clear any git folders if they slipped in
for /f "delims=" %%G in ('dir /ad /b /s "%STAGE_DIR%\.git" 2^>nul') do rmdir /s /q "%%G"

rem =========================
rem Add deploy manifest
rem =========================
echo.
echo === Writing deploy note ===
set GIT_HASH=unknown
if %HAS_GIT%==1 (
  for /f "usebackq delims=" %%H in (`git rev-parse --short HEAD 2^>nul`) do set GIT_HASH=%%H
)
(
  echo App: %APP_NAME%
  echo Release: %RELEASE_NAME%
  echo Date: %DATE% %TIME%
  echo Git: %GIT_HASH%
) > "%STAGE_DIR%\DEPLOY.txt"

rem Also copy to OUT_DIR for reference
copy /y "%STAGE_DIR%\DEPLOY.txt" "%DEPLOY_NOTE%" >nul

rem =========================
rem Zip the staged release
rem =========================
echo.
echo === Creating zip ===
if exist "%ZIP_FILE%" del /f /q "%ZIP_FILE%" >nul 2>nul

rem Use built-in PowerShell Compress-Archive
powershell -NoProfile -Command "Compress-Archive -Path '%STAGE_DIR%\*' -DestinationPath '%ZIP_FILE%' -Force" ^
  || (echo [ERROR] Zip failed & exit /b 1)

rem SHA256 checksum for integrity (optional, Windows 10+)
for /f "delims=" %%C in ('powershell -NoProfile -Command "(Get-FileHash -Algorithm SHA256 '%ZIP_FILE%').Hash"') do set SHA256=%%C
echo SHA256: %SHA256%>> "%DEPLOY_NOTE%"

echo.
echo === Done ===
echo Created: %ZIP_FILE%
echo Manifest: %DEPLOY_NOTE%
echo SHA256: %SHA256%
echo.
echo Upload the zip to /home/USER/releases/ via cPanel and follow your swap/migrate steps.
exit /b 0

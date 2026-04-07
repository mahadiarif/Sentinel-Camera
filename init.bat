@echo off
setlocal

echo [METRONET] INITIALIZING SYSTEM...

:: 1. Check for Composer
where composer >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [METRONET] Composer not found. Downloading composer.phar...
    powershell -Command "Invoke-WebRequest -Uri https://getcomposer.org/composer.phar -OutFile composer.phar"
    set COMPOSER_CMD=php composer.phar
) else (
    set COMPOSER_CMD=composer
)

:: 2. Install PHP Dependencies
echo [METRONET] Installing Laravel dependencies...
%COMPOSER_CMD% install

:: 3. Install Node Dependencies
echo [METRONET] Installing JS dependencies...
call npm.cmd install

:: 4. Setup Environment
if not exist .env (
    echo [METRONET] Creating .env file...
    copy .env.example .env
)

:: 5. Initialize Database (SQLite)
echo [METRONET] Configuring for SQLite...
powershell -Command "(Get-Content .env) -replace 'DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_DATABASE=metronet', 'DB_DATABASE=database/database.sqlite' | Set-Content .env"

if not exist database\database.sqlite (
    type nul > database\database.sqlite
)

:: 6. Setup Laravel
echo [METRONET] Initializing Laravel key and migrations...
php artisan key:generate
php artisan migrate:fresh --seed

:: 7. Setup Python Environment
echo [METRONET] Setting up Python Worker...
cd python_worker
if not exist venv (
    python -m venv venv
)
call venv\Scripts\activate.bat
pip install -r requirements.txt
if not exist .env (
    copy .env.example .env
)
cd ..

echo [METRONET] INITIALIZATION COMPLETE.
echo [METRONET] Use 'run.bat' to start the system.
pause

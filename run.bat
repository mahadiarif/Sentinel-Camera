@echo off
setlocal

:: Start Laravel Web Server
echo [METRONET] Starting Laravel Web Server...
start "METRONET Server" php artisan serve

:: Start Laravel Queue Worker
echo [METRONET] Starting Queue Worker (Training)...
start "METRONET Queue" php artisan queue:work --queue=training

:: Start Python AI Worker
echo [METRONET] Starting AI Surveillance Worker...
cd python_worker
call venv\Scripts\activate.bat
start "METRONET AI" python worker.py

echo [METRONET] ALL SYSTEMS INITIALIZED.
echo [METRONET] Access Dashboard: http://localhost:8000
pause

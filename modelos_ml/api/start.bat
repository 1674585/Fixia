@echo off
REM Lanza el microservicio ML de Fixia en Windows.
REM Asume que existe un venv en modelos_ml\api\.venv (ver README).

setlocal
cd /d "%~dp0"

if exist ".venv\Scripts\activate.bat" (
    call ".venv\Scripts\activate.bat"
)

python -m uvicorn modelos_ml.api.main:app ^
    --app-dir "..\.." ^
    --host 127.0.0.1 ^
    --port 8001

endlocal

@echo off
set PORT=8888
echo Starting WRLA Documentation server at http://localhost:%PORT%
echo Press Ctrl+C to stop.
echo.
start "" "http://localhost:%PORT%"
php -S localhost:%PORT% -t "%~dp0"

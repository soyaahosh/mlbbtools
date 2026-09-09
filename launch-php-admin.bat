@echo off
title Ketupat MLBB - PHP Admin Console Server
color 0A

echo ================================================================
echo           KETUPAT MLBB - SUPREME PHP ADMIN CONSOLE
echo ================================================================
echo.

set PHP_EXE=php
if exist "%~dp0php-runtime\php.exe" (
    set "PHP_EXE=%~dp0php-runtime\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
)

echo Checking PHP binary: %PHP_EXE%
"%PHP_EXE%" -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP executable was not found.
    echo Please install PHP or ensure php-runtime exists.
    pause
    exit /b 1
)

echo Starting Ketupat PHP Admin Web Server at http://localhost:8080 ...
echo Press Ctrl+C in this window to stop the server anytime.
echo.

start "" "http://localhost:8080"
"%PHP_EXE%" -S localhost:8080 -t "%~dp0admin-php"
pause

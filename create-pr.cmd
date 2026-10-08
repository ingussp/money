@echo off
setlocal
where git.exe >nul 2>nul
if errorlevel 1 (
    echo Git is required. Install Git for Windows, then run this script again.
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\create-pr.ps1" %*
set "money_pr_exit=%ERRORLEVEL%"
if not "%money_pr_exit%"=="0" echo PR creation stopped. Review the message above.
if "%~1"=="" pause
exit /b %money_pr_exit%

@echo off
setlocal
echo Money XAMPP deployment
echo Target: C:\xampp\htdocs\money
echo The existing Money project will be archived under C:\dev\money-backups.
echo Start Apache and MySQL in XAMPP before continuing.
pause
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-xampp.ps1"
if errorlevel 1 goto failed
C:\xampp\php\php.exe C:\xampp\htdocs\money\tools\install.php
if errorlevel 1 goto database
echo.
echo Ready: http://localhost/money/
echo Register an account to create your workspace.
pause
exit /b 0
:database
echo Files deployed. Start MySQL and check config.local.php, then run:
echo C:\xampp\php\php.exe C:\xampp\htdocs\money\tools\install.php
pause
exit /b 1
:failed
echo Deployment stopped. Review the error above before continuing.
pause
exit /b 1

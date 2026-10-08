param(
    [string]$Source = (Split-Path $PSScriptRoot -Parent),
    [string]$Target = 'C:\xampp\htdocs\money'
)
$ErrorActionPreference = 'Stop'
$sourcePath = (Resolve-Path -LiteralPath $Source).Path.TrimEnd('\')
$targetPath = [System.IO.Path]::GetFullPath($Target).TrimEnd('\')
if (-not (Test-Path -LiteralPath (Join-Path $sourcePath 'index.php')) -or -not (Test-Path -LiteralPath (Join-Path $sourcePath 'database\schema.sql'))) { throw 'The source must contain the Money application.' }
if ($sourcePath -eq $targetPath) { throw 'The source and replacement target must be different directories.' }
if ($targetPath -ne 'C:\xampp\htdocs\money') { throw 'Refusing to replace anything other than the exact Money project.' }
if ((Get-Item -LiteralPath $sourcePath).Attributes -band [System.IO.FileAttributes]::ReparsePoint) { throw 'The source must not be a junction or symlink.' }
if (Test-Path -LiteralPath $targetPath) {
    if ((Get-Item -LiteralPath $targetPath).Attributes -band [System.IO.FileAttributes]::ReparsePoint) { throw 'The target must not be a junction or symlink.' }
}
$parent = (Resolve-Path -LiteralPath 'C:\xampp\htdocs').Path.TrimEnd('\')
if ($parent -ne 'C:\xampp\htdocs') { throw 'Unexpected web root.' }
if ((Get-Item -LiteralPath $parent).Attributes -band [System.IO.FileAttributes]::ReparsePoint) { throw 'The web root must not be a junction.' }
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$stage = Join-Path $parent "money-staging-$stamp"
$backupRoot = 'C:\dev\money-backups'
$backup = Join-Path $backupRoot "money-before-$stamp"
if (Test-Path -LiteralPath $stage) { throw 'Staging target already exists.' }
if (Test-Path -LiteralPath $backup) { throw 'Backup target already exists.' }
Write-Output "Verified replacement target: $targetPath"
Write-Output "Staging new version: $stage"
New-Item -ItemType Directory -Path $stage | Out-Null
foreach ($name in @('app','assets','database','index.php','router.php','config.php','config.example.php','.htaccess','README.md')) {
    Copy-Item -LiteralPath (Join-Path $sourcePath $name) -Destination (Join-Path $stage $name) -Recurse -Force
}
New-Item -ItemType Directory -Path (Join-Path $stage 'storage') | Out-Null
Copy-Item -LiteralPath (Join-Path $sourcePath 'storage\.htaccess') -Destination (Join-Path $stage 'storage\.htaccess')
New-Item -ItemType Directory -Path (Join-Path $stage 'tools') | Out-Null
foreach ($name in @('install.php','seed-demo.php')) {
    Copy-Item -LiteralPath (Join-Path $sourcePath "tools\$name") -Destination (Join-Path $stage "tools\$name")
}
foreach ($name in @('index.php','app\bootstrap.php','database\schema.sql','assets\app.css','assets\hero.jpg','.htaccess')) {
    if (-not (Test-Path -LiteralPath (Join-Path $stage $name))) { throw "Incomplete staging copy: $name" }
    if ((Get-FileHash -LiteralPath (Join-Path $sourcePath $name)).Hash -ne (Get-FileHash -LiteralPath (Join-Path $stage $name)).Hash) { throw "Staging verification failed: $name" }
}
New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
if ((Resolve-Path -LiteralPath $backupRoot).Path.TrimEnd('\') -ne 'C:\dev\money-backups') { throw 'Unexpected backup directory.' }
if ((Get-Item -LiteralPath $backupRoot).Attributes -band [System.IO.FileAttributes]::ReparsePoint) { throw 'Backup directory must not be a junction.' }
if ((Resolve-Path -LiteralPath $stage).Path.TrimEnd('\') -ne [System.IO.Path]::GetFullPath($stage)) { throw 'Staging path mismatch.' }
if (Test-Path -LiteralPath $targetPath) {
    Write-Output "Archiving old project outside web root: $backup"
    Move-Item -LiteralPath $targetPath -Destination $backup
}
try {
    Move-Item -LiteralPath $stage -Destination $targetPath
} catch {
    if ((Test-Path -LiteralPath $backup) -and -not (Test-Path -LiteralPath $targetPath)) { Move-Item -LiteralPath $backup -Destination $targetPath }
    throw
}
Write-Output "Deployment complete: $targetPath"
Write-Output 'Next: start XAMPP MySQL and Apache, run tools\install.php with XAMPP PHP, and open http://localhost/money/'

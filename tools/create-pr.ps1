param([switch]$CheckOnly)
$ErrorActionPreference = 'Stop'
$repositoryPath = (Resolve-Path -LiteralPath (Split-Path $PSScriptRoot -Parent)).Path
$gitExecutable = (Get-Command git.exe -ErrorAction Stop).Source
$repository = 'ingussp/money'
$branch = 'feature/php-money-workspace'
$baseBranch = 'main'
$title = 'Replace legacy app with PHP/MySQL Money workspace'
$bodyPath = Join-Path $PSScriptRoot 'pr-description.md'

function Invoke-MoneyGit {
    param([string[]]$Arguments)
    $output = & $gitExecutable -c "safe.directory=$repositoryPath" -C $repositoryPath @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Git command failed: git $($Arguments -join ' ')." }
    return $output
}

try {
    $remote = Invoke-MoneyGit -Arguments @('remote','get-url','origin')
    if ($remote -notmatch '^(https://github\.com/ingussp/money(?:\.git)?/?|git@github\.com:ingussp/money(?:\.git)?)$') {
        throw 'The origin remote must point to https://github.com/ingussp/money.'
    }
    $currentBranch = Invoke-MoneyGit -Arguments @('branch','--show-current')
    if ($currentBranch -ne $branch) { throw "Switch to $branch before running this script." }
    $changes = Invoke-MoneyGit -Arguments @('status','--porcelain')
    if ($changes) { throw 'Commit or move aside your local changes before publishing the prepared PR.' }
    if (-not (Test-Path -LiteralPath $bodyPath)) { throw 'The prepared PR description is missing.' }
    $body = [System.IO.File]::ReadAllText($bodyPath, [System.Text.Encoding]::UTF8)
    $request = @{ title = $title; head = $branch; base = $baseBranch; body = $body } | ConvertTo-Json
    $payload = $request | ConvertFrom-Json
    foreach ($field in @('title','head','base','body')) {
        if ($payload.$field -isnot [string]) { throw "The pull request field '$field' must be plain text." }
    }
    if ($CheckOnly) {
        Write-Output "Repository: https://github.com/$repository"
        Write-Output "Prepared branch: $branch -> $baseBranch"
        Write-Output "Title: $title"
        Write-Output 'GitHub request validated: title, head, base and body are plain JSON strings.'
        Write-Output 'Local publication checks passed. No push or PR creation was performed.'
        exit 0
    }

    Write-Output "Fetching $repository and publishing $branch..."
    Invoke-MoneyGit -Arguments @('fetch','origin',$baseBranch) | Out-Host
    Invoke-MoneyGit -Arguments @('push','--set-upstream','origin',$branch) | Out-Host

    $gh = Get-Command gh.exe -ErrorAction SilentlyContinue
    if ($gh) {
        & $gh.Source auth status --hostname github.com *> $null
        if ($LASTEXITCODE -eq 0) {
            $existingJson = & $gh.Source pr list --repo $repository --head $branch --base $baseBranch --state open --json url
            if ($LASTEXITCODE -ne 0) { throw 'GitHub CLI could not check existing pull requests.' }
            $existing = @($existingJson | ConvertFrom-Json)
            if ($existing.Count -gt 0) { Write-Output "Pull request: $($existing[0].url)"; exit 0 }
            & $gh.Source pr create --repo $repository --base $baseBranch --head $branch --title $title --body-file $bodyPath
            if ($LASTEXITCODE -ne 0) { throw 'GitHub CLI could not create the pull request.' }
            exit 0
        }
    }

    $token = $env:GH_TOKEN
    if (-not $token) { $token = $env:GITHUB_TOKEN }
    if (-not $token) {
        $credentialRequest = "protocol=https`nhost=github.com`npath=ingussp/money.git`n`n"
        $credentialOutput = $credentialRequest | & $gitExecutable -c "safe.directory=$repositoryPath" -c credential.interactive=never -C $repositoryPath credential fill 2>$null
        if ($LASTEXITCODE -ne 0) { throw 'GitHub credentials were not available. Sign in through Git Credential Manager or gh auth login, then run this script again.' }
        foreach ($line in $credentialOutput) {
            if ($line.StartsWith('password=')) { $token = $line.Substring(9); break }
        }
        $credentialOutput = $null
    }
    if (-not $token) { throw 'No GitHub access token is available for PR creation. Sign in with Git Credential Manager or GitHub CLI.' }

    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $headers = @{
        Authorization = "Bearer $token"
        Accept = 'application/vnd.github+json'
        'X-GitHub-Api-Version' = '2026-03-10'
        'User-Agent' = 'Money-PR-Helper'
    }
    $head = [Uri]::EscapeDataString("ingussp:$branch")
    $apiUrl = "https://api.github.com/repos/$repository/pulls"
    $existing = Invoke-RestMethod -Method Get -Uri "${apiUrl}?state=open&head=$head&base=$baseBranch" -Headers $headers
    if ($existing -and $existing.Count -gt 0) { Write-Output "Pull request: $($existing[0].html_url)"; exit 0 }
    $pullRequest = Invoke-RestMethod -Method Post -Uri $apiUrl -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($request))
    Write-Output "Pull request created: $($pullRequest.html_url)"
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    $errorDetails = $_.ErrorDetails.Message
    if ($errorDetails) {
        try {
            $githubError = $errorDetails | ConvertFrom-Json
            if ($githubError.message) { Write-Host "GitHub: $($githubError.message)" -ForegroundColor Red }
            foreach ($detail in $githubError.errors) {
                if ($detail -is [string]) { Write-Host $detail -ForegroundColor Red }
                elseif ($detail.message) { Write-Host $detail.message -ForegroundColor Red }
                else { Write-Host "$($detail.resource) / $($detail.field): $($detail.code)" -ForegroundColor Red }
            }
        } catch {
            Write-Host 'The server response did not contain readable JSON error details.' -ForegroundColor Red
        }
    }
    exit 1
} finally {
    $token = $null
    $headers = $null
    $credentialOutput = $null
}

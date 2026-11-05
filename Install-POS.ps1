#Requires -Version 5.1
#Requires -RunAsAdministrator

<#
.SYNOPSIS
    Installs the Point of Sale local Node.js print agent for WampServer
    on Windows with a USB printer installed as a Windows printer queue.

.DESCRIPTION
    Run this script from the root of the cloned pointofsale repository.

    - Installs Node.js LTS through winget if Node.js 18+ is unavailable.
    - Detects Windows printer queues and lets the operator select one.
    - Creates POSPrinter\config.json from config.example.json.
    - Generates a cryptographically random API token.
    - Updates the three existing print settings in root config.php.
    - Installs npm dependencies and runs the repository tests.
    - Registers a per-user logon task and starts the print agent.
    - Checks the agent's /health endpoint.

.NOTES
    Do not commit POSPrinter\config.json or the generated API token.
    The system-queue adapter requires a RAW/ESC-POS-compatible Windows queue.
    A successful spooler response does not prove that paper physically printed.
#>

[CmdletBinding()]
param(
    [string]$ProjectRoot = $PSScriptRoot,
    [switch]$SkipTests,
    [switch]$NoScheduledTask
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ServiceHost = '127.0.0.1'
$ServicePort = 3000
$PrinterId = 'usb-receipt'
$TaskName = 'POS Node Print Agent'
$Origins = @('http://localhost:90', 'http://127.0.0.1:90')
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-Section {
    param([Parameter(Mandatory)][string]$Message)
    Write-Host ''
    Write-Host ('=' * 64) -ForegroundColor Cyan
    Write-Host " $Message" -ForegroundColor Cyan
    Write-Host ('=' * 64) -ForegroundColor Cyan
}

function Write-Ok {
    param([Parameter(Mandatory)][string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-Warn {
    param([Parameter(Mandatory)][string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Backup-File {
    param([Parameter(Mandatory)][string]$Path)

    if (Test-Path -LiteralPath $Path) {
        $backupPath = '{0}.backup-{1}' -f $Path, (Get-Date -Format 'yyyyMMdd-HHmmss')
        Copy-Item -LiteralPath $Path -Destination $backupPath
        Write-Ok "Backup created: $backupPath"
    }
}

function New-ApiToken {
    $bytes = New-Object byte[] 32
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $rng.GetBytes($bytes)
    }
    finally {
        $rng.Dispose()
    }
    return ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
}

function Get-NodeRuntime {
    $node = Get-Command node.exe -ErrorAction SilentlyContinue
    if (-not $node) {
        return $null
    }

    $nodeExe = $node.Source
    $npmCmd = Join-Path (Split-Path -Parent $nodeExe) 'npm.cmd'

    if (-not (Test-Path -LiteralPath $npmCmd)) {
        throw "npm.cmd was not found beside Node.js: $npmCmd"
    }

    $versionText = (& $nodeExe --version).Trim()
    if ($LASTEXITCODE -ne 0 -or $versionText -notmatch '^v?(\d+)\.') {
        throw "Could not determine the installed Node.js version: $versionText"
    }

    $major = [int]$Matches[1]
    if ($major -lt 18) {
        throw "Node.js 18 or later is required. Installed version: $versionText"
    }

    return [PSCustomObject]@{
        NodeExe = $nodeExe
        NpmCmd = $npmCmd
        Version = $versionText
    }
}

function Set-PhpAssignment {
    param(
        [Parameter(Mandatory)][string]$Text,
        [Parameter(Mandatory)][string]$Variable,
        [Parameter(Mandatory)][string]$Value
    )

    $pattern = '(?m)^([ \t]*)\$' + [regex]::Escape($Variable) + "[ \t]*=[ \t]*'[^']*'[ \t]*;"
    $found = [regex]::Matches($Text, $pattern)

    if ($found.Count -ne 1) {
        throw "Expected exactly one existing PHP assignment for variable $Variable; found $($found.Count). No PHP changes have been written."
    }

    $evaluator = [System.Text.RegularExpressions.MatchEvaluator]{
        param($match)
        return $match.Groups[1].Value + '$' + $Variable + " = '" + $Value + "';"
    }

    return [regex]::Replace($Text, $pattern, $evaluator, 1)
}

# -------------------------------------------------------------------
# Validate repository layout
# -------------------------------------------------------------------

Write-Section 'VALIDATING POINT OF SALE REPOSITORY'

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    throw 'ProjectRoot is empty. Run this script from inside the cloned repository.'
}

$ProjectRoot = [System.IO.Path]::GetFullPath($ProjectRoot)
$AgentDir = Join-Path $ProjectRoot 'POSPrinter'
$TemplatePath = Join-Path $AgentDir 'config.example.json'
$AgentConfigPath = Join-Path $AgentDir 'config.json'
$PackagePath = Join-Path $AgentDir 'package.json'
$PhpConfigPath = Join-Path $ProjectRoot 'config.php'

foreach ($requiredPath in @($AgentDir, $TemplatePath, $PackagePath, $PhpConfigPath)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required repository file or directory was not found: $requiredPath"
    }
}

Write-Ok "Repository root: $ProjectRoot"
Write-Ok 'POSPrinter files and root config.php found.'

# -------------------------------------------------------------------
# Install/check Node.js
# -------------------------------------------------------------------

Write-Section 'CHECKING NODE.JS'

$runtime = Get-NodeRuntime

if (-not $runtime) {
    $winget = Get-Command winget.exe -ErrorAction SilentlyContinue
    if (-not $winget) {
        throw 'Node.js is missing and winget is unavailable. Install Node.js LTS from https://nodejs.org/en/download, reopen PowerShell, then rerun this script.'
    }

    Write-Host 'Installing Node.js LTS using winget...' -ForegroundColor Yellow
    & $winget.Source install --id OpenJS.NodeJS.LTS -e --silent --accept-package-agreements --accept-source-agreements

    if ($LASTEXITCODE -ne 0) {
        throw "winget failed to install Node.js. Exit code: $LASTEXITCODE"
    }

    $nodeInstallPath = Join-Path $env:ProgramFiles 'nodejs'
    if (Test-Path -LiteralPath $nodeInstallPath) {
        $env:Path = "$nodeInstallPath;$env:Path"
    }

    $runtime = Get-NodeRuntime
    if (-not $runtime) {
        throw 'Node.js installation was attempted, but this PowerShell session cannot locate node.exe. Close PowerShell, reopen it, and rerun this script.'
    }
}

Write-Ok "Node.js $($runtime.Version)"
Write-Ok "npm: $($runtime.NpmCmd)"

# -------------------------------------------------------------------
# Detect and select the installed printer queue
# -------------------------------------------------------------------

Write-Section 'SELECTING USB PRINTER QUEUE'

$printers = @(Get-Printer | Sort-Object Name)
if ($printers.Count -eq 0) {
    throw 'Windows has no installed printer queues. Install the USB printer driver, confirm it appears in Windows Printers & scanners, and rerun this script.'
}

for ($i = 0; $i -lt $printers.Count; $i++) {
    $p = $printers[$i]
    Write-Host ('[{0}] {1} | Driver: {2} | Port: {3}' -f ($i + 1), $p.Name, $p.DriverName, $p.PortName)
}

do {
    $answer = Read-Host 'Enter the number of the USB receipt printer'
    $selectedIndex = 0
    $valid = [int]::TryParse($answer, [ref]$selectedIndex)
    if ($valid) {
        $valid = ($selectedIndex -ge 1 -and $selectedIndex -le $printers.Count)
    }
    if (-not $valid) {
        Write-Warn 'Enter a valid number from the printer list.'
    }
} while (-not $valid)

$selectedPrinter = $printers[$selectedIndex - 1]
$printerName = [string]$selectedPrinter.Name

Write-Ok "Selected Windows queue: $printerName"
Write-Warn 'The Windows queue must pass RAW ESC/POS bytes unchanged. Some vendor drivers modify receipt data.'

# -------------------------------------------------------------------
# Read template and prepare local Node configuration
# -------------------------------------------------------------------

Write-Section 'CREATING LOCAL NODE.JS CONFIGURATION'

try {
    $nodeConfig = Get-Content -LiteralPath $TemplatePath -Raw | ConvertFrom-Json
}
catch {
    throw "Could not parse POSPrinter\config.example.json: $($_.Exception.Message)"
}

foreach ($requiredProperty in @('serviceHost', 'servicePort', 'apiToken', 'defaultPrinterId', 'allowedOrigins', 'printers', 'maxRequestBytes')) {
    if ($null -eq $nodeConfig.PSObject.Properties[$requiredProperty]) {
        throw "The repository config.example.json is missing expected property '$requiredProperty'. Installer stopped without writing configuration."
    }
}

$token = New-ApiToken

$nodeConfig.serviceHost = $ServiceHost
$nodeConfig.servicePort = $ServicePort
$nodeConfig.apiToken = $token
$nodeConfig.defaultPrinterId = $PrinterId
$nodeConfig.allowedOrigins = $Origins
$nodeConfig.printers = @(
    [PSCustomObject]@{
        id = $PrinterId
        name = $printerName
        adapter = 'system-queue'
        queue = $printerName
        timeoutMs = 20000
        cashDrawer = $false
    }
)

$json = $nodeConfig | ConvertTo-Json -Depth 30

if (Test-Path -LiteralPath $AgentConfigPath) {
    Backup-File -Path $AgentConfigPath
}

[System.IO.File]::WriteAllText($AgentConfigPath, $json, $Utf8NoBom)
Write-Ok 'POSPrinter\config.json created from the repository template.'

# -------------------------------------------------------------------
# Install npm packages and run the project's tests
# -------------------------------------------------------------------

Write-Section 'INSTALLING NODE.JS DEPENDENCIES'

Push-Location $AgentDir
try {
    & $runtime.NpmCmd install
    if ($LASTEXITCODE -ne 0) {
        throw "npm install failed with exit code $LASTEXITCODE."
    }
    Write-Ok 'npm dependencies installed.'

    if (-not $SkipTests) {
        Write-Section 'RUNNING REPOSITORY TESTS'
        & $runtime.NpmCmd test
        if ($LASTEXITCODE -ne 0) {
            throw "The repository test command failed with exit code $LASTEXITCODE. PHP configuration has not yet been changed."
        }
        Write-Ok 'Repository tests passed.'
    }
    else {
        Write-Warn 'Tests were skipped because -SkipTests was specified.'
    }
}
finally {
    Pop-Location
}

# -------------------------------------------------------------------
# Update the existing PHP print settings, with a backup
# -------------------------------------------------------------------

Write-Section 'UPDATING PHP PRINT CONFIGURATION'

$phpText = [System.IO.File]::ReadAllText($PhpConfigPath)
$phpUpdated = $phpText

$phpUpdated = Set-PhpAssignment -Text $phpUpdated -Variable 'POSPrintUrl' -Value 'http://127.0.0.1:3000/print-receipt'
$phpUpdated = Set-PhpAssignment -Text $phpUpdated -Variable 'POSPrintToken' -Value $token
$phpUpdated = Set-PhpAssignment -Text $phpUpdated -Variable 'POSPrinterId' -Value $PrinterId

if ($phpUpdated -ne $phpText) {
    Backup-File -Path $PhpConfigPath
    [System.IO.File]::WriteAllText($PhpConfigPath, $phpUpdated, $Utf8NoBom)
    Write-Ok 'Updated POSPrintUrl, POSPrintToken and POSPrinterId in config.php.'
}
else {
    Write-Ok 'PHP print settings already match the generated configuration.'
}

# -------------------------------------------------------------------
# Register per-user logon task
# -------------------------------------------------------------------

if (-not $NoScheduledTask) {
    Write-Section 'CONFIGURING AUTOMATIC STARTUP'

    $action = New-ScheduledTaskAction -Execute $runtime.NodeExe -Argument 'server.js' -WorkingDirectory $AgentDir
    $trigger = New-ScheduledTaskTrigger -AtLogOn -User "$env:USERDOMAIN\$env:USERNAME"
    $principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit ([TimeSpan]::Zero) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)

    $task = New-ScheduledTask -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Local Node.js print agent for the Point of Sale application'

    Register-ScheduledTask -TaskName $TaskName -InputObject $task -Force | Out-Null
    Write-Ok "Scheduled task '$TaskName' registered for the current Windows user."
}
else {
    Write-Warn 'Automatic startup task was skipped because -NoScheduledTask was specified.'
}

# -------------------------------------------------------------------
# Start agent and verify health
# -------------------------------------------------------------------

Write-Section 'STARTING AND VERIFYING PRINT AGENT'

if (-not $NoScheduledTask) {
    Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
}

$portOwner = @(Get-NetTCPConnection -LocalPort $ServicePort -State Listen -ErrorAction SilentlyContinue)
if ($portOwner.Count -gt 0) {
    $pids = @($portOwner | Select-Object -ExpandProperty OwningProcess -Unique)
    foreach ($pidValue in $pids) {
        $processInfo = Get-CimInstance Win32_Process -Filter "ProcessId = $pidValue" -ErrorAction SilentlyContinue
        if ($processInfo) {
            Write-Warn "Port $ServicePort is already occupied by PID $pidValue ($($processInfo.Name)). The installer will not terminate it."
        }
    }
    throw "Port $ServicePort is already in use. Identify the existing process before starting the POS print agent."
}

if (-not $NoScheduledTask) {
    Start-ScheduledTask -TaskName $TaskName
}
else {
    Start-Process -FilePath $runtime.NodeExe -ArgumentList 'server.js' -WorkingDirectory $AgentDir -WindowStyle Minimized
}

$healthUrl = 'http://' + $ServiceHost + ':' + $ServicePort + '/health'
$health = $null
$healthy = $false

for ($attempt = 1; $attempt -le 20; $attempt++) {
    Start-Sleep -Seconds 2
    try {
        $health = Invoke-RestMethod -Uri $healthUrl -Method Get -TimeoutSec 5
        $healthy = $true
        break
    }
    catch {
        Write-Host "Waiting for print agent ($attempt/20)..."
    }
}

if (-not $healthy) {
    if (-not $NoScheduledTask) {
        $taskState = (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue).State
        Write-Warn "Scheduled task state: $taskState"
    }
    throw "The print agent did not respond at $healthUrl. Run 'node server.js' from '$AgentDir' to inspect its startup error."
}

Write-Ok "Print agent health endpoint responded at $healthUrl"

# -------------------------------------------------------------------
# Final report
# -------------------------------------------------------------------

Write-Section 'INSTALLATION CONFIGURED'

Write-Host "Repository       : $ProjectRoot"
Write-Host "Print agent      : $AgentDir"
Write-Host "Node.js          : $($runtime.Version)"
Write-Host 'POS website      : http://localhost:90'
Write-Host "Print service    : $healthUrl"
Write-Host "Printer queue    : $printerName"
Write-Host "Printer profile  : $PrinterId"
Write-Host "Startup task     : $(if ($NoScheduledTask) { 'Not configured' } else { $TaskName })"
Write-Host ''
Write-Host 'Generated API token (store securely):' -ForegroundColor Yellow
Write-Host $token -ForegroundColor Yellow
Write-Host ''
Write-Warn 'Do not commit POSPrinter\config.json or the generated API token to Git.'
Write-Warn 'The health check confirms the service is responding, not that the printer physically printed.'
Write-Host ''
Write-Host 'NEXT: Open http://localhost:90, perform a test checkout and verify the printed receipt.' -ForegroundColor Cyan
Write-Host 'A physical receipt test is still required before production use.' -ForegroundColor Cyan
Write-Host ''
Write-Host 'INSTALLATION CONFIGURATION COMPLETED.' -ForegroundColor Green

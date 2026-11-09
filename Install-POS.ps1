#Requires -Version 5.1
#Requires -RunAsAdministrator

[CmdletBinding()]
param(
    [string]$ProjectRoot = $PSScriptRoot,
    [switch]$SkipTests
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$ProjectRoot = [System.IO.Path]::GetFullPath($ProjectRoot)
$AgentDir = Join-Path $ProjectRoot 'POSPrinter'
$TemplatePath = Join-Path $AgentDir 'config.example.json'
$ConfigPath = Join-Path $AgentDir 'config.json'
$TaskName = 'POS Node Print Agent'
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)

foreach ($path in @($AgentDir, $TemplatePath, (Join-Path $AgentDir 'package.json'))) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Required file not found: $path" }
}

Write-Host ''
Write-Host 'SMARTERNOW POS PRINTER INSTALLER' -ForegroundColor Cyan
Write-Host 'This setup connects your local printer to the live POS using outbound HTTPS.'
Write-Host 'No VPN, port forwarding, X-Print-Token or manual security key is required.'
Write-Host ''

$serverBase = (Read-Host 'Enter the live POS website address (example: https://your-domain.com)').Trim().TrimEnd('/')
if ($serverBase -notmatch '^https://[^/]+(?:/.*)?$') {
    throw 'Enter the complete HTTPS address of the live POS website.'
}
$serverUrl = $serverBase + '/POSajax/print-agent.php'

$node = Get-Command node.exe -ErrorAction SilentlyContinue
if (-not $node) {
    $winget = Get-Command winget.exe -ErrorAction SilentlyContinue
    if (-not $winget) { throw 'Node.js is missing and winget is unavailable. Install Node.js LTS, then rerun this installer.' }
    & $winget.Source install --id OpenJS.NodeJS.LTS -e --silent --accept-package-agreements --accept-source-agreements
    if ($LASTEXITCODE -ne 0) { throw 'Node.js LTS installation failed.' }
    $nodeInstallPath = Join-Path $env:ProgramFiles 'nodejs'
    if (Test-Path -LiteralPath $nodeInstallPath) { $env:Path = $nodeInstallPath + ';' + $env:Path }
    $node = Get-Command node.exe -ErrorAction SilentlyContinue
}
if (-not $node) { throw 'Node.js was installed but is not available in this PowerShell session. Reopen PowerShell and rerun.' }
$nodeVersion = (& $node.Source --version).Trim()
if ($nodeVersion -notmatch '^v(\d+)\.' -or [int]$Matches[1] -lt 18) {
    throw "Node.js 18 or later is required. Found $nodeVersion."
}
$npm = Join-Path (Split-Path -Parent $node.Source) 'npm.cmd'
if (-not (Test-Path -LiteralPath $npm)) { throw 'npm.cmd was not found beside Node.js.' }

$printers = @(Get-Printer | Sort-Object Name)
if ($printers.Count -eq 0) { throw 'Windows has no installed printers. Install the receipt printer driver and rerun.' }
Write-Host ''
Write-Host 'Select the Windows printer queue that prints receipts:' -ForegroundColor Cyan
for ($i = 0; $i -lt $printers.Count; $i++) {
    Write-Host ('[{0}] {1} | {2}' -f ($i + 1), $printers[$i].Name, $printers[$i].DriverName)
}
do {
    $answer = Read-Host 'Printer number'
    $selected = 0
    $valid = [int]::TryParse($answer, [ref]$selected) -and $selected -ge 1 -and $selected -le $printers.Count
    if (-not $valid) { Write-Host 'Choose a number from the list.' -ForegroundColor Yellow }
} while (-not $valid)
$printerName = [string]$printers[$selected - 1].Name

$config = Get-Content -LiteralPath $TemplatePath -Raw | ConvertFrom-Json
$config.serverUrl = $serverUrl
$config.defaultPrinterId = 'usb-receipt'
$config.printers = @([PSCustomObject]@{
    id = 'usb-receipt'
    name = $printerName
    adapter = 'system-queue'
    queue = $printerName
    timeoutMs = 20000
    cashDrawer = $false
})
if (Test-Path -LiteralPath $ConfigPath) {
    Copy-Item -LiteralPath $ConfigPath -Destination ($ConfigPath + '.backup-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
}
[System.IO.File]::WriteAllText($ConfigPath, ($config | ConvertTo-Json -Depth 20), $Utf8NoBom)

Push-Location $AgentDir
try {
    & $npm install
    if ($LASTEXITCODE -ne 0) { throw 'npm install failed.' }
    if (-not $SkipTests) {
        & $npm test
        if ($LASTEXITCODE -ne 0) { throw 'POS print-agent tests failed.' }
    }
}
finally { Pop-Location }

$action = New-ScheduledTaskAction -Execute $node.Source -Argument 'server.js' -WorkingDirectory $AgentDir
$trigger = New-ScheduledTaskTrigger -AtLogOn -User "$env:USERDOMAIN\$env:USERNAME"
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit ([TimeSpan]::Zero) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
$task = New-ScheduledTask -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Local SmartERPlims POS print agent'
Register-ScheduledTask -TaskName $TaskName -InputObject $task -Force | Out-Null
Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
Start-ScheduledTask -TaskName $TaskName

Write-Host ''
Write-Host 'INSTALLATION CONFIGURED' -ForegroundColor Green
Write-Host "Live POS: $serverUrl"
Write-Host "Printer:  $printerName"
Write-Host 'The agent starts automatically when this Windows user signs in.'
Write-Host 'The printer identity is generated automatically and stored locally.'
Write-Host 'Apply SQL/pos_print_queue.sql to the POS company database before testing checkout.'
Write-Host 'A test print is still required to verify the physical printer and paper output.'

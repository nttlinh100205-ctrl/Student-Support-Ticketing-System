param(
    [ValidateSet('Run', 'Check')]
    [string]$Action = 'Run',
    [string]$ApacheRoot = 'E:\Xampp\apache'
)

$ErrorActionPreference = 'Stop'
$gatewayRoot = $PSScriptRoot.Replace('\', '/')
$apacheDirectory = (Resolve-Path -LiteralPath $ApacheRoot).Path.Replace('\', '/')
$httpd = Join-Path $apacheDirectory 'bin/httpd.exe'
if (-not (Test-Path -LiteralPath $httpd)) {
    throw "Apache executable not found: $httpd"
}
New-Item -ItemType Directory -Path (Join-Path $PSScriptRoot 'runtime') -Force | Out-Null
$httpdArguments = @(
    '-d', $apacheDirectory,
    '-f', "$gatewayRoot/httpd.conf"
)
$previousApacheRoot = $env:APACHE_ROOT
$previousGatewayRoot = $env:GATEWAY_ROOT
try {
    $env:APACHE_ROOT = $apacheDirectory
    $env:GATEWAY_ROOT = $gatewayRoot
    & $httpd @httpdArguments -t
    if ($LASTEXITCODE -ne 0) { throw 'Gateway configuration check failed.' }
    if ($Action -eq 'Check') { return }

    Write-Host 'API gateway: http://localhost:8000. Keep this terminal open; Ctrl+C stops it.'
    Write-Host 'Start the five Laravel services separately on ports 8001 through 8005.'
    & $httpd @httpdArguments
    if ($LASTEXITCODE -ne 0) { throw "Gateway exited with code $LASTEXITCODE." }
} finally {
    $env:APACHE_ROOT = $previousApacheRoot
    $env:GATEWAY_ROOT = $previousGatewayRoot
}

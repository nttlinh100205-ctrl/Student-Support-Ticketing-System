param(
    [string]$Php = 'E:\Xampp\php\php.exe',
    [string]$ReportPhp = '',
    [string]$ApacheRoot = 'E:\Xampp\apache',
    [switch]$SkipReports,
    [switch]$Check
)

$ErrorActionPreference = 'Stop'
if (-not $ReportPhp) { $ReportPhp = $Php }
$services = @(
    @{ Name = 'accounts'; Directory = 'module1-account-service'; Port = 8001; Php = $Php },
    @{ Name = 'catalog'; Directory = 'module2-catalog-service'; Port = 8002; Php = $Php },
    @{ Name = 'requests'; Directory = 'module3-request-service'; Port = 8003; Php = $Php },
    @{ Name = 'news'; Directory = 'module4-discussions-and-documents'; Port = 8004; Php = $Php }
)
if (-not $SkipReports) {
    $services += @{ Name = 'reports'; Directory = 'report-service'; Port = 8005; Php = $ReportPhp }
}

# Check everything before launching; never create databases or overwrite .env.
$problems = @()
foreach ($service in $services) {
    $directory = Join-Path $PSScriptRoot $service.Directory
    foreach ($relative in @('.env', 'vendor/autoload.php')) {
        if (-not (Test-Path -LiteralPath (Join-Path $directory $relative))) {
            $problems += "$($service.Name): missing $relative in $directory"
        }
    }
    if (-not (Test-Path -LiteralPath $service.Php)) {
        $problems += "$($service.Name): PHP executable not found: $($service.Php)"
        continue
    }
    $versionOutput = & $service.Php -r 'echo PHP_VERSION_ID;'
    if ($LASTEXITCODE -ne 0 -or "$versionOutput" -notmatch '^\d+$') {
        $problems += "$($service.Name): cannot determine PHP version."
        continue
    }
    $minimum = 80200
    if ([int]$versionOutput -lt $minimum) {
        $problems += "$($service.Name): PHP too old. All services require PHP >= 8.2."
    }
}
$reusedPorts = @{}
foreach ($port in (@(8000) + @($services | ForEach-Object { $_.Port }))) {
    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $port)
    try { $listener.Start() }
    catch {
        $expectedDirectory = if ($port -eq 8000) { Join-Path $PSScriptRoot 'gateway' } else { Join-Path $PSScriptRoot ($services | Where-Object { $_.Port -eq $port }).Directory }
        $connections = @(Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
        $matches = $connections.Count -gt 0
        foreach ($connection in $connections) {
            $owner = Get-CimInstance Win32_Process -Filter "ProcessId=$($connection.OwningProcess)" -ErrorAction SilentlyContinue
            $command = [string]$owner.CommandLine
            if (-not $command.Replace('\', '/').Contains($expectedDirectory.Replace('\', '/') + '/')) { $matches = $false }
        }
        if ($matches) { $reusedPorts[$port] = $true; Write-Host "Reusing this project's existing service on port $port." }
        else { $problems += "Port $port belongs to an unknown process. Stop it or free the port before starting." }
    }
    finally { $listener.Stop() }
}
if (-not (Test-Path -LiteralPath (Join-Path $ApacheRoot 'bin/httpd.exe'))) {
    $problems += "Apache not found in $ApacheRoot"
}
if ($problems.Count) { throw ($problems -join [Environment]::NewLine) }
& (Join-Path $PSScriptRoot 'gateway/gateway.ps1') -Action Check -ApacheRoot $ApacheRoot
if ($Check) { Write-Host 'Preflight passed. No service was started.'; return }

$runtime = Join-Path $PSScriptRoot 'gateway/runtime'
New-Item -ItemType Directory -Path $runtime -Force | Out-Null
$started = [System.Collections.Generic.List[object]]::new()
$oldApacheRoot = $env:APACHE_ROOT
$oldGatewayRoot = $env:GATEWAY_ROOT
try {
    foreach ($service in $services) {
        if ($reusedPorts.ContainsKey($service.Port)) { continue }
        $process = Start-Process -FilePath $service.Php -ArgumentList @(
            'artisan', 'serve', '--host=127.0.0.1', "--port=$($service.Port)", '--tries=1', '--no-reload'
        ) -WorkingDirectory (Join-Path $PSScriptRoot $service.Directory) -WindowStyle Hidden -PassThru `
          -RedirectStandardOutput (Join-Path $runtime "$($service.Name).out.log") `
          -RedirectStandardError (Join-Path $runtime "$($service.Name).err.log")
        $started.Add(@{ Name = $service.Name; Process = $process; Port = $service.Port })
    }
    $env:APACHE_ROOT = $ApacheRoot.Replace('\', '/')
    $env:GATEWAY_ROOT = (Join-Path $PSScriptRoot 'gateway').Replace('\', '/')
    $config = Join-Path $PSScriptRoot 'gateway/httpd.conf'
    # -X keeps Apache in one process so this script can reliably stop it.
    if (-not $reusedPorts.ContainsKey(8000)) {
    $apache = Start-Process -FilePath (Join-Path $ApacheRoot 'bin/httpd.exe') `
        -ArgumentList @('-X', '-f', ('"' + $config + '"')) -WindowStyle Hidden -PassThru `
        -RedirectStandardOutput (Join-Path $runtime 'gateway.out.log') `
        -RedirectStandardError (Join-Path $runtime 'gateway.err.log')
    $started.Add(@{ Name = 'gateway'; Process = $apache; Port = 8000 })
    }

    foreach ($entry in $started) {
        $ready = $false
        for ($attempt = 0; $attempt -lt 60; $attempt++) {
            if ($entry.Process.HasExited) { throw "$($entry.Name) exited. See logs in $runtime" }
            $client = [System.Net.Sockets.TcpClient]::new()
            try { $client.Connect('127.0.0.1', $entry.Port); $ready = $true }
            catch { Start-Sleep -Milliseconds 250 }
            finally { $client.Dispose() }
            if ($ready) { break }
        }
        if (-not $ready) { throw "$($entry.Name) did not listen on port $($entry.Port). See $runtime" }
        Write-Host "$($entry.Name): listening on $($entry.Port)"
    }
    Write-Host 'Open http://localhost:8000. Keep this terminal open; Ctrl+C stops all processes started here.'
    Write-Host 'Listening checks do not verify database connectivity. Logs: gateway/runtime/'
    Write-Host 'Existing reused services remain running when you press Ctrl+C.'
    if ($SkipReports) { Write-Warning 'Report service is disabled for this run.' }
    while ($true) {
        foreach ($entry in $started) {
            if ($entry.Process.HasExited) { throw "$($entry.Name) stopped. See logs in $runtime" }
        }
        Start-Sleep -Seconds 1
    }
} finally {
    foreach ($entry in $started) {
        if (-not $entry.Process.HasExited) {
            # Stop only the process trees created by this invocation.
            & taskkill.exe /PID $entry.Process.Id /T /F 2>&1 | Out-Null
        }
    }
    $env:APACHE_ROOT = $oldApacheRoot
    $env:GATEWAY_ROOT = $oldGatewayRoot
}

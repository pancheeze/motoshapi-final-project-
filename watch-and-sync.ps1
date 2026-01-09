# File watcher script to automatically update schema.sql when PHP files change
$folder = "C:\xampp\htdocs\trialprograms"
$filter = "*.php"
$lastRun = Get-Date
$cooldown = 5 # seconds between runs to prevent multiple updates

$action = {
    $currentTime = Get-Date
    $timeSinceLastRun = ($currentTime - $lastRun).TotalSeconds
    
    if ($timeSinceLastRun -ge $cooldown) {
        Write-Host "`n[$(Get-Date -Format 'HH:mm:ss')] Change detected. Updating schema.sql with structure and data..."
        & "C:\xampp\php\php.exe" "$folder\database\sync_database.php"
        $script:lastRun = $currentTime
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Schema update completed.`n"
    }
}

# Create file system watcher
$fsw = New-Object IO.FileSystemWatcher $folder, $filter -Property @{
    IncludeSubdirectories = $true
    EnableRaisingEvents = $true
}

# Register events
Register-ObjectEvent $fsw Changed -Action $action
Register-ObjectEvent $fsw Created -Action $action
Register-ObjectEvent $fsw Deleted -Action $action
Register-ObjectEvent $fsw Renamed -Action $action

Write-Host "`n=== Schema Sync Watcher Started ==="
Write-Host "Watching for changes in: $folder"
Write-Host "Press Ctrl+C to stop watching`n"

try {
    while ($true) { Start-Sleep -Seconds 1 }
} finally {
    # Cleanup
    $fsw.EnableRaisingEvents = $false
    $fsw.Dispose()
    Write-Host "`n=== Schema Sync Watcher Stopped ==="
} 
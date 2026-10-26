$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$pluginRoot = Split-Path -Parent $PSScriptRoot
$version = (Get-Content -LiteralPath (Join-Path $pluginRoot 'package.json') -Raw | ConvertFrom-Json).version
$artifacts = Join-Path $pluginRoot 'artifacts'
New-Item -ItemType Directory -Path $artifacts -Force | Out-Null
$zipPath = Join-Path $artifacts ('aremedia-gutenberg-' + $version + '.zip')
$files = @('aremedia-gutenberg.php', 'README.md', 'LICENSE') | ForEach-Object { Join-Path $pluginRoot $_ }
$files += Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'build'), (Join-Path $pluginRoot 'docs') -Recurse -File | ForEach-Object { $_.FullName }

$stream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
$zip = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in $files) {
        $relative = $file.Substring($pluginRoot.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file,
            ('aremedia-gutenberg/' + $relative), [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $zip.Dispose()
    $stream.Dispose()
}

$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $entries = @($archive.Entries | ForEach-Object { $_.FullName })
    foreach ($file in @('aremedia-gutenberg.php', 'README.md', 'build/block.json', 'build/index.js',
        'build/index.asset.php', 'build/render.php', 'build/style.css', 'build/fonts/jost-normal.woff2',
        'build/fonts/jost-italic.woff2', 'build/fonts/OFL.txt', 'docs/validation.md')) {
        if ($entries -notcontains ('aremedia-gutenberg/' + $file)) { throw ('ZIP missing ' + $file) }
    }
    if ($entries | Where-Object { $_ -match '(^|/)(node_modules|vendor|\.git|wp-config\.php)(/|$)' }) {
        throw 'ZIP contains excluded development or site files.'
    }
    Write-Output ('Verified installable ZIP: ' + $entries.Count + ' files, ' + (Get-Item -LiteralPath $zipPath).Length + ' bytes.')
} finally {
    $archive.Dispose()
}
Get-FileHash -LiteralPath $zipPath -Algorithm SHA256 | ForEach-Object { Write-Output ('SHA256: ' + $_.Hash) }

param(
    [string]$Php = 'php',
    [string]$Edge = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'
)

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$artifacts = Join-Path $pluginRoot 'artifacts'
New-Item -ItemType Directory -Path $artifacts -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $pluginRoot 'docs/images') -Force | Out-Null

foreach ($size in @(@{Name='desktop';Width=1280;Height=1600}, @{Name='mobile';Width=390;Height=1600}, @{Name='reference';Width=1720;Height=2286}, @{Name='typography';Width=700;Height=477})) {
    $fixture = Join-Path $artifacts ($size.Name + '-fixture.html')
    $fixtureArgs = @((Join-Path $pluginRoot 'tests/preview.php'))
    if ($size.Name -eq 'mobile') { $fixtureArgs += '--mobile' }
    if ($size.Name -eq 'reference') { $fixtureArgs += '--reference' }
    if ($size.Name -eq 'typography') { $fixtureArgs += '--typography' }
    & $Php @fixtureArgs | Set-Content -LiteralPath $fixture -Encoding UTF8
    if ($LASTEXITCODE -ne 0) { throw 'PHP fixture generation failed.' }

    $screenshot = Join-Path $artifacts ($size.Name + '.png')
    $domPath = Join-Path $artifacts ($size.Name + '.html')
    $profile = Join-Path $artifacts ('browser-' + $size.Name)
    # This disposable, offline fixture needs no browser sandbox or external resources.
    $browserArgs = @('--headless=new', '--no-sandbox', '--disable-gpu', '--in-process-gpu', '--hide-scrollbars',
        '--virtual-time-budget=5000',
        '--no-first-run', '--disable-background-networking', '--dump-dom',
        ('--user-data-dir="' + $profile + '"'), ('--screenshot="' + $screenshot + '"'),
        ('--window-size=' + $size.Width + ',' + $size.Height), ([System.Uri]$fixture).AbsoluteUri)
    $started = [DateTime]::UtcNow
    $browser = Start-Process -FilePath $Edge -ArgumentList $browserArgs -WindowStyle Hidden -PassThru `
        -RedirectStandardError (Join-Path $artifacts ($size.Name + '.log')) -RedirectStandardOutput $domPath
    if (-not $browser.WaitForExit(15000)) {
        Stop-Process -Id $browser.Id -Force
        throw 'Headless Edge timed out.'
    }
    $dom = Get-Content -LiteralPath $domPath -Raw
    if ($dom -notmatch 'data-layout-result="PASS"') { throw ('Layout assertions failed: ' + $size.Name) }
    if (-not (Test-Path -LiteralPath $screenshot)) { throw 'Screenshot was not created.' }
    if ((Get-Item -LiteralPath $screenshot).LastWriteTimeUtc -lt $started) { throw 'Screenshot was not updated.' }
    Copy-Item -LiteralPath $screenshot -Destination (Join-Path $pluginRoot ('docs/images/block-' + $size.Name + '.png'))
    $checkData = [System.Net.WebUtility]::HtmlDecode([regex]::Match($dom, 'data-layout-checks="([^"]+)"').Groups[1].Value) | ConvertFrom-Json
    $assertionCount = @($checkData.PSObject.Properties).Count
    Write-Output ($size.Name + ': PASS (' + $assertionCount + ' browser layout assertions); screenshot captured.')
}

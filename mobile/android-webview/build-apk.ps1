$ErrorActionPreference = 'Stop'

$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$App = Join-Path $Root 'app'
$Src = Join-Path $App 'src\main'
$Build = Join-Path $Root 'build'
$Sdk = $env:ANDROID_SDK_ROOT

if (-not $Sdk) {
    $Sdk = $env:ANDROID_HOME
}

if (-not $Sdk) {
    $Sdk = Join-Path $env:LOCALAPPDATA 'Android\Sdk'
}

if (-not (Test-Path $Sdk)) {
    throw "Android SDK tidak ditemukan. Set ANDROID_SDK_ROOT atau install Android SDK."
}

$BuildToolsDir = Get-ChildItem (Join-Path $Sdk 'build-tools') -Directory |
    Sort-Object Name -Descending |
    Select-Object -First 1

if (-not $BuildToolsDir) {
    throw "Android build-tools tidak ditemukan di $Sdk."
}

$PlatformDir = Get-ChildItem (Join-Path $Sdk 'platforms') -Directory |
    Sort-Object Name -Descending |
    Select-Object -First 1

if (-not $PlatformDir) {
    throw "Android platform tidak ditemukan di $Sdk."
}

$Aapt2 = Join-Path $BuildToolsDir.FullName 'aapt2.exe'
$D8 = Join-Path $BuildToolsDir.FullName 'd8.bat'
$Zipalign = Join-Path $BuildToolsDir.FullName 'zipalign.exe'
$Apksigner = Join-Path $BuildToolsDir.FullName 'apksigner.bat'
$AndroidJar = Join-Path $PlatformDir.FullName 'android.jar'

function Invoke-BuildCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string] $FilePath,

        [Parameter(Mandatory = $true)]
        [string[]] $Arguments
    )

    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command gagal ($LASTEXITCODE): $FilePath $($Arguments -join ' ')"
    }
}

foreach ($Tool in @($Aapt2, $D8, $Zipalign, $Apksigner, $AndroidJar)) {
    if (-not (Test-Path $Tool)) {
        throw "Tool build tidak ditemukan: $Tool"
    }
}

if (Test-Path $Build) {
    Remove-Item -LiteralPath $Build -Recurse -Force
}

$Compiled = Join-Path $Build 'compiled'
$Generated = Join-Path $Build 'generated'
$Classes = Join-Path $Build 'classes'
$Dex = Join-Path $Build 'dex'
$Apk = Join-Path $Build 'apk'

New-Item -ItemType Directory -Force -Path $Compiled, $Generated, $Classes, $Dex, $Apk | Out-Null

$CompiledRes = Join-Path $Compiled 'resources.zip'
$Manifest = Join-Path $Src 'AndroidManifest.xml'
$UnsignedApk = Join-Path $Apk 'AMPUH-unsigned.apk'
$DexedApk = Join-Path $Apk 'AMPUH-dexed.apk'
$AlignedApk = Join-Path $Apk 'AMPUH-aligned.apk'
$SignedApk = Join-Path $Build 'AMPUH-debug.apk'

Invoke-BuildCommand $Aapt2 @('compile', '--dir', (Join-Path $Src 'res'), '-o', $CompiledRes)
Invoke-BuildCommand $Aapt2 @('link', '-o', $UnsignedApk, '-I', $AndroidJar, '--manifest', $Manifest, '--java', $Generated, '--auto-add-overlay', $CompiledRes)

$JavaFiles = @()
$JavaFiles += Get-ChildItem -Path (Join-Path $Src 'java') -Recurse -Filter *.java | ForEach-Object { $_.FullName }
$JavaFiles += Get-ChildItem -Path $Generated -Recurse -Filter *.java | ForEach-Object { $_.FullName }

if (-not $JavaFiles) {
    throw "Tidak ada file Java untuk dikompilasi."
}

Invoke-BuildCommand 'javac' (@('-encoding', 'UTF-8', '-source', '17', '-target', '17', '-classpath', $AndroidJar, '-d', $Classes) + $JavaFiles)

$ClassesJar = Join-Path $Build 'classes.jar'
if (Test-Path $ClassesJar) {
    Remove-Item -LiteralPath $ClassesJar -Force
}
Push-Location $Classes
try {
    Invoke-BuildCommand 'jar' @('cf', $ClassesJar, '.')
}
finally {
    Pop-Location
}

Invoke-BuildCommand $D8 @('--lib', $AndroidJar, '--min-api', '23', '--output', $Dex, $ClassesJar)

Copy-Item -LiteralPath $UnsignedApk -Destination $DexedApk -Force
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$Zip = [System.IO.Compression.ZipFile]::Open($DexedApk, [System.IO.Compression.ZipArchiveMode]::Update)
try {
    $Existing = $Zip.GetEntry('classes.dex')
    if ($Existing) {
        $Existing.Delete()
    }
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($Zip, (Join-Path $Dex 'classes.dex'), 'classes.dex') | Out-Null
}
finally {
    $Zip.Dispose()
}

Invoke-BuildCommand $Zipalign @('-f', '4', $DexedApk, $AlignedApk)

$DebugStore = Join-Path $Root 'debug.keystore'
if (-not (Test-Path $DebugStore)) {
    Invoke-BuildCommand 'keytool' @(
        '-genkeypair',
        '-v',
        '-keystore', $DebugStore,
        '-storepass', 'android',
        '-alias', 'androiddebugkey',
        '-keypass', 'android',
        '-keyalg', 'RSA',
        '-keysize', '2048',
        '-validity', '10000',
        '-dname', 'CN=Android Debug,O=Android,C=ID'
    )
}

Invoke-BuildCommand $Apksigner @(
    'sign',
    '--ks', $DebugStore,
    '--ks-key-alias', 'androiddebugkey',
    '--ks-pass', 'pass:android',
    '--key-pass', 'pass:android',
    '--out', $SignedApk,
    $AlignedApk
)

Invoke-BuildCommand $Apksigner @('verify', '--verbose', $SignedApk)

Write-Host "APK berhasil dibuat: $SignedApk"

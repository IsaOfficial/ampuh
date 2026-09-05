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
$AndroidJar = Join-Path $PlatformDir.FullName 'android.jar'
$Bundletool = $env:BUNDLETOOL_JAR
$BundledBundletool = Join-Path $Root 'tools\bundletool-all.jar'
$BundletoolUrl = 'https://github.com/google/bundletool/releases/download/1.18.3/bundletool-all-1.18.3.jar'

if (-not $Bundletool -and (Test-Path $BundledBundletool)) {
    $Bundletool = $BundledBundletool
}

if (-not $Bundletool) {
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $BundledBundletool) | Out-Null
    Write-Host "Mengunduh bundletool standalone..."
    Invoke-WebRequest -Uri $BundletoolUrl -OutFile $BundledBundletool -UseBasicParsing
    $Bundletool = $BundledBundletool
}

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

foreach ($Tool in @($Aapt2, $D8, $AndroidJar, $Bundletool)) {
    if (-not $Tool -or -not (Test-Path $Tool)) {
        throw "Tool build tidak ditemukan: $Tool"
    }
}

function New-RandomPassword {
    $bytes = New-Object byte[] 24
    $rng = New-Object Security.Cryptography.RNGCryptoServiceProvider
    try {
        $rng.GetBytes($bytes)
    }
    finally {
        $rng.Dispose()
    }
    return ([Convert]::ToBase64String($bytes) -replace '[^a-zA-Z0-9]', '').Substring(0, 24)
}

function Read-SigningProperties {
    param([string] $Path)

    $props = @{}
    if (Test-Path $Path) {
        foreach ($line in Get-Content $Path) {
            if (-not $line -or $line.Trim().StartsWith('#') -or -not $line.Contains('=')) {
                continue
            }

            $key, $value = $line.Split('=', 2)
            $props[$key.Trim()] = $value.Trim()
        }
    }

    return $props
}

if (Test-Path $Build) {
    Remove-Item -LiteralPath $Build -Recurse -Force
}

$KeystoreDir = Join-Path $Root 'keystore'
$PlaystoreDir = Join-Path $Root 'playstore'
$SigningProps = Join-Path $Root 'release-signing.properties'
$Keystore = Join-Path $KeystoreDir 'ampuh-upload-key.jks'
$Alias = 'ampuh_upload'

New-Item -ItemType Directory -Force -Path $KeystoreDir, $PlaystoreDir | Out-Null

$props = Read-SigningProperties $SigningProps
if (-not $props.ContainsKey('storePassword') -or -not $props.ContainsKey('keyPassword')) {
    $storePassword = New-RandomPassword
    $keyPassword = $storePassword
    @(
        '# Jangan commit file ini. Simpan bersama keystore upload Play Store.',
        "storePassword=$storePassword",
        "keyPassword=$keyPassword",
        "keyAlias=$Alias",
        "storeFile=$Keystore"
    ) | Set-Content -LiteralPath $SigningProps -Encoding ASCII
    $props = Read-SigningProperties $SigningProps
}

$StorePass = $props['storePassword']
$KeyPass = $StorePass
$Alias = $props['keyAlias']
if ($props.ContainsKey('storeFile') -and $props['storeFile']) {
    $Keystore = $props['storeFile']
}

if (-not (Test-Path $Keystore)) {
    Invoke-BuildCommand 'keytool' @(
        '-genkeypair',
        '-v',
        '-keystore', $Keystore,
        '-storepass', $StorePass,
        '-alias', $Alias,
        '-keypass', $KeyPass,
        '-keyalg', 'RSA',
        '-keysize', '4096',
        '-validity', '10000',
        '-dname', 'CN=AMPUH,O=MTs Negeri 1 Jepara,L=Jepara,ST=Jawa Tengah,C=ID'
    )
}

$Compiled = Join-Path $Build 'compiled'
$Generated = Join-Path $Build 'generated'
$Classes = Join-Path $Build 'classes'
$Dex = Join-Path $Build 'dex'
$Bundle = Join-Path $Build 'bundle'

New-Item -ItemType Directory -Force -Path $Compiled, $Generated, $Classes, $Dex, $Bundle | Out-Null

$CompiledRes = Join-Path $Compiled 'resources.zip'
$Manifest = Join-Path $Src 'AndroidManifest.xml'
$BaseModuleZip = Join-Path $Bundle 'base.zip'
$BundleModuleZip = Join-Path $Bundle 'base-module.zip'
$UnsignedAab = Join-Path $Bundle 'AMPUH-release-unsigned.aab'
$SignedAab = Join-Path $PlaystoreDir 'AMPUH-release-v1.0.3-code13.aab'

Invoke-BuildCommand $Aapt2 @('compile', '--dir', (Join-Path $Src 'res'), '-o', $CompiledRes)
Invoke-BuildCommand $Aapt2 @('link', '--proto-format', '-o', $BaseModuleZip, '-I', $AndroidJar, '--manifest', $Manifest, '--java', $Generated, '--auto-add-overlay', $CompiledRes)

$JavaFiles = @()
$JavaFiles += Get-ChildItem -Path (Join-Path $Src 'java') -Recurse -Filter *.java | ForEach-Object { $_.FullName }
$JavaFiles += Get-ChildItem -Path $Generated -Recurse -Filter *.java | ForEach-Object { $_.FullName }

if (-not $JavaFiles) {
    throw "Tidak ada file Java untuk dikompilasi."
}

Invoke-BuildCommand 'javac' (@('-encoding', 'UTF-8', '-source', '17', '-target', '17', '-classpath', $AndroidJar, '-d', $Classes) + $JavaFiles)

$ClassesJar = Join-Path $Build 'classes.jar'
Push-Location $Classes
try {
    Invoke-BuildCommand 'jar' @('cf', $ClassesJar, '.')
}
finally {
    Pop-Location
}

Invoke-BuildCommand $D8 @('--lib', $AndroidJar, '--min-api', '23', '--output', $Dex, $ClassesJar)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$Zip = [System.IO.Compression.ZipFile]::Open($BaseModuleZip, [System.IO.Compression.ZipArchiveMode]::Update)
try {
    $Existing = $Zip.GetEntry('dex/classes.dex')
    if ($Existing) {
        $Existing.Delete()
    }
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($Zip, (Join-Path $Dex 'classes.dex'), 'dex/classes.dex') | Out-Null
}
finally {
    $Zip.Dispose()
}

if (Test-Path $BundleModuleZip) {
    Remove-Item -LiteralPath $BundleModuleZip -Force
}

$SourceZip = [System.IO.Compression.ZipFile]::OpenRead($BaseModuleZip)
$TargetZip = [System.IO.Compression.ZipFile]::Open($BundleModuleZip, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($entry in $SourceZip.Entries) {
        if ($entry.FullName.EndsWith('/')) {
            continue
        }

        $targetName = if ($entry.FullName -eq 'AndroidManifest.xml') {
            'manifest/AndroidManifest.xml'
        } else {
            $entry.FullName
        }

        $targetEntry = $TargetZip.CreateEntry($targetName)
        $sourceStream = $entry.Open()
        $targetStream = $targetEntry.Open()
        try {
            $sourceStream.CopyTo($targetStream)
        }
        finally {
            $targetStream.Dispose()
            $sourceStream.Dispose()
        }
    }
}
finally {
    $TargetZip.Dispose()
    $SourceZip.Dispose()
}

Invoke-BuildCommand 'java' @('-jar', $Bundletool, 'build-bundle', '--modules', $BundleModuleZip, '--output', $UnsignedAab)
Invoke-BuildCommand 'java' @('-jar', $Bundletool, 'validate', '--bundle', $UnsignedAab)

if (Test-Path $SignedAab) {
    Remove-Item -LiteralPath $SignedAab -Force
}

Invoke-BuildCommand 'jarsigner' @(
    '-sigalg', 'SHA256withRSA',
    '-digestalg', 'SHA-256',
    '-keystore', $Keystore,
    '-storepass', $StorePass,
    '-keypass', $KeyPass,
    '-signedjar', $SignedAab,
    $UnsignedAab,
    $Alias
)

Invoke-BuildCommand 'jarsigner' @('-verify', '-verbose', '-certs', $SignedAab)

$CertPem = Join-Path $PlaystoreDir 'ampuh-upload-certificate.pem'
Invoke-BuildCommand 'keytool' @('-export', '-rfc', '-keystore', $Keystore, '-storepass', $StorePass, '-alias', $Alias, '-file', $CertPem)

Write-Host "AAB release berhasil dibuat: $SignedAab"
Write-Host "Upload key certificate: $CertPem"
Write-Host "SIMPAN AMAN: $Keystore dan $SigningProps"

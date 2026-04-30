$ErrorActionPreference = "Stop"

$phpArgs = @(
    "-d", "extension=fileinfo",
    "-d", "extension=pdo_mysql",
    "-S", "127.0.0.1:8000",
    "-t", ".",
    "../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
)

Write-Host "Starting Croissantly Order Panel at http://127.0.0.1:8000"
Push-Location "$PSScriptRoot\public"
try {
    php @phpArgs
} finally {
    Pop-Location
}

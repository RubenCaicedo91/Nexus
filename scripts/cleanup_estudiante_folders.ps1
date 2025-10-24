<#
Script: cleanup_estudiante_folders.ps1
Propósito: Colapsar carpetas repetidas tipo 'estudiante/estudiante_14/estudiante/estudiante_14/...' a una sola carpeta 'estudiante/estudiante_14'.
Uso:
  - Dry run (solo mostrar acciones):
      .\cleanup_estudiante_folders.ps1 -BasePath "C:\xampp\htdocs\NexusTeamEducation\files\estudiante" -DryRun
  - Ejecutar (mover archivos y borrar directorios vacíos):
      .\cleanup_estudiante_folders.ps1 -BasePath "C:\xampp\htdocs\NexusTeamEducation\files\estudiante"

Advertencia: Hacer backup de la carpeta antes de ejecutar en producción.
#>

param(
    [string]$BasePath = "",
    [switch]$DryRun
)

# Si no se pasa BasePath, asumir carpeta ../files/estudiante relativa al script
if ([string]::IsNullOrWhiteSpace($BasePath)) {
    $default = Join-Path -Path $PSScriptRoot -ChildPath '..\files\estudiante'
    try {
        $resolved = (Resolve-Path -Path $default -ErrorAction Stop).ProviderPath
        $BasePath = $resolved
    } catch {
        Write-Error "No se encontró la ruta por defecto: $default. Pasa -BasePath con la ruta correcta."
        exit 1
    }
}

Write-Host "BasePath usada: $BasePath"

if (-not (Test-Path -Path $BasePath)) {
    Write-Error "La ruta especificada no existe: $BasePath"
    exit 1
}

# Obtener todas las carpetas que contengan 'estudiante' o 'estudiante_{id}' en cualquier nivel
$dirs = Get-ChildItem -Path $BasePath -Recurse -Directory | Where-Object { $_.Name -match '^estudiante(_\d+)?$' }

if (-not $dirs) {
    Write-Host "No se encontraron carpetas 'estudiante' o 'estudiante_{id}' bajo $BasePath"
    exit 0
}

# Para cada folder encontrado, determinar el nombre objetivo (última ocurrencia 'estudiante' o 'estudiante_{id}')
foreach ($dir in $dirs) {
    # Obtener todos los segmentos del path y filtrar por el patrón
    $segments = $dir.FullName -split '[\\/]'
    $matches = $segments | Where-Object { $_ -match '^estudiante(_\d+)?$' }
    if (-not $matches) { continue }

    $targetName = $matches[-1]
    $targetPath = Join-Path -Path $BasePath -ChildPath $targetName

    if ($dir.FullName.TrimEnd('\') -ieq $targetPath.TrimEnd('\')) {
        # Ya está en el nivel correcto
        continue
    }

    Write-Host "Procesando: $($dir.FullName) -> destino: $targetPath"

    if ($DryRun) {
        Write-Host "[DRY RUN] Se crearían acciones para mover archivos a $targetPath y eliminar directorios vacíos."
        continue
    }

    # Crear carpeta objetivo si no existe
    if (-not (Test-Path -Path $targetPath)) {
        Write-Host "Creando carpeta de destino: $targetPath"
        New-Item -ItemType Directory -Path $targetPath | Out-Null
    }

    # Mover archivos (renombrar si existe conflicto)
    $files = Get-ChildItem -Path $dir.FullName -Recurse -File
    foreach ($file in $files) {
        $dest = Join-Path -Path $targetPath -ChildPath $file.Name
        if (Test-Path -Path $dest) {
            $base = [System.IO.Path]::GetFileNameWithoutExtension($file.Name)
            $ext = [System.IO.Path]::GetExtension($file.Name)
            $counter = 1
            do {
                $newName = "${base}_$counter$ext"
                $dest = Join-Path -Path $targetPath -ChildPath $newName
                $counter++
            } while (Test-Path -Path $dest)
            Write-Host "Archivo ya existe. Renombrando a $newName"
        }
        Move-Item -Path $file.FullName -Destination $dest
    }

    # Eliminar directorios vacíos descendientes (orden descendente)
    $subDirs = Get-ChildItem -Path $dir.FullName -Recurse -Directory | Sort-Object -Property FullName -Descending
    foreach ($sd in $subDirs) {
        try {
            $count = (Get-ChildItem -Path $sd.FullName -Force | Measure-Object).Count
            if ($count -eq 0) {
                Remove-Item -Path $sd.FullName -Force -Recurse
                Write-Host "Eliminado directorio vacío: $($sd.FullName)"
            }
        } catch {
            Write-Warning "No se pudo eliminar: $($sd.FullName) - $_"
        }
    }

    # Intentar eliminar el directorio original si quedó vacío
    try {
        $countTop = (Get-ChildItem -Path $dir.FullName -Force | Measure-Object).Count
        if ($countTop -eq 0) {
            Remove-Item -Path $dir.FullName -Force -Recurse
            Write-Host "Eliminado directorio original vacío: $($dir.FullName)"
        }
    } catch {
        Write-Warning "No se pudo eliminar el directorio original: $($dir.FullName) - $_"
    }
}

Write-Host "Limpieza finalizada. Carpetas en $BasePath:" -ForegroundColor Green
Get-ChildItem -Path $BasePath -Directory | Select-Object Name,FullName

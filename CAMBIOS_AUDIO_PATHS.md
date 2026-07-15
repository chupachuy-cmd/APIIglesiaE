# Análisis y Actualización de Rutas de Audio - Resumen de Cambios

## 📋 Cambios Realizados

### 1. **form.php** - Configuración de carga de archivos

- ✅ **Coros**: Cambió de carpeta `audioscoros` a carpeta `audios`
  - Antes: `'dir' => 'audioscoros'`
  - Después: `'dir' => 'audios'`

- ✅ **Devocionarios**: Se agregó configuración de upload para audios
  - Configuración: `'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => 'mp3', 'accept' => '.mp3']`

- ✅ **Dulia**: Se agregó configuración de upload para audios
  - Configuración: `'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => 'mp3', 'accept' => '.mp3']`

- ✅ **Hiperdulia**: Se agregó configuración de upload para audios
  - Configuración: `'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => 'mp3', 'accept' => '.mp3']`

- ✅ **Latria**: Se agregó configuración de upload para audios
  - Configuración: `'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => 'mp3', 'accept' => '.mp3']`

### 2. **resumen.txt** - Documentación actualizada

- ✅ Cambiado: `├── audioscoros/` a `├── audios/`
- ✅ Descripción actualizada a: `# Archivos de audio de coros, devocionarios, dulia, hiperdulia y latria`

### 3. **Estructura de carpetas**

- ✅ Carpeta `audios/` existe y contiene todos los archivos mp3:
  - 100+ archivos de audio en diferentes categorías
  - Coros (múltiples archivos)
  - Devocionarios (múltiples archivos)
  - Cultos (Dulia, Hiperdulia, Latria)

### 4. **Script de migración de datos** (OPCIONAL)

- ✅ Creado: `migrate_audio_paths.php`
- Función: Actualizar cualquier URL antigua en la base de datos de `audioscoros/` a `audios/`
- Uso: Visitar `http://localhost/api/migrate_audio_paths.php`

## 📁 Tablas afectadas

| Tabla         | Estado         | Campo     | Carpeta        |
| ------------- | -------------- | --------- | -------------- |
| coros         | ✅ Actualizado | url       | audios/        |
| devocionarios | ✅ Actualizado | url       | audios/        |
| dulia         | ✅ Actualizado | url       | audios/        |
| hiperdulia    | ✅ Actualizado | url       | audios/        |
| latria        | ✅ Actualizado | url       | audios/        |
| gacetas       | -              | url       | gacetas/ (PDF) |
| eventos       | -              | image_url | images/        |

## 🎵 Tipos de archivo soportados

- **MP3, WAV, M4A**: Audio (todas las tablas de audio)
- **PDF**: Gacetas
- **JPG, PNG, JPEG**: Imágenes para eventos

## ⚡ Próximos pasos (Opcional)

1. Ejecutar `/api/migrate_audio_paths.php` si hay datos antiguos
2. Probar subida de archivos en: `tabla.php?endpoint=coros` (y otros)
3. Verificar que los audios se reproducen correctamente en la plataforma

## 📝 Notas

- La carpeta `audios/` es ahora la carpeta centralizada para TODOS los audios
- Los archivos PHP en `audios/` son parte del módulo de oraciones (no de coros)
- El sistema detecta automáticamente si un URL es un archivo de audio (.mp3, .wav, .m4a)

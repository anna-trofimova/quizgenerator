# Moodle Plugin: Quiz Generator

Este plugin permite a los docentes generar preguntas tipo test automáticamente a partir de documentos PDF usando inteligencia artificial.

## Funcionalidades

- Subida de archivos PDF desde Moodle.
- Extracción de texto del documento.
- Generación de preguntas de opción múltiple o verdadero/falso mediante OpenAI.
- Almacenamiento de preguntas en la base de datos.
- Visualización y gestión de preguntas desde la interfaz de Moodle.

## Instalación

1. Copia la carpeta `quizgenerator` dentro de `moodle/local/`.
2. Accede a la administración de Moodle y completa la instalación.
3. Configura la clave de la API de OpenAI en el archivo de configuración del plugin.

## Requisitos

- Moodle 4.x
- PHP 7.4 o superior
- Cuenta de OpenAI con API Key válida

## Uso

1. Entra en el menú de administración de Moodle.
2. Sube un documento PDF.
3. Selecciona el número y tipo de preguntas.
4. Haz clic en **Generar preguntas**.
5. Visualiza y administra las preguntas creadas.


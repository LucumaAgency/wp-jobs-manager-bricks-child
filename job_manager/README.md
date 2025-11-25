# Templates de WP Job Manager - InspJobPortal

## Cómo usar los templates

### 1. Shortcode principal para mostrar trabajos con filtros

Para mostrar el listado completo de trabajos con los filtros modernos fullwidth, usa:

```
[inspjob_listings]
```

O con parámetros personalizados:

```
[inspjob_listings per_page="12" show_filters="true" show_pagination="true"]
```

### 2. Shortcode estándar de WP Job Manager

Si prefieres usar el shortcode estándar, usa:

```
[jobs]
```

Este usará automáticamente nuestros templates personalizados para los cards.

## Archivos de templates

- **job-listings.php** - Template principal que incluye filtros y listado
- **job-filters.php** - Solo el formulario de filtros con diseño fullwidth
- **content-job_listing.php** - Template de cada card de trabajo en el listado
- **content-single-job_listing.php** - Template de página individual del trabajo (2 columnas)

## Estructura de la página

1. **Filtros de búsqueda** (fullwidth con gradiente #164FC9)
   - Fila 1: Keywords | Location
   - Fila 2: Experience | Salary
   - Fila 3: Job Types (checkboxes)

2. **Listado de trabajos** (cards en grid flexible)
   - 3 columnas en desktop
   - 2 columnas en tablet
   - 1 columna en móvil

## Personalización

Todos los estilos están en:
- `/assets/css/job-manager-custom.css`

JavaScript para interactividad:
- `/assets/js/job-manager-custom.js`

Funcionalidad PHP:
- `/inc/job-manager-customizations.php`

## Campos personalizados disponibles

- `_job_salary_min` - Salario mínimo
- `_job_salary_max` - Salario máximo
- `_job_experience` - Nivel de experiencia
- `_job_benefits` - Beneficios
- `_remote_work` - Trabajo remoto (1/0)
- `_job_urgency` - Urgente (1/0)
# WP Job Manager - Bricks Child Theme

Child theme personalizado para Bricks Builder con integraciones y personalizaciones para WP Job Manager.

## 🎨 Características

- **Color principal:** #164FC9
- **Fuente principal:** Montserrat
- **Vista de trabajos:** Cards en cuadrícula (flexbox)
- **Diseño responsive:** 3 columnas desktop, 2 tablet, 1 móvil
- **Campos personalizados:** Salario, experiencia, beneficios, trabajo remoto, urgente
- **Filtros adicionales:** Por salario y experiencia
- **SEO optimizado:** Schema.org implementado

## 📁 Estructura de archivos

```
bricks-child/
├── assets/
│   └── css/
│       └── job-manager-custom.css    # Estilos personalizados
├── elements/
│   └── title.php                     # Elemento personalizado de Bricks
├── inc/
│   └── job-manager-customizations.php # Funciones PHP para WP Job Manager
├── job_manager/                      # Templates para sobrescribir (opcional)
├── functions.php                     # Funciones principales del child theme
├── style.css                        # Estilos del child theme
└── screenshot.png                   # Screenshot del theme
```

## 🚀 Instalación

1. **Requisitos previos:**
   - WordPress 5.0+
   - Bricks Builder (theme padre)
   - WP Job Manager plugin

2. **Instalación:**
   - Descarga o clona este repositorio
   - Sube la carpeta `bricks-child` a `/wp-content/themes/`
   - Activa el child theme desde el panel de WordPress

3. **Configuración:**
   - Los estilos se aplican automáticamente
   - Los campos personalizados aparecerán en el formulario de envío de trabajos

## 💻 Uso

### Shortcodes disponibles

```php
// Listado de trabajos (se verán como cards automáticamente)
[jobs]
[jobs per_page="12" show_filters="true"]

// Trabajos destacados personalizados
[inspjob_featured limit="6" columns="3"]

// Barra de búsqueda personalizada
[inspjob_search]

// Dashboard de empleador
[job_dashboard]

// Formulario de envío
[submit_job_form]
```

### Personalización de colores

Para cambiar los colores, edita las variables CSS en `assets/css/job-manager-custom.css`:

```css
:root {
    --primary-color: #164FC9;      /* Color principal */
    --primary-dark: #0F3A96;       /* Color principal oscuro */
    --primary-light: #4B75D6;      /* Color principal claro */
    --primary-ultra-light: #EBF0FC; /* Color principal ultra claro */
}
```

### Campos personalizados

Los siguientes campos personalizados están disponibles:

- `_job_salary` - Salario del trabajo
- `_job_experience` - Experiencia requerida
- `_job_benefits` - Beneficios del puesto
- `_remote_work` - Si es trabajo remoto
- `_job_urgency` - Si es contratación urgente

## 🎯 Características principales

### Vista de Cards con Flexbox

Los trabajos se muestran automáticamente como cards en lugar de lista:
- Display flexbox para mejor compatibilidad
- Gap de 2rem entre cards
- Animaciones suaves en hover
- Sombras con el color principal

### Responsive Design

- **Desktop (>992px):** 3 columnas
- **Tablet (768px-992px):** 2 columnas
- **Móvil (<768px):** 1 columna

### Filtros personalizados

Se añaden automáticamente filtros para:
- Rango salarial
- Nivel de experiencia

### SEO

- Schema.org implementado para job postings
- Meta datos estructurados
- Compatible con Google Jobs

## 🔧 Personalización avanzada

### Modificar templates

Para personalizar los templates de WP Job Manager:

1. Copia los templates desde:
   `/wp-content/plugins/wp-job-manager/templates/`

2. Pégalos en:
   `/wp-content/themes/bricks-child/job_manager/`

3. Edita los archivos según necesites

### Añadir más campos

En `inc/job-manager-customizations.php`, función `inspjob_custom_job_fields()`:

```php
$fields['job']['nuevo_campo'] = array(
    'label'       => 'Mi Campo',
    'type'        => 'text',
    'required'    => false,
    'placeholder' => 'Placeholder aquí',
    'priority'    => 12
);
```

## 📝 Licencia

Este proyecto está bajo licencia GPL v2 o posterior.

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea tu feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la branch (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📧 Soporte

Para soporte o consultas sobre este child theme, por favor abre un issue en GitHub.

---

Desarrollado con ❤️ para InspJobPortal
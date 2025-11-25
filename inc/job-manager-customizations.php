<?php
/**
 * WP Job Manager Customizations for Bricks Child Theme
 * InspJobPortal - Color: #164FC9 - Font: Montserrat
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ENQUEUE STYLES Y SCRIPTS
 * =========================
 */
add_action('wp_enqueue_scripts', 'inspjob_bricks_job_manager_assets', 20);
function inspjob_bricks_job_manager_assets() {
    // Solo cargar en páginas relacionadas con jobs
    if (is_post_type_archive('job_listing') || is_singular('job_listing') || has_shortcode(get_post()->post_content ?? '', 'jobs') || has_shortcode(get_post()->post_content ?? '', 'job')) {

        // CSS Personalizado para WP Job Manager
        wp_enqueue_style(
            'inspjob-job-manager',
            get_stylesheet_directory_uri() . '/assets/css/job-manager-custom.css',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/css/job-manager-custom.css')
        );

        // JavaScript personalizado - crear archivo si no existe
        $js_file = get_stylesheet_directory() . '/assets/js/job-manager-custom.js';
        if (!file_exists($js_file)) {
            // Crear directorio si no existe
            if (!file_exists(dirname($js_file))) {
                wp_mkdir_p(dirname($js_file));
            }
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'inspjob-job-manager-js',
            get_stylesheet_directory_uri() . '/assets/js/job-manager-custom.js',
            array('jquery'),
            '1.0.1',
            true
        );

        // Localizar script para AJAX
        wp_localize_script('inspjob-job-manager-js', 'inspjob_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('inspjob_nonce')
        ));
    }
}

/**
 * SOPORTE PARA TEMPLATES
 * =======================
 */
add_theme_support('job-manager-templates');

/**
 * CAMPOS PERSONALIZADOS
 * ======================
 */
add_filter('submit_job_form_fields', 'inspjob_custom_job_fields');
function inspjob_custom_job_fields($fields) {

    // Campo Salario Mínimo
    $fields['job']['job_salary_min'] = array(
        'label'       => 'Salario Mínimo (€)',
        'type'        => 'text',
        'required'    => false,
        'placeholder' => 'ej. 30000',
        'description' => 'Salario mínimo anual en euros (solo números)',
        'priority'    => 7,
        'attributes'  => array(
            'pattern' => '[0-9]*',
            'inputmode' => 'numeric'
        )
    );

    // Campo Salario Máximo
    $fields['job']['job_salary_max'] = array(
        'label'       => 'Salario Máximo (€)',
        'type'        => 'text',
        'required'    => false,
        'placeholder' => 'ej. 50000',
        'description' => 'Salario máximo anual en euros (solo números)',
        'priority'    => 8,
        'attributes'  => array(
            'pattern' => '[0-9]*',
            'inputmode' => 'numeric'
        )
    );

    // Campo Experiencia
    $fields['job']['job_experience'] = array(
        'label'       => 'Experiencia Requerida',
        'type'        => 'select',
        'required'    => false,
        'options'     => array(
            ''        => 'Seleccionar...',
            'entry'   => 'Sin experiencia',
            'junior'  => '1-2 años',
            'mid'     => '3-5 años',
            'senior'  => '5-10 años',
            'expert'  => '10+ años'
        ),
        'priority'    => 9
    );

    // Campo Beneficios
    $fields['job']['job_benefits'] = array(
        'label'       => 'Beneficios',
        'type'        => 'textarea',
        'required'    => false,
        'placeholder' => 'Lista los beneficios del puesto...',
        'priority'    => 10
    );

    // Campo Trabajo Remoto
    $fields['job']['remote_work'] = array(
        'label'       => '¿Trabajo Remoto Disponible?',
        'type'        => 'checkbox',
        'required'    => false,
        'priority'    => 11
    );

    // Campo Urgente
    $fields['job']['job_urgency'] = array(
        'label'       => '¿Contratación Urgente?',
        'type'        => 'checkbox',
        'required'    => false,
        'priority'    => 12
    );

    return $fields;
}

// Asegurar que los campos de salario se muestren correctamente
add_filter('submit_job_form_fields_get_job_data', 'inspjob_populate_salary_fields', 10, 2);
function inspjob_populate_salary_fields($fields, $job) {
    if ($job) {
        $fields['job']['job_salary_min']['value'] = get_post_meta($job->ID, '_job_salary_min', true);
        $fields['job']['job_salary_max']['value'] = get_post_meta($job->ID, '_job_salary_max', true);
    }
    return $fields;
}

// Hook adicional para campos personalizados en admin
add_filter('job_manager_job_listing_data_fields', 'inspjob_admin_fields');
function inspjob_admin_fields($fields) {

    $fields['_job_salary_min'] = array(
        'label'       => __('Salario Mínimo (€)', 'inspjob'),
        'type'        => 'text',
        'placeholder' => __('ej. 30000', 'inspjob'),
        'description' => __('Salario mínimo anual en euros', 'inspjob'),
        'priority'    => 3.5
    );

    $fields['_job_salary_max'] = array(
        'label'       => __('Salario Máximo (€)', 'inspjob'),
        'type'        => 'text',
        'placeholder' => __('ej. 50000', 'inspjob'),
        'description' => __('Salario máximo anual en euros', 'inspjob'),
        'priority'    => 3.6
    );

    $fields['_remote_work'] = array(
        'label'       => __('Trabajo Remoto', 'inspjob'),
        'type'        => 'checkbox',
        'description' => __('Marcar si es trabajo remoto', 'inspjob'),
        'priority'    => 4
    );

    $fields['_job_urgency'] = array(
        'label'       => __('Contratación Urgente', 'inspjob'),
        'type'        => 'checkbox',
        'description' => __('Marcar si es urgente', 'inspjob'),
        'priority'    => 4.1
    );

    return $fields;
}

// Guardar campos personalizados
add_action('job_manager_update_job_data', 'inspjob_save_custom_fields', 10, 2);
function inspjob_save_custom_fields($job_id, $values) {

    if (isset($values['job']['job_salary_min'])) {
        update_post_meta($job_id, '_job_salary_min', absint($values['job']['job_salary_min']));
    }

    if (isset($values['job']['job_salary_max'])) {
        update_post_meta($job_id, '_job_salary_max', absint($values['job']['job_salary_max']));
    }

    if (isset($values['job']['job_experience'])) {
        update_post_meta($job_id, '_job_experience', sanitize_text_field($values['job']['job_experience']));
    }

    if (isset($values['job']['job_benefits'])) {
        update_post_meta($job_id, '_job_benefits', sanitize_textarea_field($values['job']['job_benefits']));
    }

    if (isset($values['job']['remote_work'])) {
        update_post_meta($job_id, '_remote_work', $values['job']['remote_work'] ? '1' : '0');
    }

    if (isset($values['job']['job_urgency'])) {
        update_post_meta($job_id, '_job_urgency', $values['job']['job_urgency'] ? '1' : '0');
    }
}

// Mostrar campos en el frontend
add_action('single_job_listing_meta_end', 'inspjob_display_custom_fields');
function inspjob_display_custom_fields() {
    global $post;

    $salary_min = get_post_meta($post->ID, '_job_salary_min', true);
    $salary_max = get_post_meta($post->ID, '_job_salary_max', true);
    $experience = get_post_meta($post->ID, '_job_experience', true);

    // Mostrar rango salarial
    if ($salary_min || $salary_max) {
        $salary_display = '';
        if ($salary_min && $salary_max) {
            $salary_display = '€' . number_format($salary_min, 0, ',', '.') . ' - €' . number_format($salary_max, 0, ',', '.');
        } elseif ($salary_min) {
            $salary_display = 'Desde €' . number_format($salary_min, 0, ',', '.');
        } elseif ($salary_max) {
            $salary_display = 'Hasta €' . number_format($salary_max, 0, ',', '.');
        }
        echo '<li class="job-salary"><strong>Salario:</strong> ' . esc_html($salary_display) . '</li>';
    }

    if ($experience) {
        $labels = array(
            'entry'  => 'Sin experiencia',
            'junior' => '1-2 años',
            'mid'    => '3-5 años',
            'senior' => '5-10 años',
            'expert' => '10+ años'
        );
        if (isset($labels[$experience])) {
            echo '<li class="job-experience"><strong>Experiencia:</strong> ' . esc_html($labels[$experience]) . '</li>';
        }
    }
}

/**
 * OVERRIDE DE TEMPLATES
 * =====================
 */

// Asegurar que WP Job Manager use nuestros templates personalizados
add_filter('job_manager_locate_template', 'inspjob_locate_template', 10, 3);
function inspjob_locate_template($template, $template_name, $template_path) {
    $custom_template = get_stylesheet_directory() . '/job_manager/' . $template_name;

    if (file_exists($custom_template)) {
        return $custom_template;
    }

    return $template;
}

// Shortcode personalizado para mostrar trabajos con nuestro diseño
add_shortcode('inspjob_listings', 'inspjob_listings_shortcode');
function inspjob_listings_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => 12,
        'orderby' => 'featured',
        'order' => 'DESC',
        'show_filters' => true,
        'show_categories' => true,
        'show_pagination' => true
    ), $atts);

    ob_start();

    // Incluir nuestro template personalizado
    include(get_stylesheet_directory() . '/job_manager/job-listings.php');

    return ob_get_clean();
}

/**
 * FILTROS DE BÚSQUEDA PERSONALIZADOS
 * ===================================
 */

// Filtrar trabajos por experiencia
add_filter('job_manager_get_listings', 'inspjob_filter_by_experience', 10, 2);
function inspjob_filter_by_experience($query_args, $args) {
    if (!empty($_GET['search_experience'])) {
        $query_args['meta_query'][] = array(
            'key'     => '_job_experience',
            'value'   => sanitize_text_field($_GET['search_experience']),
            'compare' => '='
        );
    }
    return $query_args;
}

// Filtrar trabajos por rango salarial
add_filter('job_manager_get_listings', 'inspjob_filter_by_salary', 10, 2);
function inspjob_filter_by_salary($query_args, $args) {
    if (!empty($_GET['search_salary'])) {
        $salary_range = sanitize_text_field($_GET['search_salary']);

        // Parsear el rango salarial
        if ($salary_range === '0-20000') {
            $min = 0;
            $max = 20000;
        } elseif ($salary_range === '20000-30000') {
            $min = 20000;
            $max = 30000;
        } elseif ($salary_range === '30000-40000') {
            $min = 30000;
            $max = 40000;
        } elseif ($salary_range === '40000-60000') {
            $min = 40000;
            $max = 60000;
        } elseif ($salary_range === '60000-80000') {
            $min = 60000;
            $max = 80000;
        } elseif ($salary_range === '80000+') {
            $min = 80000;
            $max = 999999999;
        } else {
            return $query_args;
        }

        // Filtrar por salario mínimo o máximo dentro del rango
        $query_args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key'     => '_job_salary_min',
                'value'   => array($min, $max),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN'
            ),
            array(
                'key'     => '_job_salary_max',
                'value'   => array($min, $max),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN'
            )
        );
    }
    return $query_args;
}

// Filtrar trabajos remotos
add_filter('job_manager_get_listings', 'inspjob_filter_by_remote', 10, 2);
function inspjob_filter_by_remote($query_args, $args) {
    if (!empty($_GET['search_remote']) && $_GET['search_remote'] == '1') {
        $query_args['meta_query'][] = array(
            'key'     => '_remote_work',
            'value'   => '1',
            'compare' => '='
        );
    }
    return $query_args;
}

// Filtrar por tipo de trabajo (checkbox múltiple)
add_filter('job_manager_get_listings', 'inspjob_filter_by_job_types', 10, 2);
function inspjob_filter_by_job_types($query_args, $args) {
    if (!empty($_GET['search_job_type']) && is_array($_GET['search_job_type'])) {
        $job_types = array_map('sanitize_text_field', $_GET['search_job_type']);

        $query_args['tax_query'][] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => $job_types,
            'operator' => 'IN'
        );
    }
    return $query_args;
}

/**
 * SHORTCODES PERSONALIZADOS
 * ==========================
 */

// Shortcode para trabajos destacados
add_shortcode('inspjob_featured', 'inspjob_featured_shortcode');
function inspjob_featured_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 6,
        'columns' => 3
    ), $atts);

    $jobs = get_job_listings(array(
        'posts_per_page' => $atts['limit'],
        'featured' => true
    ));

    ob_start();
    ?>
    <div class="inspjob-featured-grid" style="display: grid; grid-template-columns: repeat(<?php echo $atts['columns']; ?>, 1fr); gap: 2rem;">
        <?php if ($jobs->have_posts()) : while ($jobs->have_posts()) : $jobs->the_post(); ?>
            <div class="inspjob-job-card">
                <?php get_job_manager_template_part('content', 'job_listing'); ?>
            </div>
        <?php endwhile; else : ?>
            <p>No hay trabajos destacados disponibles.</p>
        <?php endif; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode para búsqueda rápida
add_shortcode('inspjob_search', 'inspjob_search_shortcode');
function inspjob_search_shortcode() {
    ob_start();
    ?>
    <form class="inspjob-search-form" method="get" action="<?php echo get_permalink(get_option('job_manager_jobs_page_id')); ?>">
        <div class="search-grid" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div>
                <label>Palabras clave</label>
                <input type="text" name="search_keywords" placeholder="Título, empresa...">
            </div>
            <div>
                <label>Ubicación</label>
                <input type="text" name="search_location" placeholder="Ciudad o código postal">
            </div>
            <button type="submit" style="background: #164FC9; color: white; padding: 0.75rem 2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Buscar Empleos
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * PERSONALIZACIÓN DE CONSULTAS
 * =============================
 */
add_action('pre_get_posts', 'inspjob_customize_queries');
function inspjob_customize_queries($query) {
    if (!is_admin() && $query->is_main_query()) {
        if (is_post_type_archive('job_listing')) {
            $query->set('posts_per_page', 12);
        }
    }
}

/**
 * AJAX HANDLERS
 * ==============
 */
add_action('wp_ajax_save_job', 'inspjob_save_job_ajax');
add_action('wp_ajax_nopriv_save_job', 'inspjob_save_job_ajax');
function inspjob_save_job_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'inspjob_nonce')) {
        wp_die('Security check failed');
    }

    $job_id = intval($_POST['job_id']);
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(['message' => 'Debes iniciar sesión']);
        return;
    }

    // Guardar en user meta
    $saved_jobs = get_user_meta($user_id, 'saved_jobs', true);
    if (!is_array($saved_jobs)) {
        $saved_jobs = array();
    }

    if (!in_array($job_id, $saved_jobs)) {
        $saved_jobs[] = $job_id;
        update_user_meta($user_id, 'saved_jobs', $saved_jobs);
        wp_send_json_success(['message' => 'Trabajo guardado']);
    } else {
        wp_send_json_error(['message' => 'Ya guardaste este trabajo']);
    }
}

/**
 * SCHEMA.ORG PARA SEO
 * ====================
 */
add_action('wp_head', 'inspjob_schema');
function inspjob_schema() {
    if (is_singular('job_listing')) {
        global $post;

        $schema = array(
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => get_the_title(),
            'description' => get_the_excerpt(),
            'datePosted' => get_the_date('c'),
            'hiringOrganization' => array(
                '@type' => 'Organization',
                'name' => get_the_company_name()
            )
        );

        if (get_the_job_location()) {
            $schema['jobLocation'] = array(
                '@type' => 'Place',
                'address' => get_the_job_location()
            );
        }

        $salary = get_post_meta($post->ID, '_job_salary', true);
        if ($salary) {
            $schema['baseSalary'] = $salary;
        }

        echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
    }
}

/**
 * ESTILOS INLINE CRÍTICOS
 * ========================
 */
add_action('wp_head', 'inspjob_critical_styles', 999);
function inspjob_critical_styles() {
    if (is_post_type_archive('job_listing') || is_singular('job_listing')) {
        ?>
        <style>
            /* Color principal y fuente Montserrat */
            .job-listings-wrapper,
            .single-job-listing {
                font-family: 'Montserrat', sans-serif !important;
            }

            .btn-view-job,
            .application_button,
            input[type="submit"] {
                background-color: #164FC9 !important;
            }

            .btn-view-job:hover,
            .application_button:hover,
            input[type="submit"]:hover {
                background-color: #0F3A96 !important;
            }

            a {
                color: #164FC9;
            }

            .job-type-full-time {
                background: #EBF0FC;
                color: #164FC9;
            }
        </style>
        <?php
    }
}

/**
 * COMPATIBILIDAD CON BRICKS BUILDER
 * ==================================
 */

// Asegurar que los templates de WP Job Manager funcionen con Bricks
add_filter('bricks/active_templates', 'inspjob_bricks_compatibility', 10, 3);
function inspjob_bricks_compatibility($active_templates, $post_id, $content_type) {
    if (get_post_type($post_id) === 'job_listing') {
        // Permitir que WP Job Manager maneje sus propios templates
        return $active_templates;
    }
    return $active_templates;
}

// Registrar templates de Bricks para job_listing si es necesario
add_filter('bricks/builder/i18n', 'inspjob_bricks_labels');
function inspjob_bricks_labels($i18n) {
    $i18n['inspjob'] = 'InspJobPortal';
    return $i18n;
}
?>
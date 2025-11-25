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

        // JavaScript personalizado
        wp_enqueue_script(
            'inspjob-job-manager-js',
            get_stylesheet_directory_uri() . '/assets/js/job-manager-custom.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/job-manager-custom.js'),
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

    // Campo Salario
    $fields['job']['job_salary'] = array(
        'label'       => 'Salario',
        'type'        => 'text',
        'required'    => false,
        'placeholder' => 'ej. €30,000 - €50,000',
        'priority'    => 7
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
        'priority'    => 8
    );

    // Campo Beneficios
    $fields['job']['job_benefits'] = array(
        'label'       => 'Beneficios',
        'type'        => 'textarea',
        'required'    => false,
        'placeholder' => 'Lista los beneficios del puesto...',
        'priority'    => 9
    );

    // Campo Trabajo Remoto
    $fields['job']['remote_work'] = array(
        'label'       => '¿Trabajo Remoto Disponible?',
        'type'        => 'checkbox',
        'required'    => false,
        'priority'    => 10
    );

    // Campo Urgente
    $fields['job']['job_urgency'] = array(
        'label'       => '¿Contratación Urgente?',
        'type'        => 'checkbox',
        'required'    => false,
        'priority'    => 11
    );

    return $fields;
}

// Guardar campos personalizados
add_action('job_manager_update_job_data', 'inspjob_save_custom_fields', 10, 2);
function inspjob_save_custom_fields($job_id, $values) {

    if (isset($values['job']['job_salary'])) {
        update_post_meta($job_id, '_job_salary', sanitize_text_field($values['job']['job_salary']));
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

    $salary = get_post_meta($post->ID, '_job_salary', true);
    $experience = get_post_meta($post->ID, '_job_experience', true);

    if ($salary) {
        echo '<li class="job-salary"><strong>Salario:</strong> ' . esc_html($salary) . '</li>';
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
 * FILTROS DE BÚSQUEDA PERSONALIZADOS
 * ===================================
 */
add_action('job_manager_job_filters_before', 'inspjob_custom_filters');
function inspjob_custom_filters($atts) {
    ?>
    <div class="search_salary">
        <label for="search_salary">Rango Salarial</label>
        <select name="filter_salary" id="search_salary" class="job-manager-filter">
            <option value="">Cualquier salario</option>
            <option value="0-30000">€0 - €30,000</option>
            <option value="30000-50000">€30,000 - €50,000</option>
            <option value="50000-70000">€50,000 - €70,000</option>
            <option value="70000-100000">€70,000 - €100,000</option>
            <option value="100000+">€100,000+</option>
        </select>
    </div>

    <div class="search_experience">
        <label for="search_experience">Experiencia</label>
        <select name="filter_experience" id="search_experience" class="job-manager-filter">
            <option value="">Cualquier experiencia</option>
            <option value="entry">Sin experiencia</option>
            <option value="junior">1-2 años</option>
            <option value="mid">3-5 años</option>
            <option value="senior">5-10 años</option>
            <option value="expert">10+ años</option>
        </select>
    </div>
    <?php
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
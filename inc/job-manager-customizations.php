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
 * TRADUCCIONES DEL FORMULARIO DE APLICACIÓN
 * ==========================================
 */
add_filter('gettext', 'inspjob_translate_application_form', 10, 3);
add_filter('ngettext', 'inspjob_translate_application_form', 10, 3);
function inspjob_translate_application_form($translated, $text, $domain) {
    // Solo traducir textos de WP Job Manager
    if (strpos($domain, 'job') === false && $domain !== 'default') {
        return $translated;
    }

    $translations = array(
        // Formulario de aplicación
        'Apply for job' => 'Aplicar al empleo',
        'Apply Now' => 'Aplicar ahora',
        'Apply' => 'Aplicar',
        'Application' => 'Aplicación',
        'Your name' => 'Tu nombre',
        'Full name' => 'Nombre completo',
        'Name' => 'Nombre',
        'Your email' => 'Tu correo electrónico',
        'Email address' => 'Correo electrónico',
        'Email' => 'Correo',
        'Message' => 'Mensaje',
        'Your message' => 'Tu mensaje',
        'Cover letter' => 'Carta de presentación',
        'Application message' => 'Mensaje de aplicación',
        'Submit' => 'Enviar',
        'Send application' => 'Enviar aplicación',
        'Send Application' => 'Enviar Aplicación',
        'Submit Application' => 'Enviar Aplicación',
        'Application sent' => 'Aplicación enviada',
        'Application submitted successfully.' => 'Aplicación enviada correctamente.',
        'Thank you for your application' => 'Gracias por tu aplicación',
        'Resume' => 'Currículum',
        'Upload Resume' => 'Subir Currículum',
        'Upload CV' => 'Subir CV',
        'Attach Resume' => 'Adjuntar Currículum',
        'Online resume' => 'Currículum en línea',
        'Website/URL' => 'Sitio web/URL',
        'Website' => 'Sitio web',
        'Phone' => 'Teléfono',
        'Phone number' => 'Número de teléfono',
        'Required' => 'Requerido',
        'optional' => 'opcional',
        'Optional' => 'Opcional',

        // Botones y acciones
        'Apply with Resume' => 'Aplicar con Currículum',
        'Apply with LinkedIn' => 'Aplicar con LinkedIn',
        'Apply with Indeed' => 'Aplicar con Indeed',

        // Mensajes de error/éxito
        'Please enter your name' => 'Por favor ingresa tu nombre',
        'Please enter your email address' => 'Por favor ingresa tu correo electrónico',
        'Please enter a valid email address' => 'Por favor ingresa un correo electrónico válido',
        'Please enter your message' => 'Por favor ingresa tu mensaje',
        'There was an error sending your application' => 'Hubo un error al enviar tu aplicación',

        // Labels adicionales
        'Candidate name' => 'Nombre del candidato',
        'Candidate email' => 'Correo del candidato',
        'Application email' => 'Correo de aplicación',
        'How to apply' => 'Cómo aplicar',
        'To apply for this job' => 'Para aplicar a este empleo',
    );

    if (isset($translations[$text])) {
        return $translations[$text];
    }

    return $translated;
}

/**
 * PÁGINA DE LOGIN PERSONALIZADA
 * ==============================
 */

// Shortcode para formulario de login
add_shortcode('inspjob_login_form', 'inspjob_login_form_shortcode');
function inspjob_login_form_shortcode($atts) {
    // Si ya está logueado, mostrar mensaje
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $redirect_url = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url();

        return '<div class="inspjob-login-logged-in">
            <p>Hola, <strong>' . esc_html($current_user->display_name) . '</strong>. Ya has iniciado sesión.</p>
            <p><a href="' . esc_url($redirect_url) . '" class="btn-primary">Continuar</a></p>
        </div>';
    }

    $atts = shortcode_atts(array(
        'redirect' => '',
    ), $atts);

    // Obtener URL de redirección
    $redirect_to = !empty($atts['redirect']) ? $atts['redirect'] : '';
    if (empty($redirect_to) && isset($_GET['redirect_to'])) {
        $redirect_to = esc_url($_GET['redirect_to']);
    }
    if (empty($redirect_to)) {
        $redirect_to = home_url();
    }

    // Mensajes de error
    $error_message = '';
    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        $error_message = '<div class="inspjob-login-error">Usuario o contraseña incorrectos. Por favor, inténtalo de nuevo.</div>';
    }
    if (isset($_GET['logged_out']) && $_GET['logged_out'] === 'true') {
        $error_message = '<div class="inspjob-login-success">Has cerrado sesión correctamente.</div>';
    }

    // Formulario de login
    $form = '<div class="inspjob-login-form-wrapper">
        ' . $error_message . '
        <form name="loginform" id="inspjob-loginform" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post" class="inspjob-login-form">
            <div class="form-field">
                <label for="user_login">Correo electrónico o usuario</label>
                <input type="text" name="log" id="user_login" class="input" required />
            </div>
            <div class="form-field">
                <label for="user_pass">Contraseña</label>
                <input type="password" name="pwd" id="user_pass" class="input" required />
            </div>
            <div class="form-field form-field-remember">
                <label>
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever" /> Recordarme
                </label>
            </div>
            <div class="form-field form-field-submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="btn-primary" value="Iniciar Sesión" />
                <input type="hidden" name="redirect_to" value="' . esc_attr($redirect_to) . '" />
            </div>
        </form>
        <div class="inspjob-login-links">
            <a href="' . esc_url(wp_lostpassword_url($redirect_to)) . '">¿Olvidaste tu contraseña?</a>
            ' . (get_option('users_can_register') ? '<a href="' . esc_url(wp_registration_url()) . '">Crear una cuenta</a>' : '') . '
        </div>
    </div>';

    return $form;
}

// Redirigir wp-login.php a página personalizada (solo para usuarios no admin)
add_action('login_init', 'inspjob_redirect_login_page');
function inspjob_redirect_login_page() {
    // URL de la página de login personalizada
    $custom_login_page = home_url('/iniciar-sesion/');

    // Obtener la acción actual
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

    // No redirigir para estas acciones
    $allowed_actions = array('logout', 'lostpassword', 'rp', 'resetpass', 'postpass', 'confirm_admin_email');

    if (in_array($action, $allowed_actions)) {
        return;
    }

    // No redirigir si es POST (envío de formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }

    // No redirigir si ya está en la página de login personalizada
    if (strpos($_SERVER['REQUEST_URI'], 'iniciar-sesion') !== false) {
        return;
    }

    // Construir URL de redirección
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';
    $login_url = $custom_login_page;

    if (!empty($redirect_to)) {
        $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
    }

    wp_redirect($login_url);
    exit;
}

// Redirigir errores de login a página personalizada
add_action('wp_login_failed', 'inspjob_login_failed');
function inspjob_login_failed() {
    $custom_login_page = home_url('/iniciar-sesion/');

    $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';
    $login_url = add_query_arg('login', 'failed', $custom_login_page);

    if (!empty($redirect_to)) {
        $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
    }

    wp_redirect($login_url);
    exit;
}

// Redirigir después de logout a página personalizada
add_action('wp_logout', 'inspjob_logout_redirect');
function inspjob_logout_redirect() {
    $custom_login_page = home_url('/iniciar-sesion/');
    $login_url = add_query_arg('logged_out', 'true', $custom_login_page);

    wp_redirect($login_url);
    exit;
}

/**
 * CAMPOS PERSONALIZADOS
 * ======================
 */
add_filter('submit_job_form_fields', 'inspjob_custom_job_fields');
function inspjob_custom_job_fields($fields) {

    // Campo Salario Mínimo
    $fields['job']['job_salary_min'] = array(
        'label'       => 'Salario Mínimo (S/)',
        'type'        => 'text',
        'required'    => false,
        'placeholder' => 'ej. 30000',
        'description' => 'Salario mínimo anual en soles (solo números)',
        'priority'    => 7,
        'attributes'  => array(
            'pattern' => '[0-9]*',
            'inputmode' => 'numeric'
        )
    );

    // Campo Salario Máximo
    $fields['job']['job_salary_max'] = array(
        'label'       => 'Salario Máximo (S/)',
        'type'        => 'text',
        'required'    => false,
        'placeholder' => 'ej. 50000',
        'description' => 'Salario máximo anual en soles (solo números)',
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
        'label'       => __('Salario Mínimo (S/)', 'inspjob'),
        'type'        => 'text',
        'placeholder' => __('ej. 30000', 'inspjob'),
        'description' => __('Salario mínimo anual en soles', 'inspjob'),
        'priority'    => 3.5
    );

    $fields['_job_salary_max'] = array(
        'label'       => __('Salario Máximo (S/)', 'inspjob'),
        'type'        => 'text',
        'placeholder' => __('ej. 50000', 'inspjob'),
        'description' => __('Salario máximo anual en soles', 'inspjob'),
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
            $salary_display = 'S/ ' . number_format($salary_min, 0, ',', '.') . ' - S/ ' . number_format($salary_max, 0, ',', '.');
        } elseif ($salary_min) {
            $salary_display = 'Desde S/ ' . number_format($salary_min, 0, ',', '.');
        } elseif ($salary_max) {
            $salary_display = 'Hasta S/ ' . number_format($salary_max, 0, ',', '.');
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

// Shortcode para filtros de salario y tipo de trabajo
add_shortcode('inspjob_filters', 'inspjob_filters_shortcode');
function inspjob_filters_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_salary' => 'yes',
        'show_job_type' => 'yes',
    ), $atts);

    // Obtener tipos de trabajo desde la taxonomía
    $job_types = get_terms(array(
        'taxonomy' => 'job_listing_type',
        'hide_empty' => false,
    ));

    // Valores actuales de los filtros
    $current_salary = isset($_GET['filter_salary']) ? sanitize_text_field($_GET['filter_salary']) : '';
    $current_job_types = isset($_GET['filter_job_type']) ? array_map('sanitize_text_field', (array)$_GET['filter_job_type']) : array();

    ob_start();
    ?>
    <div class="inspjob-filters-wrapper">
        <form class="inspjob-filters-form" method="get" action="<?php echo get_permalink(get_option('job_manager_jobs_page_id')); ?>">
            <?php // Preservar otros parámetros de búsqueda ?>
            <?php if (isset($_GET['search_keywords'])) : ?>
                <input type="hidden" name="search_keywords" value="<?php echo esc_attr($_GET['search_keywords']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['search_location'])) : ?>
                <input type="hidden" name="search_location" value="<?php echo esc_attr($_GET['search_location']); ?>">
            <?php endif; ?>

            <div class="inspjob-filters-container">
                <?php if ($atts['show_salary'] === 'yes') : ?>
                <!-- Filtro de Salario -->
                <div class="inspjob-filter-group">
                    <label class="inspjob-filter-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v12M9 9h6M9 15h6"></path>
                        </svg>
                        Rango Salarial
                    </label>
                    <div class="inspjob-filter-options inspjob-salary-options">
                        <label class="inspjob-filter-chip <?php echo $current_salary === '' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="" <?php checked($current_salary, ''); ?>>
                            <span>Todos</span>
                        </label>
                        <label class="inspjob-filter-chip <?php echo $current_salary === '0-2000' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="0-2000" <?php checked($current_salary, '0-2000'); ?>>
                            <span>Hasta S/ 2,000</span>
                        </label>
                        <label class="inspjob-filter-chip <?php echo $current_salary === '2000-4000' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="2000-4000" <?php checked($current_salary, '2000-4000'); ?>>
                            <span>S/ 2,000 - 4,000</span>
                        </label>
                        <label class="inspjob-filter-chip <?php echo $current_salary === '4000-6000' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="4000-6000" <?php checked($current_salary, '4000-6000'); ?>>
                            <span>S/ 4,000 - 6,000</span>
                        </label>
                        <label class="inspjob-filter-chip <?php echo $current_salary === '6000-10000' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="6000-10000" <?php checked($current_salary, '6000-10000'); ?>>
                            <span>S/ 6,000 - 10,000</span>
                        </label>
                        <label class="inspjob-filter-chip <?php echo $current_salary === '10000+' ? 'active' : ''; ?>">
                            <input type="radio" name="filter_salary" value="10000+" <?php checked($current_salary, '10000+'); ?>>
                            <span>Más de S/ 10,000</span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($atts['show_job_type'] === 'yes' && !empty($job_types) && !is_wp_error($job_types)) : ?>
                <!-- Filtro de Tipo de Trabajo -->
                <div class="inspjob-filter-group">
                    <label class="inspjob-filter-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Tipo de Trabajo
                    </label>
                    <div class="inspjob-filter-options inspjob-jobtype-options">
                        <?php foreach ($job_types as $type) : ?>
                        <label class="inspjob-filter-chip <?php echo in_array($type->slug, $current_job_types) ? 'active' : ''; ?>">
                            <input type="checkbox" name="filter_job_type[]" value="<?php echo esc_attr($type->slug); ?>" <?php checked(in_array($type->slug, $current_job_types)); ?>>
                            <span><?php echo esc_html($type->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div class="inspjob-filter-actions">
                    <button type="submit" class="inspjob-filter-btn inspjob-filter-apply">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Aplicar Filtros
                    </button>
                    <a href="<?php echo get_permalink(get_option('job_manager_jobs_page_id')); ?>" class="inspjob-filter-btn inspjob-filter-clear">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Filtrar trabajos por el nuevo filtro de salario del shortcode
add_filter('job_manager_get_listings', 'inspjob_filter_by_salary_shortcode', 10, 2);
function inspjob_filter_by_salary_shortcode($query_args, $args) {
    if (!empty($_GET['filter_salary'])) {
        $salary_range = sanitize_text_field($_GET['filter_salary']);

        $ranges = array(
            '0-2000'     => array(0, 2000),
            '2000-4000'  => array(2000, 4000),
            '4000-6000'  => array(4000, 6000),
            '6000-10000' => array(6000, 10000),
            '10000+'     => array(10000, 999999999),
        );

        if (isset($ranges[$salary_range])) {
            $min = $ranges[$salary_range][0];
            $max = $ranges[$salary_range][1];

            if (!isset($query_args['meta_query'])) {
                $query_args['meta_query'] = array();
            }

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
    }
    return $query_args;
}

// Filtrar trabajos por tipo de trabajo desde el shortcode
add_filter('job_manager_get_listings', 'inspjob_filter_by_job_type_shortcode', 10, 2);
function inspjob_filter_by_job_type_shortcode($query_args, $args) {
    if (!empty($_GET['filter_job_type']) && is_array($_GET['filter_job_type'])) {
        $job_types = array_map('sanitize_text_field', $_GET['filter_job_type']);

        if (!isset($query_args['tax_query'])) {
            $query_args['tax_query'] = array();
        }

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
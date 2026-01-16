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
            'nonce' => wp_create_nonce('inspjob_nonce'),
            'apply_nonce' => wp_create_nonce('inspjob_apply'),
            'profile_nonce' => wp_create_nonce('inspjob_update_profile'),
            'my_applications_nonce' => wp_create_nonce('inspjob_my_applications'),
            'manage_applications_nonce' => wp_create_nonce('inspjob_manage_applications'),
            'availability_nonce' => wp_create_nonce('inspjob_availability'),
            'dashboard_url' => home_url('/mi-dashboard/'),
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

    // Campo Cantidad de Empleados/Vacantes
    $fields['job']['job_vacancies'] = array(
        'label'       => 'Cantidad de Vacantes',
        'type'        => 'select',
        'required'    => false,
        'options'     => array(
            ''      => 'Seleccionar...',
            '1'     => '1 vacante',
            '2-5'   => '2 - 5 vacantes',
            '6-10'  => '6 - 10 vacantes',
            '11-20' => '11 - 20 vacantes',
            '20+'   => 'Más de 20 vacantes'
        ),
        'priority'    => 13
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

    $fields['_job_vacancies'] = array(
        'label'       => __('Cantidad de Vacantes', 'inspjob'),
        'type'        => 'select',
        'options'     => array(
            ''      => __('Seleccionar...', 'inspjob'),
            '1'     => __('1 vacante', 'inspjob'),
            '2-5'   => __('2 - 5 vacantes', 'inspjob'),
            '6-10'  => __('6 - 10 vacantes', 'inspjob'),
            '11-20' => __('11 - 20 vacantes', 'inspjob'),
            '20+'   => __('Más de 20 vacantes', 'inspjob')
        ),
        'priority'    => 4.2
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

    if (isset($values['job']['job_vacancies'])) {
        update_post_meta($job_id, '_job_vacancies', sanitize_text_field($values['job']['job_vacancies']));
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

// Shortcode para búsqueda rápida (legacy)
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
 * SHORTCODE: [inspjob_form]
 * Formulario de búsqueda completo con filtros integrados
 *
 * Parámetros:
 * - show_keywords: yes/no (default: yes)
 * - show_location: yes/no (default: yes)
 * - show_categories: yes/no (default: no)
 * - show_salary: yes/no (default: yes)
 * - show_job_type: yes/no (default: yes)
 * - show_remote: yes/no (default: yes)
 * - layout: horizontal/vertical (default: horizontal)
 */
add_shortcode('inspjob_form', 'inspjob_form_shortcode');
function inspjob_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_keywords'   => 'yes',
        'show_location'   => 'yes',
        'show_categories' => 'no',
        'show_salary'     => 'yes',
        'show_job_type'   => 'yes',
        'show_remote'     => 'yes',
        'layout'          => 'horizontal',
    ), $atts);

    // Obtener valores actuales
    $current_keywords   = isset($_GET['search_keywords']) ? sanitize_text_field($_GET['search_keywords']) : '';
    $current_location   = isset($_GET['search_location']) ? sanitize_text_field($_GET['search_location']) : '';
    $current_category   = isset($_GET['search_category']) ? sanitize_text_field($_GET['search_category']) : '';
    $current_salary     = isset($_GET['filter_salary']) ? sanitize_text_field($_GET['filter_salary']) : '';
    $current_job_types  = isset($_GET['filter_job_type']) ? array_map('sanitize_text_field', (array)$_GET['filter_job_type']) : array();
    $current_remote     = isset($_GET['filter_remote']) ? sanitize_text_field($_GET['filter_remote']) : '';

    // Obtener taxonomías
    $job_types = get_terms(array('taxonomy' => 'job_listing_type', 'hide_empty' => false));
    $categories = get_terms(array('taxonomy' => 'job_listing_category', 'hide_empty' => false));

    $layout_class = $atts['layout'] === 'vertical' ? 'inspjob-form-vertical' : 'inspjob-form-horizontal';

    ob_start();
    ?>
    <div class="inspjob-form-wrapper <?php echo esc_attr($layout_class); ?>">
        <form class="inspjob-form" method="get" action="<?php echo esc_url(get_permalink(get_option('job_manager_jobs_page_id'))); ?>">

            <!-- Campos principales de búsqueda -->
            <div class="inspjob-form-main">
                <?php if ($atts['show_keywords'] === 'yes') : ?>
                <div class="inspjob-form-field inspjob-field-keywords">
                    <label for="inspjob-keywords">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Palabras clave
                    </label>
                    <input type="text" id="inspjob-keywords" name="search_keywords" value="<?php echo esc_attr($current_keywords); ?>" placeholder="Título, empresa, habilidades...">
                </div>
                <?php endif; ?>

                <?php if ($atts['show_location'] === 'yes') : ?>
                <div class="inspjob-form-field inspjob-field-location">
                    <label for="inspjob-location">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Ubicación
                    </label>
                    <input type="text" id="inspjob-location" name="search_location" value="<?php echo esc_attr($current_location); ?>" placeholder="Ciudad, distrito...">
                </div>
                <?php endif; ?>

                <?php if ($atts['show_categories'] === 'yes' && !empty($categories) && !is_wp_error($categories)) : ?>
                <div class="inspjob-form-field inspjob-field-category">
                    <label for="inspjob-category">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Categoría
                    </label>
                    <select id="inspjob-category" name="search_category">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($current_category, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="inspjob-form-field inspjob-field-submit">
                    <button type="submit" class="inspjob-btn-search">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Buscar
                    </button>
                </div>
            </div>

            <!-- Filtros adicionales en 3 columnas -->
            <?php if ($atts['show_salary'] === 'yes' || $atts['show_job_type'] === 'yes' || $atts['show_remote'] === 'yes') : ?>
            <div class="inspjob-form-filters">
                <div class="inspjob-filters-grid">
                    <?php if ($atts['show_salary'] === 'yes') : ?>
                    <!-- Columna 1: Salario -->
                    <div class="inspjob-filter-column">
                        <span class="inspjob-filter-title">Salario</span>
                        <div class="inspjob-filter-chips">
                            <label class="inspjob-chip <?php echo $current_salary === '' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="" <?php checked($current_salary, ''); ?>>
                                <span>Todos</span>
                            </label>
                            <label class="inspjob-chip <?php echo $current_salary === '0-2000' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="0-2000" <?php checked($current_salary, '0-2000'); ?>>
                                <span>Hasta S/2k</span>
                            </label>
                            <label class="inspjob-chip <?php echo $current_salary === '2000-4000' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="2000-4000" <?php checked($current_salary, '2000-4000'); ?>>
                                <span>S/2k - 4k</span>
                            </label>
                            <label class="inspjob-chip <?php echo $current_salary === '4000-6000' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="4000-6000" <?php checked($current_salary, '4000-6000'); ?>>
                                <span>S/4k - 6k</span>
                            </label>
                            <label class="inspjob-chip <?php echo $current_salary === '6000-10000' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="6000-10000" <?php checked($current_salary, '6000-10000'); ?>>
                                <span>S/6k - 10k</span>
                            </label>
                            <label class="inspjob-chip <?php echo $current_salary === '10000+' ? 'active' : ''; ?>">
                                <input type="radio" name="filter_salary" value="10000+" <?php checked($current_salary, '10000+'); ?>>
                                <span>+S/10k</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($atts['show_job_type'] === 'yes' && !empty($job_types) && !is_wp_error($job_types)) : ?>
                    <!-- Columna 2: Tipo -->
                    <div class="inspjob-filter-column">
                        <span class="inspjob-filter-title">Tipo</span>
                        <div class="inspjob-filter-chips">
                            <?php foreach ($job_types as $type) : ?>
                            <label class="inspjob-chip <?php echo in_array($type->slug, $current_job_types) ? 'active' : ''; ?>">
                                <input type="checkbox" name="filter_job_type[]" value="<?php echo esc_attr($type->slug); ?>" <?php checked(in_array($type->slug, $current_job_types)); ?>>
                                <span><?php echo esc_html($type->name); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($atts['show_remote'] === 'yes') : ?>
                    <!-- Columna 3: Remoto -->
                    <div class="inspjob-filter-column inspjob-filter-column-remote">
                        <span class="inspjob-filter-title">Modalidad</span>
                        <label class="inspjob-btn-remote <?php echo $current_remote === '1' ? 'active' : ''; ?>">
                            <input type="checkbox" name="filter_remote" value="1" <?php checked($current_remote, '1'); ?>>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span>Solo Remoto</span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Limpiar filtros -->
                <div class="inspjob-filter-clear-wrap">
                    <a href="<?php echo esc_url(get_permalink(get_option('job_manager_jobs_page_id'))); ?>" class="inspjob-btn-clear">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Limpiar filtros
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Filtrar por trabajo remoto
add_filter('job_manager_get_listings', 'inspjob_filter_by_remote_checkbox', 10, 2);
function inspjob_filter_by_remote_checkbox($query_args, $args) {
    if (!empty($_GET['filter_remote']) && $_GET['filter_remote'] === '1') {
        if (!isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array();
        }
        $query_args['meta_query'][] = array(
            'key'     => '_remote_work',
            'value'   => '1',
            'compare' => '='
        );
    }
    return $query_args;
}

// Filtrar por categoría
add_filter('job_manager_get_listings', 'inspjob_filter_by_category', 10, 2);
function inspjob_filter_by_category($query_args, $args) {
    if (!empty($_GET['search_category'])) {
        if (!isset($query_args['tax_query'])) {
            $query_args['tax_query'] = array();
        }
        $query_args['tax_query'][] = array(
            'taxonomy' => 'job_listing_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['search_category']),
        );
    }
    return $query_args;
}

/**
 * SHORTCODE: [inspjob_cards]
 * Mostrar cards de trabajos con control total
 *
 * Parámetros:
 * - per_page: número de trabajos (default: 12)
 * - columns: 1-4 columnas (default: 3)
 * - orderby: date/title/rand (default: date)
 * - order: ASC/DESC (default: DESC)
 * - featured: yes/no/only (default: no) - only = solo destacados
 * - filled: yes/no (default: no) - mostrar trabajos llenos
 * - categories: slugs separados por coma
 * - job_types: slugs separados por coma
 * - show_pagination: yes/no (default: yes)
 * - show_count: yes/no (default: yes) - mostrar contador
 */
add_shortcode('inspjob_cards', 'inspjob_cards_shortcode');
function inspjob_cards_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page'        => 12,
        'columns'         => 3,
        'orderby'         => 'date',
        'order'           => 'DESC',
        'featured'        => 'no',
        'filled'          => 'no',
        'categories'      => '',
        'job_types'       => '',
        'show_pagination' => 'yes',
        'show_count'      => 'yes',
    ), $atts);

    // Construir argumentos de query
    $query_args = array(
        'post_type'      => 'job_listing',
        'post_status'    => 'publish',
        'posts_per_page' => intval($atts['per_page']),
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'paged'          => max(1, get_query_var('paged')),
    );

    // Meta query
    $meta_query = array();

    // Featured
    if ($atts['featured'] === 'only') {
        $meta_query[] = array(
            'key'   => '_featured',
            'value' => '1',
        );
    } elseif ($atts['featured'] === 'yes') {
        $query_args['orderby'] = array(
            'meta_value_num' => 'DESC',
            $atts['orderby'] => $atts['order'],
        );
        $query_args['meta_key'] = '_featured';
    }

    // Filled positions
    if ($atts['filled'] === 'no') {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_filled',
                'value'   => '0',
            ),
            array(
                'key'     => '_filled',
                'compare' => 'NOT EXISTS',
            ),
        );
    }

    // Aplicar filtros de búsqueda desde URL
    if (!empty($_GET['search_keywords'])) {
        $query_args['s'] = sanitize_text_field($_GET['search_keywords']);
    }

    if (!empty($_GET['search_location'])) {
        $meta_query[] = array(
            'key'     => '_job_location',
            'value'   => sanitize_text_field($_GET['search_location']),
            'compare' => 'LIKE',
        );
    }

    // Filtro de salario
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
            $meta_query[] = array(
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

    // Filtro remoto
    if (!empty($_GET['filter_remote']) && $_GET['filter_remote'] === '1') {
        $meta_query[] = array(
            'key'     => '_remote_work',
            'value'   => '1',
            'compare' => '='
        );
    }

    // Filtro de vacantes
    if (!empty($_GET['filter_vacancies'])) {
        $meta_query[] = array(
            'key'     => '_job_vacancies',
            'value'   => sanitize_text_field($_GET['filter_vacancies']),
            'compare' => '='
        );
    }

    // Filtro de ubicación (dropdown)
    if (!empty($_GET['filter_location'])) {
        $meta_query[] = array(
            'key'     => '_job_location',
            'value'   => sanitize_text_field($_GET['filter_location']),
            'compare' => '='
        );
    }

    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    // Filtro de fecha de publicación
    if (!empty($_GET['filter_date'])) {
        $date_filter = sanitize_text_field($_GET['filter_date']);
        $date_ranges = array(
            'today'   => 'today',
            '3days'   => '-3 days',
            'week'    => '-1 week',
            '15days'  => '-15 days',
            'month'   => '-1 month',
        );
        if (isset($date_ranges[$date_filter])) {
            $query_args['date_query'] = array(
                array(
                    'after'     => $date_ranges[$date_filter],
                    'inclusive' => true,
                ),
            );
        }
    }

    // Tax query
    $tax_query = array();

    // Categorías del shortcode
    if (!empty($atts['categories'])) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_category',
            'field'    => 'slug',
            'terms'    => array_map('trim', explode(',', $atts['categories'])),
        );
    }

    // Categoría desde URL
    if (!empty($_GET['search_category'])) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['search_category']),
        );
    }

    // Tipos de trabajo del shortcode
    if (!empty($atts['job_types'])) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => array_map('trim', explode(',', $atts['job_types'])),
        );
    }

    // Tipos de trabajo desde URL
    if (!empty($_GET['filter_job_type']) && is_array($_GET['filter_job_type'])) {
        $tax_query[] = array(
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => array_map('sanitize_text_field', $_GET['filter_job_type']),
            'operator' => 'IN'
        );
    }

    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $jobs = new WP_Query($query_args);

    ob_start();
    ?>
    <div class="inspjob-cards-wrapper">

        <?php if ($atts['show_count'] === 'yes') : ?>
        <div class="inspjob-cards-header">
            <p class="inspjob-jobs-count">
                <?php
                $total = $jobs->found_posts;
                echo sprintf(
                    _n('%s trabajo encontrado', '%s trabajos encontrados', $total, 'inspjob'),
                    '<strong>' . number_format_i18n($total) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($jobs->have_posts()) : ?>
        <ul class="inspjob-cards-grid inspjob-cols-<?php echo intval($atts['columns']); ?>">
            <?php while ($jobs->have_posts()) : $jobs->the_post(); ?>
                <?php get_job_manager_template_part('content', 'job_listing'); ?>
            <?php endwhile; ?>
        </ul>

        <?php if ($atts['show_pagination'] === 'yes' && $jobs->max_num_pages > 1) : ?>
        <div class="inspjob-pagination">
            <?php
            echo paginate_links(array(
                'total'     => $jobs->max_num_pages,
                'current'   => max(1, get_query_var('paged')),
                'prev_text' => '&laquo; Anterior',
                'next_text' => 'Siguiente &raquo;',
            ));
            ?>
        </div>
        <?php endif; ?>

        <?php else : ?>
        <div class="inspjob-no-jobs">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <h3>No se encontraron trabajos</h3>
            <p>Intenta ajustar los filtros o busca con otros términos.</p>
            <a href="<?php echo esc_url(get_permalink(get_option('job_manager_jobs_page_id'))); ?>" class="inspjob-btn-reset">Ver todos los trabajos</a>
        </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * SHORTCODE: [inspjob_filters]
 * Filtros secundarios con dropdowns: Ubicación, Vacantes, Tiempo de publicación
 * Para usar debajo del formulario de búsqueda
 */
add_shortcode('inspjob_filters', 'inspjob_filters_shortcode');
function inspjob_filters_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_location' => 'yes',
        'show_vacancies' => 'yes',
        'show_date' => 'yes',
    ), $atts);

    // Valores actuales de los filtros
    $current_location = isset($_GET['filter_location']) ? sanitize_text_field($_GET['filter_location']) : '';
    $current_vacancies = isset($_GET['filter_vacancies']) ? sanitize_text_field($_GET['filter_vacancies']) : '';
    $current_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';

    // Obtener ubicaciones únicas de los trabajos publicados
    $locations = array();
    if ($atts['show_location'] === 'yes') {
        global $wpdb;
        $locations = $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_job_location'
            AND pm.meta_value != ''
            AND p.post_type = 'job_listing'
            AND p.post_status = 'publish'
            ORDER BY pm.meta_value ASC"
        );
    }

    ob_start();
    ?>
    <div class="inspjob-filters-wrapper inspjob-filters-dropdowns">
        <form class="inspjob-filters-form" method="get" action="<?php echo esc_url(get_permalink(get_option('job_manager_jobs_page_id'))); ?>">
            <?php // Preservar otros parámetros de búsqueda ?>
            <?php if (isset($_GET['search_keywords'])) : ?>
                <input type="hidden" name="search_keywords" value="<?php echo esc_attr($_GET['search_keywords']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['filter_salary'])) : ?>
                <input type="hidden" name="filter_salary" value="<?php echo esc_attr($_GET['filter_salary']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['filter_job_type']) && is_array($_GET['filter_job_type'])) : ?>
                <?php foreach ($_GET['filter_job_type'] as $type) : ?>
                    <input type="hidden" name="filter_job_type[]" value="<?php echo esc_attr($type); ?>">
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (isset($_GET['filter_remote'])) : ?>
                <input type="hidden" name="filter_remote" value="<?php echo esc_attr($_GET['filter_remote']); ?>">
            <?php endif; ?>

            <div class="inspjob-filters-dropdown-container">
                <?php if ($atts['show_location'] === 'yes' && !empty($locations)) : ?>
                <!-- Dropdown Ubicación -->
                <div class="inspjob-filter-dropdown">
                    <label for="filter_location">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Ubicación
                    </label>
                    <select name="filter_location" id="filter_location">
                        <option value="">Todas las ubicaciones</option>
                        <?php foreach ($locations as $location) : ?>
                        <option value="<?php echo esc_attr($location); ?>" <?php selected($current_location, $location); ?>>
                            <?php echo esc_html($location); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($atts['show_vacancies'] === 'yes') : ?>
                <!-- Dropdown Vacantes -->
                <div class="inspjob-filter-dropdown">
                    <label for="filter_vacancies">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Vacantes
                    </label>
                    <select name="filter_vacancies" id="filter_vacancies">
                        <option value="">Todas</option>
                        <option value="1" <?php selected($current_vacancies, '1'); ?>>1 vacante</option>
                        <option value="2-5" <?php selected($current_vacancies, '2-5'); ?>>2 - 5 vacantes</option>
                        <option value="6-10" <?php selected($current_vacancies, '6-10'); ?>>6 - 10 vacantes</option>
                        <option value="11-20" <?php selected($current_vacancies, '11-20'); ?>>11 - 20 vacantes</option>
                        <option value="20+" <?php selected($current_vacancies, '20+'); ?>>Más de 20</option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($atts['show_date'] === 'yes') : ?>
                <!-- Dropdown Tiempo de Publicación -->
                <div class="inspjob-filter-dropdown">
                    <label for="filter_date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Publicado
                    </label>
                    <select name="filter_date" id="filter_date">
                        <option value="">Cualquier fecha</option>
                        <option value="today" <?php selected($current_date, 'today'); ?>>Hoy</option>
                        <option value="3days" <?php selected($current_date, '3days'); ?>>Últimos 3 días</option>
                        <option value="week" <?php selected($current_date, 'week'); ?>>Última semana</option>
                        <option value="15days" <?php selected($current_date, '15days'); ?>>Últimos 15 días</option>
                        <option value="month" <?php selected($current_date, 'month'); ?>>Último mes</option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Botón Aplicar -->
                <div class="inspjob-filter-dropdown inspjob-filter-submit">
                    <button type="submit" class="inspjob-btn-filter">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filtrar
                    </button>
                    <?php
                    $has_filters = !empty($current_location) || !empty($current_vacancies) || !empty($current_date);
                    if ($has_filters) :
                    ?>
                    <a href="<?php echo esc_url(get_permalink(get_option('job_manager_jobs_page_id'))); ?>" class="inspjob-btn-clear-filters">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Limpiar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Filtrar trabajos por ubicación exacta
add_filter('job_manager_get_listings', 'inspjob_filter_by_location_dropdown', 10, 2);
function inspjob_filter_by_location_dropdown($query_args, $args) {
    if (!empty($_GET['filter_location'])) {
        if (!isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array();
        }
        $query_args['meta_query'][] = array(
            'key'     => '_job_location',
            'value'   => sanitize_text_field($_GET['filter_location']),
            'compare' => '='
        );
    }
    return $query_args;
}

// Filtrar trabajos por cantidad de vacantes
add_filter('job_manager_get_listings', 'inspjob_filter_by_vacancies', 10, 2);
function inspjob_filter_by_vacancies($query_args, $args) {
    if (!empty($_GET['filter_vacancies'])) {
        $vacancies = sanitize_text_field($_GET['filter_vacancies']);

        if (!isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array();
        }

        $query_args['meta_query'][] = array(
            'key'     => '_job_vacancies',
            'value'   => $vacancies,
            'compare' => '='
        );
    }
    return $query_args;
}

// Filtrar trabajos por fecha de publicación
add_filter('job_manager_get_listings', 'inspjob_filter_by_date', 10, 2);
function inspjob_filter_by_date($query_args, $args) {
    if (!empty($_GET['filter_date'])) {
        $date_filter = sanitize_text_field($_GET['filter_date']);

        $date_ranges = array(
            'today'   => 'today',
            '3days'   => '-3 days',
            'week'    => '-1 week',
            '15days'  => '-15 days',
            'month'   => '-1 month',
        );

        if (isset($date_ranges[$date_filter])) {
            $query_args['date_query'] = array(
                array(
                    'after'     => $date_ranges[$date_filter],
                    'inclusive' => true,
                ),
            );
        }
    }
    return $query_args;
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

/**
 * CARGAR CLASES ADICIONALES DEL SISTEMA
 * ======================================
 */

// Database Migration - Must load first
require_once get_stylesheet_directory() . '/inc/database-migration.php';

// Core Classes
require_once get_stylesheet_directory() . '/inc/class-job-seeker.php';
require_once get_stylesheet_directory() . '/inc/class-application-tracker.php';
require_once get_stylesheet_directory() . '/inc/class-matching-engine.php';
require_once get_stylesheet_directory() . '/inc/class-salary-transparency.php';
require_once get_stylesheet_directory() . '/inc/class-employer-score.php';

// Anti-Ghosting System
require_once get_stylesheet_directory() . '/inc/class-sla-commitment.php';
require_once get_stylesheet_directory() . '/inc/class-application-timeline.php';
require_once get_stylesheet_directory() . '/inc/class-ghost-cleanup.php';

// Gamification and Reverse Applications
require_once get_stylesheet_directory() . '/inc/class-gamification.php';
require_once get_stylesheet_directory() . '/inc/class-reverse-application.php';

// Email Notifications
require_once get_stylesheet_directory() . '/inc/email-notifications.php';
?>
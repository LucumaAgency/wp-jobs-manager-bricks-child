<?php
/**
 * Job Seeker Role and Profile Management
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Job_Seeker {

    /**
     * User meta keys for job seeker profile
     */
    const META_KEYS = [
        'headline'           => '_job_seeker_headline',
        'bio'                => '_job_seeker_bio',
        'skills'             => '_job_seeker_skills',
        'experience_level'   => '_job_seeker_experience_level',
        'salary_min'         => '_job_seeker_salary_min',
        'salary_max'         => '_job_seeker_salary_max',
        'location'           => '_job_seeker_location',
        'remote_preference'  => '_job_seeker_remote_preference',
        'availability'       => '_job_seeker_availability',
        'categories'         => '_job_seeker_categories',
        'job_types'          => '_job_seeker_job_types',
        'profile_completion' => '_job_seeker_profile_completion',
        'level'              => '_job_seeker_level',
        'points'             => '_job_seeker_points',
        'resume_url'         => '_job_seeker_resume_url',
        'phone'              => '_job_seeker_phone',
        'linkedin'           => '_job_seeker_linkedin',
        'portfolio'          => '_job_seeker_portfolio',
    ];

    /**
     * Experience levels
     */
    const EXPERIENCE_LEVELS = [
        'entry'  => 'Sin experiencia',
        'junior' => 'Junior (1-2 años)',
        'mid'    => 'Mid-Level (3-5 años)',
        'senior' => 'Senior (5-8 años)',
        'expert' => 'Experto (8+ años)',
    ];

    /**
     * Remote preferences
     */
    const REMOTE_PREFERENCES = [
        'yes'    => 'Solo remoto',
        'hybrid' => 'Hibrido',
        'no'     => 'Presencial',
    ];

    /**
     * Availability options
     */
    const AVAILABILITY_OPTIONS = [
        'immediate' => 'Inmediata',
        '2weeks'    => 'En 2 semanas',
        '1month'    => 'En 1 mes',
        'other'     => 'Otro',
    ];

    /**
     * Initialize the class
     */
    public static function init() {
        // Register role
        add_action('init', [__CLASS__, 'register_role']);

        // Register shortcodes
        add_shortcode('inspjob_register_job_seeker', [__CLASS__, 'render_registration_form']);
        add_shortcode('inspjob_job_seeker_profile', [__CLASS__, 'render_profile_form']);
        add_shortcode('inspjob_job_seeker_dashboard', [__CLASS__, 'render_dashboard']);

        // AJAX handlers
        add_action('wp_ajax_nopriv_inspjob_register_job_seeker', [__CLASS__, 'ajax_register']);
        add_action('wp_ajax_inspjob_update_job_seeker_profile', [__CLASS__, 'ajax_update_profile']);
        add_action('wp_ajax_inspjob_upload_resume', [__CLASS__, 'ajax_upload_resume']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);

        // Profile completion recalculation
        add_action('profile_update', [__CLASS__, 'recalculate_profile_completion']);
    }

    /**
     * Register the job_seeker role
     */
    public static function register_role() {
        if (!get_role('job_seeker')) {
            add_role(
                'job_seeker',
                __('Candidato', 'flavor-starter'),
                [
                    'read'         => true,
                    'edit_posts'   => false,
                    'delete_posts' => false,
                    'upload_files' => true,
                ]
            );
        }
    }

    /**
     * Enqueue scripts for job seeker pages
     */
    public static function enqueue_scripts() {
        if (!is_page(['registro-candidato', 'mi-perfil', 'mi-dashboard', 'dashboard-candidato'])) {
            return;
        }

        wp_enqueue_media();
    }

    /**
     * Check if user is a job seeker
     */
    public static function is_job_seeker($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        return $user && in_array('job_seeker', (array) $user->roles);
    }

    /**
     * Get job seeker profile data
     */
    public static function get_profile($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return null;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }

        $profile = [
            'user_id'      => $user_id,
            'display_name' => $user->display_name,
            'email'        => $user->user_email,
            'avatar_url'   => get_avatar_url($user_id, ['size' => 200]),
        ];

        foreach (self::META_KEYS as $key => $meta_key) {
            $value = get_user_meta($user_id, $meta_key, true);

            // Decode JSON fields
            if (in_array($key, ['skills', 'categories', 'job_types']) && !empty($value)) {
                $value = json_decode($value, true) ?: [];
            }

            $profile[$key] = $value;
        }

        return $profile;
    }

    /**
     * Update job seeker profile
     */
    public static function update_profile($user_id, $data) {
        if (!$user_id || !self::is_job_seeker($user_id)) {
            return new WP_Error('invalid_user', 'Usuario no valido');
        }

        $allowed_fields = array_keys(self::META_KEYS);

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                continue;
            }

            $meta_key = self::META_KEYS[$key];

            // Encode JSON fields
            if (in_array($key, ['skills', 'categories', 'job_types']) && is_array($value)) {
                $value = json_encode($value);
            }

            // Sanitize based on field type
            switch ($key) {
                case 'bio':
                    $value = sanitize_textarea_field($value);
                    break;
                case 'salary_min':
                case 'salary_max':
                case 'points':
                    $value = absint($value);
                    break;
                case 'linkedin':
                case 'portfolio':
                case 'resume_url':
                    $value = esc_url_raw($value);
                    break;
                default:
                    if (!is_array($value)) {
                        $value = sanitize_text_field($value);
                    }
            }

            update_user_meta($user_id, $meta_key, $value);
        }

        // Recalculate profile completion
        self::recalculate_profile_completion($user_id);

        return true;
    }

    /**
     * Calculate and update profile completion percentage
     */
    public static function recalculate_profile_completion($user_id) {
        $profile = self::get_profile($user_id);
        if (!$profile) {
            return 0;
        }

        $weights = [
            'headline'          => 10,
            'bio'               => 15,
            'skills'            => 15,
            'experience_level'  => 10,
            'salary_min'        => 5,
            'salary_max'        => 5,
            'location'          => 10,
            'remote_preference' => 5,
            'availability'      => 5,
            'categories'        => 10,
            'resume_url'        => 10,
        ];

        $total = 0;
        $completed = 0;

        foreach ($weights as $field => $weight) {
            $total += $weight;
            $value = $profile[$field] ?? '';

            if (is_array($value)) {
                if (!empty($value)) {
                    $completed += $weight;
                }
            } elseif (!empty($value)) {
                $completed += $weight;
            }
        }

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        update_user_meta($user_id, self::META_KEYS['profile_completion'], $percentage);

        // Update level based on points and completion
        self::update_level($user_id);

        return $percentage;
    }

    /**
     * Update user level based on points
     */
    public static function update_level($user_id) {
        $points = (int) get_user_meta($user_id, self::META_KEYS['points'], true);

        $levels = [
            'platinum' => 600,
            'gold'     => 300,
            'silver'   => 100,
            'bronze'   => 0,
        ];

        $new_level = 'bronze';
        foreach ($levels as $level => $min_points) {
            if ($points >= $min_points) {
                $new_level = $level;
                break;
            }
        }

        update_user_meta($user_id, self::META_KEYS['level'], $new_level);

        return $new_level;
    }

    /**
     * Add points to user
     */
    public static function add_points($user_id, $points) {
        $current_points = (int) get_user_meta($user_id, self::META_KEYS['points'], true);
        $new_points = $current_points + $points;

        update_user_meta($user_id, self::META_KEYS['points'], $new_points);
        self::update_level($user_id);

        return $new_points;
    }

    /**
     * AJAX: Register new job seeker
     */
    public static function ajax_register() {
        check_ajax_referer('inspjob_register_job_seeker', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');

        // Validation
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Email invalido']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'El email ya esta registrado']);
        }

        if (strlen($password) < 8) {
            wp_send_json_error(['message' => 'La contrasena debe tener al menos 8 caracteres']);
        }

        if (empty($first_name)) {
            wp_send_json_error(['message' => 'El nombre es obligatorio']);
        }

        // Create user
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Update user data
        wp_update_user([
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role'         => 'job_seeker',
        ]);

        // Initialize profile meta
        update_user_meta($user_id, self::META_KEYS['level'], 'bronze');
        update_user_meta($user_id, self::META_KEYS['points'], 0);
        update_user_meta($user_id, self::META_KEYS['profile_completion'], 0);

        // Auto-login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Trigger action for gamification
        do_action('inspjob_job_seeker_registered', $user_id);

        wp_send_json_success([
            'message'  => 'Registro exitoso',
            'redirect' => home_url('/mi-perfil/'),
        ]);
    }

    /**
     * AJAX: Update job seeker profile
     */
    public static function ajax_update_profile() {
        check_ajax_referer('inspjob_update_profile', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id || !self::is_job_seeker($user_id)) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $data = [];

        // Text fields
        $text_fields = ['headline', 'bio', 'location', 'phone', 'linkedin', 'portfolio'];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }

        // Select fields
        $select_fields = ['experience_level', 'remote_preference', 'availability'];
        foreach ($select_fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        // Numeric fields
        if (isset($_POST['salary_min'])) {
            $data['salary_min'] = absint($_POST['salary_min']);
        }
        if (isset($_POST['salary_max'])) {
            $data['salary_max'] = absint($_POST['salary_max']);
        }

        // Array fields
        if (isset($_POST['skills'])) {
            $data['skills'] = array_map('sanitize_text_field', (array) $_POST['skills']);
        }
        if (isset($_POST['categories'])) {
            $data['categories'] = array_map('absint', (array) $_POST['categories']);
        }
        if (isset($_POST['job_types'])) {
            $data['job_types'] = array_map('sanitize_text_field', (array) $_POST['job_types']);
        }

        // Update display name if first/last name provided
        if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
            $user_data = ['ID' => $user_id];
            if (isset($_POST['first_name'])) {
                $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
            }
            if (isset($_POST['last_name'])) {
                $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
            }
            if (isset($user_data['first_name']) && isset($user_data['last_name'])) {
                $user_data['display_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            }
            wp_update_user($user_data);
        }

        $result = self::update_profile($user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $profile = self::get_profile($user_id);

        wp_send_json_success([
            'message'            => 'Perfil actualizado correctamente',
            'profile_completion' => $profile['profile_completion'],
            'level'              => $profile['level'],
        ]);
    }

    /**
     * AJAX: Upload resume
     */
    public static function ajax_upload_resume() {
        check_ajax_referer('inspjob_upload_resume', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id || !self::is_job_seeker($user_id)) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        if (empty($_FILES['resume'])) {
            wp_send_json_error(['message' => 'No se ha seleccionado ningun archivo']);
        }

        $file = $_FILES['resume'];

        // Validate file type
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'Tipo de archivo no permitido. Solo se aceptan PDF y DOC/DOCX']);
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => 'El archivo es demasiado grande. Maximo 5MB']);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }

        // Save resume URL to user meta
        update_user_meta($user_id, self::META_KEYS['resume_url'], $upload['url']);

        // Recalculate profile completion
        self::recalculate_profile_completion($user_id);

        wp_send_json_success([
            'message' => 'CV subido correctamente',
            'url'     => $upload['url'],
        ]);
    }

    /**
     * Render registration form shortcode
     */
    public static function render_registration_form($atts) {
        if (is_user_logged_in()) {
            return '<p class="inspjob-notice">Ya tienes una cuenta. <a href="' . esc_url(home_url('/mi-perfil/')) . '">Ir a mi perfil</a></p>';
        }

        ob_start();
        include get_stylesheet_directory() . '/job_manager/job-seeker-register.php';
        return ob_get_clean();
    }

    /**
     * Render profile form shortcode
     */
    public static function render_profile_form($atts) {
        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes <a href="' . esc_url(home_url('/iniciar-sesion/')) . '">iniciar sesion</a> para ver tu perfil.</p>';
        }

        if (!self::is_job_seeker()) {
            return '<p class="inspjob-notice">Esta pagina es solo para candidatos.</p>';
        }

        $profile = self::get_profile();

        ob_start();
        include get_stylesheet_directory() . '/job_manager/job-seeker-profile.php';
        return ob_get_clean();
    }

    /**
     * Render dashboard shortcode
     */
    public static function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes <a href="' . esc_url(home_url('/iniciar-sesion/')) . '">iniciar sesion</a> para ver tu dashboard.</p>';
        }

        if (!self::is_job_seeker()) {
            return '<p class="inspjob-notice">Esta pagina es solo para candidatos.</p>';
        }

        $profile = self::get_profile();

        ob_start();
        include get_stylesheet_directory() . '/job_manager/job-seeker-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Get formatted level name
     */
    public static function get_level_label($level) {
        $labels = [
            'bronze'   => 'Bronce',
            'silver'   => 'Plata',
            'gold'     => 'Oro',
            'platinum' => 'Platino',
        ];

        return $labels[$level] ?? 'Bronce';
    }

    /**
     * Get level icon class
     */
    public static function get_level_icon($level) {
        $icons = [
            'bronze'   => 'medal-bronze',
            'silver'   => 'medal-silver',
            'gold'     => 'medal-gold',
            'platinum' => 'medal-platinum',
        ];

        return $icons[$level] ?? 'medal-bronze';
    }

    /**
     * Search job seekers with filters
     */
    public static function search_job_seekers($args = []) {
        $defaults = [
            'experience_level'  => '',
            'location'          => '',
            'remote_preference' => '',
            'categories'        => [],
            'skills'            => [],
            'number'            => 20,
            'offset'            => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $meta_query = [];

        if (!empty($args['experience_level'])) {
            $meta_query[] = [
                'key'   => self::META_KEYS['experience_level'],
                'value' => $args['experience_level'],
            ];
        }

        if (!empty($args['location'])) {
            $meta_query[] = [
                'key'     => self::META_KEYS['location'],
                'value'   => $args['location'],
                'compare' => 'LIKE',
            ];
        }

        if (!empty($args['remote_preference'])) {
            $meta_query[] = [
                'key'   => self::META_KEYS['remote_preference'],
                'value' => $args['remote_preference'],
            ];
        }

        $user_query_args = [
            'role'       => 'job_seeker',
            'number'     => $args['number'],
            'offset'     => $args['offset'],
            'meta_query' => $meta_query,
        ];

        $users = get_users($user_query_args);

        $job_seekers = [];
        foreach ($users as $user) {
            $job_seekers[] = self::get_profile($user->ID);
        }

        return $job_seekers;
    }
}

// Initialize
InspJob_Job_Seeker::init();

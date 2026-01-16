<?php
/**
 * Reverse Application System
 * Allows job seekers to publish their availability for employers to find
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Reverse_Application {

    /**
     * Custom post type name
     */
    const POST_TYPE = 'candidate_availability';

    /**
     * Meta keys
     */
    const META_KEYS = [
        'status'        => '_availability_status',
        'job_types'     => '_availability_job_types',
        'salary_min'    => '_availability_salary_min',
        'salary_max'    => '_availability_salary_max',
        'start_date'    => '_availability_start_date',
        'views'         => '_availability_views',
        'contacts'      => '_availability_contacts',
        'categories'    => '_availability_categories',
        'experience'    => '_availability_experience',
        'remote_pref'   => '_availability_remote_preference',
        'location'      => '_availability_location',
    ];

    /**
     * Status options
     */
    const STATUSES = [
        'active' => 'Activo',
        'paused' => 'Pausado',
    ];

    /**
     * Initialize the system
     */
    public static function init() {
        // Register post type
        add_action('init', [__CLASS__, 'register_post_type']);

        // Shortcodes
        add_shortcode('inspjob_post_availability', [__CLASS__, 'render_post_form']);
        add_shortcode('inspjob_available_candidates', [__CLASS__, 'render_candidates_list']);
        add_shortcode('inspjob_my_availability', [__CLASS__, 'render_my_availability']);

        // AJAX handlers
        add_action('wp_ajax_inspjob_save_availability', [__CLASS__, 'ajax_save_availability']);
        add_action('wp_ajax_inspjob_toggle_availability', [__CLASS__, 'ajax_toggle_availability']);
        add_action('wp_ajax_inspjob_contact_candidate', [__CLASS__, 'ajax_contact_candidate']);
        add_action('wp_ajax_inspjob_track_view', [__CLASS__, 'ajax_track_view']);

        // Template redirect for single view
        add_filter('template_include', [__CLASS__, 'template_include']);
    }

    /**
     * Register custom post type
     */
    public static function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'               => 'Candidatos Disponibles',
                'singular_name'      => 'Disponibilidad',
                'add_new'            => 'Publicar Disponibilidad',
                'add_new_item'       => 'Publicar Mi Disponibilidad',
                'edit_item'          => 'Editar Disponibilidad',
                'view_item'          => 'Ver Disponibilidad',
                'search_items'       => 'Buscar Candidatos',
                'not_found'          => 'No se encontraron candidatos',
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-id',
            'supports'           => ['title', 'editor', 'author'],
            'rewrite'            => ['slug' => 'candidatos-disponibles'],
            'has_archive'        => true,
            'capability_type'    => 'post',
        ]);
    }

    /**
     * Get user availability post
     */
    public static function get_user_availability($user_id) {
        $posts = get_posts([
            'post_type'   => self::POST_TYPE,
            'author'      => $user_id,
            'post_status' => ['publish', 'draft'],
            'numberposts' => 1,
        ]);

        return !empty($posts) ? $posts[0] : null;
    }

    /**
     * Get availability data
     */
    public static function get_availability_data($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return null;
        }

        $data = [
            'id'          => $post->ID,
            'user_id'     => $post->post_author,
            'title'       => $post->post_title,
            'description' => $post->post_content,
            'created'     => $post->post_date,
            'modified'    => $post->post_modified,
            'is_active'   => $post->post_status === 'publish',
        ];

        foreach (self::META_KEYS as $key => $meta_key) {
            $value = get_post_meta($post_id, $meta_key, true);

            // Decode JSON fields
            if (in_array($key, ['job_types', 'categories'])) {
                $value = $value ? json_decode($value, true) : [];
            }

            $data[$key] = $value;
        }

        // Get user profile data
        if (class_exists('InspJob_Job_Seeker')) {
            $data['profile'] = InspJob_Job_Seeker::get_profile($post->post_author);
        }

        return $data;
    }

    /**
     * Create or update availability
     */
    public static function save_availability($user_id, $data) {
        // Check if user already has an availability post
        $existing = self::get_user_availability($user_id);

        $post_data = [
            'post_type'    => self::POST_TYPE,
            'post_title'   => sanitize_text_field($data['title'] ?? ''),
            'post_content' => sanitize_textarea_field($data['description'] ?? ''),
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ];

        if ($existing) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        if (isset($data['salary_min'])) {
            update_post_meta($post_id, self::META_KEYS['salary_min'], absint($data['salary_min']));
        }
        if (isset($data['salary_max'])) {
            update_post_meta($post_id, self::META_KEYS['salary_max'], absint($data['salary_max']));
        }
        if (isset($data['start_date'])) {
            update_post_meta($post_id, self::META_KEYS['start_date'], sanitize_text_field($data['start_date']));
        }
        if (isset($data['job_types'])) {
            update_post_meta($post_id, self::META_KEYS['job_types'], json_encode((array) $data['job_types']));
        }
        if (isset($data['categories'])) {
            update_post_meta($post_id, self::META_KEYS['categories'], json_encode((array) $data['categories']));
        }
        if (isset($data['experience'])) {
            update_post_meta($post_id, self::META_KEYS['experience'], sanitize_text_field($data['experience']));
        }
        if (isset($data['remote_pref'])) {
            update_post_meta($post_id, self::META_KEYS['remote_pref'], sanitize_text_field($data['remote_pref']));
        }
        if (isset($data['location'])) {
            update_post_meta($post_id, self::META_KEYS['location'], sanitize_text_field($data['location']));
        }

        // Initialize counters
        if (!$existing) {
            update_post_meta($post_id, self::META_KEYS['views'], 0);
            update_post_meta($post_id, self::META_KEYS['contacts'], 0);
            update_post_meta($post_id, self::META_KEYS['status'], 'active');
        }

        return $post_id;
    }

    /**
     * Record employer contact
     */
    public static function record_contact($availability_id, $employer_id, $message = '', $job_id = 0) {
        global $wpdb;

        $availability = get_post($availability_id);
        if (!$availability || $availability->post_type !== self::POST_TYPE) {
            return new WP_Error('invalid', 'Disponibilidad no encontrada');
        }

        $table = $wpdb->prefix . 'inspjob_employer_contacts';

        // Check if already contacted
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE availability_id = %d AND employer_id = %d",
            $availability_id,
            $employer_id
        ));

        if ($existing) {
            return new WP_Error('already_contacted', 'Ya has contactado a este candidato');
        }

        $result = $wpdb->insert(
            $table,
            [
                'availability_id' => $availability_id,
                'employer_id'     => $employer_id,
                'candidate_id'    => $availability->post_author,
                'job_id'          => $job_id ?: null,
                'message'         => sanitize_textarea_field($message),
                'created_at'      => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s']
        );

        if ($result) {
            // Increment contact count
            $contacts = (int) get_post_meta($availability_id, self::META_KEYS['contacts'], true);
            update_post_meta($availability_id, self::META_KEYS['contacts'], $contacts + 1);

            // Trigger action for notification
            do_action('inspjob_candidate_contacted', $availability_id, $employer_id, $availability->post_author);

            return $wpdb->insert_id;
        }

        return new WP_Error('db_error', 'Error al guardar el contacto');
    }

    /**
     * Track view
     */
    public static function track_view($availability_id) {
        $views = (int) get_post_meta($availability_id, self::META_KEYS['views'], true);
        update_post_meta($availability_id, self::META_KEYS['views'], $views + 1);
    }

    /**
     * Get available candidates with filters
     */
    public static function get_candidates($args = []) {
        $defaults = [
            'categories'  => [],
            'experience'  => '',
            'remote_pref' => '',
            'salary_max'  => 0,
            'location'    => '',
            'per_page'    => 12,
            'paged'       => 1,
        ];

        $args = wp_parse_args($args, $defaults);

        $query_args = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['paged'],
            'meta_query'     => [],
        ];

        // Experience filter
        if (!empty($args['experience'])) {
            $query_args['meta_query'][] = [
                'key'   => self::META_KEYS['experience'],
                'value' => $args['experience'],
            ];
        }

        // Remote preference filter
        if (!empty($args['remote_pref'])) {
            $query_args['meta_query'][] = [
                'key'   => self::META_KEYS['remote_pref'],
                'value' => $args['remote_pref'],
            ];
        }

        // Location filter
        if (!empty($args['location'])) {
            $query_args['meta_query'][] = [
                'key'     => self::META_KEYS['location'],
                'value'   => $args['location'],
                'compare' => 'LIKE',
            ];
        }

        // Salary filter (max budget)
        if (!empty($args['salary_max'])) {
            $query_args['meta_query'][] = [
                'key'     => self::META_KEYS['salary_min'],
                'value'   => $args['salary_max'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
        }

        $query = new WP_Query($query_args);

        $candidates = [];
        foreach ($query->posts as $post) {
            $candidates[] = self::get_availability_data($post->ID);
        }

        return [
            'candidates'   => $candidates,
            'total'        => $query->found_posts,
            'max_pages'    => $query->max_num_pages,
            'current_page' => $args['paged'],
        ];
    }

    /**
     * AJAX: Save availability
     */
    public static function ajax_save_availability() {
        check_ajax_referer('inspjob_availability', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesion']);
        }

        $user = wp_get_current_user();
        if (!in_array('job_seeker', (array) $user->roles)) {
            wp_send_json_error(['message' => 'Solo los candidatos pueden publicar disponibilidad']);
        }

        $data = [
            'title'       => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'salary_min'  => $_POST['salary_min'] ?? 0,
            'salary_max'  => $_POST['salary_max'] ?? 0,
            'start_date'  => $_POST['start_date'] ?? '',
            'job_types'   => $_POST['job_types'] ?? [],
            'categories'  => $_POST['categories'] ?? [],
            'experience'  => $_POST['experience'] ?? '',
            'remote_pref' => $_POST['remote_pref'] ?? '',
            'location'    => $_POST['location'] ?? '',
        ];

        $result = self::save_availability($user->ID, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Disponibilidad publicada correctamente',
            'post_id' => $result,
        ]);
    }

    /**
     * AJAX: Toggle availability status
     */
    public static function ajax_toggle_availability() {
        check_ajax_referer('inspjob_availability', 'nonce');

        $availability = self::get_user_availability(get_current_user_id());

        if (!$availability) {
            wp_send_json_error(['message' => 'No tienes una disponibilidad publicada']);
        }

        $new_status = $availability->post_status === 'publish' ? 'draft' : 'publish';

        wp_update_post([
            'ID'          => $availability->ID,
            'post_status' => $new_status,
        ]);

        wp_send_json_success([
            'message' => $new_status === 'publish' ? 'Disponibilidad activada' : 'Disponibilidad pausada',
            'status'  => $new_status === 'publish' ? 'active' : 'paused',
        ]);
    }

    /**
     * AJAX: Contact candidate
     */
    public static function ajax_contact_candidate() {
        check_ajax_referer('inspjob_contact_candidate', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesion']);
        }

        $availability_id = absint($_POST['availability_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $job_id = absint($_POST['job_id'] ?? 0);

        $result = self::record_contact($availability_id, get_current_user_id(), $message, $job_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Contacto enviado. El candidato recibira una notificacion.']);
    }

    /**
     * AJAX: Track view
     */
    public static function ajax_track_view() {
        $availability_id = absint($_POST['availability_id'] ?? 0);

        if ($availability_id) {
            self::track_view($availability_id);
        }

        wp_send_json_success();
    }

    /**
     * Render post availability form
     */
    public static function render_post_form($atts) {
        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes <a href="' . esc_url(home_url('/iniciar-sesion/')) . '">iniciar sesion</a> para publicar tu disponibilidad.</p>';
        }

        $user = wp_get_current_user();
        if (!in_array('job_seeker', (array) $user->roles)) {
            return '<p class="inspjob-notice">Solo los candidatos pueden publicar disponibilidad.</p>';
        }

        $existing = self::get_user_availability($user->ID);
        $data = $existing ? self::get_availability_data($existing->ID) : null;

        ob_start();
        include get_stylesheet_directory() . '/job_manager/post-availability.php';
        return ob_get_clean();
    }

    /**
     * Render available candidates list
     */
    public static function render_candidates_list($atts) {
        $atts = shortcode_atts([
            'per_page' => 12,
        ], $atts);

        $paged = get_query_var('paged') ?: 1;

        $filters = [
            'experience'  => isset($_GET['experience']) ? sanitize_text_field($_GET['experience']) : '',
            'remote_pref' => isset($_GET['remote']) ? sanitize_text_field($_GET['remote']) : '',
            'location'    => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
            'salary_max'  => isset($_GET['salary_max']) ? absint($_GET['salary_max']) : 0,
            'per_page'    => $atts['per_page'],
            'paged'       => $paged,
        ];

        $results = self::get_candidates($filters);

        ob_start();
        include get_stylesheet_directory() . '/job_manager/available-candidates.php';
        return ob_get_clean();
    }

    /**
     * Render my availability management
     */
    public static function render_my_availability($atts) {
        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes iniciar sesion para gestionar tu disponibilidad.</p>';
        }

        $availability = self::get_user_availability(get_current_user_id());
        $data = $availability ? self::get_availability_data($availability->ID) : null;

        // Get contact history
        $contacts = [];
        if ($availability) {
            global $wpdb;
            $table = $wpdb->prefix . 'inspjob_employer_contacts';
            $contacts = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as employer_name
                 FROM $table c
                 INNER JOIN {$wpdb->users} u ON c.employer_id = u.ID
                 WHERE c.availability_id = %d
                 ORDER BY c.created_at DESC
                 LIMIT 20",
                $availability->ID
            ));
        }

        ob_start();
        ?>
        <div class="inspjob-my-availability">
            <?php if (!$data): ?>
                <div class="no-availability">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <h3>No has publicado tu disponibilidad</h3>
                    <p>Publica tu disponibilidad para que los empleadores puedan contactarte directamente.</p>
                    <a href="<?php echo esc_url(home_url('/publicar-disponibilidad/')); ?>" class="inspjob-btn inspjob-btn-primary">
                        Publicar disponibilidad
                    </a>
                </div>
            <?php else: ?>
                <div class="availability-header">
                    <div class="availability-info">
                        <h3><?php echo esc_html($data['title']); ?></h3>
                        <span class="status-badge status-<?php echo $data['is_active'] ? 'active' : 'paused'; ?>">
                            <?php echo $data['is_active'] ? 'Activo' : 'Pausado'; ?>
                        </span>
                    </div>
                    <div class="availability-actions">
                        <a href="<?php echo esc_url(home_url('/publicar-disponibilidad/')); ?>" class="inspjob-btn inspjob-btn-outline">
                            Editar
                        </a>
                        <button type="button" id="toggle-availability" class="inspjob-btn inspjob-btn-outline">
                            <?php echo $data['is_active'] ? 'Pausar' : 'Activar'; ?>
                        </button>
                    </div>
                </div>

                <div class="availability-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($data['views'] ?: 0); ?></span>
                        <span class="stat-label">Vistas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($data['contacts'] ?: 0); ?></span>
                        <span class="stat-label">Contactos</span>
                    </div>
                </div>

                <?php if (!empty($contacts)): ?>
                    <div class="contacts-section">
                        <h4>Empleadores que te han contactado</h4>
                        <div class="contacts-list">
                            <?php foreach ($contacts as $contact): ?>
                                <div class="contact-item">
                                    <div class="contact-info">
                                        <span class="employer-name"><?php echo esc_html($contact->employer_name); ?></span>
                                        <span class="contact-date"><?php echo esc_html(date_i18n('d M Y', strtotime($contact->created_at))); ?></span>
                                    </div>
                                    <?php if ($contact->message): ?>
                                        <p class="contact-message"><?php echo esc_html($contact->message); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-availability');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    fetch(inspjob_ajax.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'inspjob_toggle_availability',
                            nonce: inspjob_ajax.availability_nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.data.message);
                        }
                    });
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Template include for single availability view
     */
    public static function template_include($template) {
        if (is_singular(self::POST_TYPE)) {
            $custom_template = get_stylesheet_directory() . '/job_manager/content-candidate-availability.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
}

// Initialize
InspJob_Reverse_Application::init();

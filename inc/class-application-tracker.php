<?php
/**
 * Application Tracking System
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Application_Tracker {

    /**
     * Valid application statuses
     */
    const STATUSES = [
        'pending'      => 'Pendiente',
        'viewed'       => 'Visto',
        'shortlisted'  => 'Preseleccionado',
        'interviewing' => 'En entrevista',
        'offered'      => 'Oferta recibida',
        'hired'        => 'Contratado',
        'rejected'     => 'Rechazado',
        'withdrawn'    => 'Retirado',
    ];

    /**
     * Valid status transitions
     */
    const VALID_TRANSITIONS = [
        'pending'      => ['viewed', 'shortlisted', 'rejected', 'withdrawn'],
        'viewed'       => ['shortlisted', 'interviewing', 'rejected', 'withdrawn'],
        'shortlisted'  => ['interviewing', 'rejected', 'withdrawn'],
        'interviewing' => ['offered', 'rejected', 'withdrawn'],
        'offered'      => ['hired', 'rejected', 'withdrawn'],
        'hired'        => [],
        'rejected'     => [],
        'withdrawn'    => [],
    ];

    /**
     * Initialize the tracker
     */
    public static function init() {
        // Register shortcodes
        add_shortcode('inspjob_apply_form', [__CLASS__, 'render_apply_form']);
        add_shortcode('inspjob_my_applications', [__CLASS__, 'render_my_applications']);
        add_shortcode('inspjob_job_applications', [__CLASS__, 'render_job_applications']);

        // AJAX handlers
        add_action('wp_ajax_inspjob_submit_application', [__CLASS__, 'ajax_submit_application']);
        add_action('wp_ajax_inspjob_update_application_status', [__CLASS__, 'ajax_update_status']);
        add_action('wp_ajax_inspjob_withdraw_application', [__CLASS__, 'ajax_withdraw_application']);
        add_action('wp_ajax_inspjob_get_application_details', [__CLASS__, 'ajax_get_application_details']);
    }

    /**
     * Get table name
     */
    private static function get_table($table = 'applications') {
        global $wpdb;
        return $wpdb->prefix . 'inspjob_' . $table;
    }

    /**
     * Create a new application
     */
    public static function create_application($data) {
        global $wpdb;

        $job_id = absint($data['job_id'] ?? 0);
        $applicant_id = absint($data['applicant_id'] ?? get_current_user_id());

        if (!$job_id || !$applicant_id) {
            return new WP_Error('invalid_data', 'Datos invalidos');
        }

        // Check if already applied
        if (self::has_applied($job_id, $applicant_id)) {
            return new WP_Error('already_applied', 'Ya has aplicado a este empleo');
        }

        // Get employer ID from job
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'job_listing') {
            return new WP_Error('invalid_job', 'Empleo no encontrado');
        }

        $employer_id = $job->post_author;

        // Calculate match score if available
        $match_score = 0;
        if (class_exists('InspJob_Matching_Engine')) {
            $match_score = InspJob_Matching_Engine::calculate_match_score($applicant_id, $job_id);
        }

        $now = current_time('mysql');

        $result = $wpdb->insert(
            self::get_table(),
            [
                'job_id'       => $job_id,
                'applicant_id' => $applicant_id,
                'employer_id'  => $employer_id,
                'status'       => 'pending',
                'cover_letter' => sanitize_textarea_field($data['cover_letter'] ?? ''),
                'resume_url'   => esc_url_raw($data['resume_url'] ?? ''),
                'match_score'  => $match_score,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Error al guardar la aplicacion');
        }

        $application_id = $wpdb->insert_id;

        // Record in history
        self::add_history($application_id, null, 'pending', $applicant_id, 'Aplicacion enviada');

        // Trigger actions for gamification and notifications
        do_action('inspjob_application_created', $application_id, $job_id, $applicant_id, $employer_id);

        return $application_id;
    }

    /**
     * Check if user has already applied to a job
     */
    public static function has_applied($job_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table() . " WHERE job_id = %d AND applicant_id = %d",
            $job_id,
            $user_id
        ));

        return $count > 0;
    }

    /**
     * Get an application by ID
     */
    public static function get_application($application_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . " WHERE id = %d",
            $application_id
        ));
    }

    /**
     * Get application by job and user
     */
    public static function get_application_by_job_user($job_id, $user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . " WHERE job_id = %d AND applicant_id = %d",
            $job_id,
            $user_id
        ));
    }

    /**
     * Update application status
     */
    public static function update_status($application_id, $new_status, $data = []) {
        global $wpdb;

        $application = self::get_application($application_id);
        if (!$application) {
            return new WP_Error('not_found', 'Aplicacion no encontrada');
        }

        // Validate transition
        $current_status = $application->status;
        if (!self::is_valid_transition($current_status, $new_status)) {
            return new WP_Error('invalid_transition', 'Transicion de estado no valida');
        }

        $update_data = [
            'status'     => $new_status,
            'updated_at' => current_time('mysql'),
        ];

        // Set viewed_at when first viewed
        if ($new_status === 'viewed' && empty($application->viewed_at)) {
            $update_data['viewed_at'] = current_time('mysql');
        }

        // Set responded_at for responses
        if (in_array($new_status, ['shortlisted', 'interviewing', 'offered', 'hired', 'rejected'])) {
            if (empty($application->responded_at)) {
                $update_data['responded_at'] = current_time('mysql');
            }
        }

        // Handle rejection with reason
        if ($new_status === 'rejected' && !empty($data['rejection_reason_id'])) {
            $update_data['rejection_reason_id'] = absint($data['rejection_reason_id']);
        }

        $result = $wpdb->update(
            self::get_table(),
            $update_data,
            ['id' => $application_id],
            null,
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Error al actualizar el estado');
        }

        // Record in history
        $changed_by = get_current_user_id();
        $note = $data['note'] ?? '';
        self::add_history($application_id, $current_status, $new_status, $changed_by, $note);

        // Trigger action for notifications
        do_action('inspjob_application_status_changed', $application_id, $current_status, $new_status, $application);

        return true;
    }

    /**
     * Validate status transition
     */
    public static function is_valid_transition($from_status, $to_status) {
        if (!isset(self::VALID_TRANSITIONS[$from_status])) {
            return false;
        }

        return in_array($to_status, self::VALID_TRANSITIONS[$from_status]);
    }

    /**
     * Add entry to application history
     */
    private static function add_history($application_id, $from_status, $to_status, $changed_by, $note = '') {
        global $wpdb;

        $wpdb->insert(
            self::get_table('application_history'),
            [
                'application_id' => $application_id,
                'from_status'    => $from_status,
                'to_status'      => $to_status,
                'changed_by'     => $changed_by,
                'note'           => $note,
                'created_at'     => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Get application history
     */
    public static function get_history($application_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::get_table('application_history') . " WHERE application_id = %d ORDER BY created_at ASC",
            $application_id
        ));
    }

    /**
     * Get user applications (for job seeker)
     */
    public static function get_user_applications($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'status'   => '',
            'limit'    => 20,
            'offset'   => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM " . self::get_table() . " WHERE applicant_id = %d";
        $params = [$user_id];

        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }

        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Get job applications (for employer)
     */
    public static function get_job_applications($job_id, $args = []) {
        global $wpdb;

        $defaults = [
            'status'   => '',
            'limit'    => 50,
            'offset'   => 0,
            'orderby'  => 'match_score',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM " . self::get_table() . " WHERE job_id = %d";
        $params = [$job_id];

        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }

        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Get employer applications (all jobs)
     */
    public static function get_employer_applications($employer_id, $args = []) {
        global $wpdb;

        $defaults = [
            'status'   => '',
            'job_id'   => 0,
            'limit'    => 50,
            'offset'   => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM " . self::get_table() . " WHERE employer_id = %d";
        $params = [$employer_id];

        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }

        if (!empty($args['job_id'])) {
            $sql .= " AND job_id = %d";
            $params[] = $args['job_id'];
        }

        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Get user application stats
     */
    public static function get_user_stats($user_id) {
        global $wpdb;

        $table = self::get_table();

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE applicant_id = %d",
            $user_id
        ));

        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $table WHERE applicant_id = %d GROUP BY status",
            $user_id
        ), OBJECT_K);

        $stats = ['total' => (int) $total];
        foreach (array_keys(self::STATUSES) as $status) {
            $stats[$status] = isset($by_status[$status]) ? (int) $by_status[$status]->count : 0;
        }

        return $stats;
    }

    /**
     * Get employer stats
     */
    public static function get_employer_stats($employer_id) {
        global $wpdb;

        $table = self::get_table();

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_applications,
                SUM(CASE WHEN viewed_at IS NOT NULL THEN 1 ELSE 0 END) as viewed_count,
                SUM(CASE WHEN responded_at IS NOT NULL THEN 1 ELSE 0 END) as responded_count,
                SUM(CASE WHEN status = 'rejected' AND rejection_reason_id IS NOT NULL THEN 1 ELSE 0 END) as feedback_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(responded_at, NOW()))) as avg_response_hours
            FROM $table WHERE employer_id = %d",
            $employer_id
        ));

        return $stats;
    }

    /**
     * Get pending applications count for employer
     */
    public static function get_pending_count($employer_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table() . " WHERE employer_id = %d AND status IN ('pending', 'viewed')",
            $employer_id
        ));
    }

    /**
     * Get status label
     */
    public static function get_status_label($status) {
        return self::STATUSES[$status] ?? $status;
    }

    /**
     * Get rejection reasons
     */
    public static function get_rejection_reasons() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM " . self::get_table('rejection_reasons') . " ORDER BY display_order ASC"
        );
    }

    /**
     * Get rejection reason by ID
     */
    public static function get_rejection_reason($reason_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table('rejection_reasons') . " WHERE id = %d",
            $reason_id
        ));
    }

    /**
     * AJAX: Submit application
     */
    public static function ajax_submit_application() {
        check_ajax_referer('inspjob_apply', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesion para aplicar']);
        }

        $job_id = absint($_POST['job_id'] ?? 0);
        $cover_letter = sanitize_textarea_field($_POST['cover_letter'] ?? '');

        // Get resume URL from user meta if available
        $user_id = get_current_user_id();
        $resume_url = get_user_meta($user_id, '_job_seeker_resume_url', true);

        // Handle file upload if provided
        if (!empty($_FILES['resume'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES['resume'], ['test_form' => false]);

            if (!isset($upload['error'])) {
                $resume_url = $upload['url'];
            }
        }

        $result = self::create_application([
            'job_id'       => $job_id,
            'applicant_id' => $user_id,
            'cover_letter' => $cover_letter,
            'resume_url'   => $resume_url,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message'        => 'Aplicacion enviada exitosamente',
            'application_id' => $result,
        ]);
    }

    /**
     * AJAX: Update application status
     */
    public static function ajax_update_status() {
        check_ajax_referer('inspjob_manage_applications', 'nonce');

        $application_id = absint($_POST['application_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        $rejection_reason_id = absint($_POST['rejection_reason_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');

        $application = self::get_application($application_id);

        if (!$application) {
            wp_send_json_error(['message' => 'Aplicacion no encontrada']);
        }

        // Verify the current user owns this job
        $job = get_post($application->job_id);
        if (!$job || $job->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'No tienes permiso para gestionar esta aplicacion']);
        }

        // Validate status
        if (!isset(self::STATUSES[$new_status])) {
            wp_send_json_error(['message' => 'Estado no valido']);
        }

        $data = ['note' => $note];
        if ($new_status === 'rejected' && $rejection_reason_id) {
            $data['rejection_reason_id'] = $rejection_reason_id;
        }

        $result = self::update_status($application_id, $new_status, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message'    => 'Estado actualizado correctamente',
            'new_status' => $new_status,
            'label'      => self::get_status_label($new_status),
        ]);
    }

    /**
     * AJAX: Withdraw application
     */
    public static function ajax_withdraw_application() {
        check_ajax_referer('inspjob_my_applications', 'nonce');

        $application_id = absint($_POST['application_id'] ?? 0);
        $application = self::get_application($application_id);

        if (!$application) {
            wp_send_json_error(['message' => 'Aplicacion no encontrada']);
        }

        // Verify the current user owns this application
        if ($application->applicant_id != get_current_user_id()) {
            wp_send_json_error(['message' => 'No tienes permiso para retirar esta aplicacion']);
        }

        $result = self::update_status($application_id, 'withdrawn', ['note' => 'Retirada por el candidato']);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Aplicacion retirada correctamente']);
    }

    /**
     * AJAX: Get application details
     */
    public static function ajax_get_application_details() {
        check_ajax_referer('inspjob_manage_applications', 'nonce');

        $application_id = absint($_POST['application_id'] ?? 0);
        $application = self::get_application($application_id);

        if (!$application) {
            wp_send_json_error(['message' => 'Aplicacion no encontrada']);
        }

        // Get applicant info
        $applicant = get_userdata($application->applicant_id);
        $profile = [];

        if (class_exists('InspJob_Job_Seeker')) {
            $profile = InspJob_Job_Seeker::get_profile($application->applicant_id);
        }

        // Get history
        $history = self::get_history($application_id);

        wp_send_json_success([
            'application' => $application,
            'applicant'   => [
                'id'           => $applicant->ID,
                'name'         => $applicant->display_name,
                'email'        => $applicant->user_email,
                'avatar'       => get_avatar_url($applicant->ID, ['size' => 100]),
            ],
            'profile'     => $profile,
            'history'     => $history,
        ]);
    }

    /**
     * Render apply form shortcode
     */
    public static function render_apply_form($atts) {
        $atts = shortcode_atts(['job_id' => 0], $atts);

        $job_id = $atts['job_id'] ?: get_the_ID();

        if (!$job_id) {
            return '<p class="inspjob-notice">No se encontro el empleo.</p>';
        }

        if (!is_user_logged_in()) {
            return '<div class="inspjob-apply-login">
                <p>Para aplicar a este empleo, debes tener una cuenta.</p>
                <a href="' . esc_url(home_url('/iniciar-sesion/')) . '" class="inspjob-btn inspjob-btn-primary">Iniciar sesion</a>
                <a href="' . esc_url(home_url('/registro-candidato/')) . '" class="inspjob-btn inspjob-btn-outline">Crear cuenta</a>
            </div>';
        }

        if (self::has_applied($job_id)) {
            $application = self::get_application_by_job_user($job_id, get_current_user_id());
            return '<div class="inspjob-already-applied">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <p>Ya has aplicado a este empleo</p>
                <span class="application-status status-' . esc_attr($application->status) . '">Estado: ' . esc_html(self::get_status_label($application->status)) . '</span>
            </div>';
        }

        ob_start();
        include get_stylesheet_directory() . '/job_manager/application-form.php';
        return ob_get_clean();
    }

    /**
     * Render my applications shortcode
     */
    public static function render_my_applications($atts) {
        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes <a href="' . esc_url(home_url('/iniciar-sesion/')) . '">iniciar sesion</a> para ver tus aplicaciones.</p>';
        }

        $applications = self::get_user_applications(get_current_user_id());

        ob_start();
        include get_stylesheet_directory() . '/job_manager/my-applications.php';
        return ob_get_clean();
    }

    /**
     * Render job applications shortcode (for employers)
     */
    public static function render_job_applications($atts) {
        $atts = shortcode_atts(['job_id' => 0], $atts);

        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Debes iniciar sesion para ver las aplicaciones.</p>';
        }

        $job_id = $atts['job_id'] ?: (isset($_GET['job_id']) ? absint($_GET['job_id']) : 0);

        if ($job_id) {
            $job = get_post($job_id);
            if (!$job || $job->post_author != get_current_user_id()) {
                return '<p class="inspjob-notice">No tienes permiso para ver las aplicaciones de este empleo.</p>';
            }
        }

        $applications = $job_id
            ? self::get_job_applications($job_id)
            : self::get_employer_applications(get_current_user_id());

        ob_start();
        include get_stylesheet_directory() . '/job_manager/employer-applications.php';
        return ob_get_clean();
    }
}

// Initialize
InspJob_Application_Tracker::init();

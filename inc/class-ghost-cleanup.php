<?php
/**
 * Ghost Position Cleanup System
 * Automatically handles abandoned job listings and unresponsive employers
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Ghost_Cleanup {

    /**
     * Days after SLA before warning
     */
    const DAYS_TO_WARNING = 7;

    /**
     * Days after SLA before auto-close
     */
    const DAYS_TO_CLOSE = 14;

    /**
     * Meta keys
     */
    const META_KEYS = [
        'ghost_warning_sent' => '_ghost_warning_sent',
        'auto_closed_reason' => '_auto_closed_reason',
        'auto_closed_date'   => '_auto_closed_date',
    ];

    /**
     * Initialize the cleanup system
     */
    public static function init() {
        // Register cron jobs
        add_action('inspjob_ghost_cleanup', [__CLASS__, 'run_cleanup']);
        add_action('inspjob_check_sla_violations', [__CLASS__, 'check_sla_violations']);

        if (!wp_next_scheduled('inspjob_ghost_cleanup')) {
            wp_schedule_event(time(), 'daily', 'inspjob_ghost_cleanup');
        }

        if (!wp_next_scheduled('inspjob_check_sla_violations')) {
            wp_schedule_event(time(), 'hourly', 'inspjob_check_sla_violations');
        }

        // Admin notice for employers
        add_action('admin_notices', [__CLASS__, 'show_employer_warnings']);
    }

    /**
     * Run the full cleanup process
     */
    public static function run_cleanup() {
        self::send_ghost_warnings();
        self::close_ghost_positions();
    }

    /**
     * Check for SLA violations (hourly)
     */
    public static function check_sla_violations() {
        if (!class_exists('InspJob_SLA_Commitment')) {
            return;
        }

        // Get applications nearing deadline
        $nearing_deadline = InspJob_SLA_Commitment::get_applications_nearing_deadline(0, 24);

        foreach ($nearing_deadline as $app) {
            // Send reminder to employer
            do_action('inspjob_sla_reminder', $app);
        }

        // Get overdue applications
        $overdue = InspJob_SLA_Commitment::get_overdue_applications();

        foreach ($overdue as $app) {
            // Trigger SLA violation action
            do_action('inspjob_sla_violation', $app);
        }
    }

    /**
     * Send warnings to employers with ghost positions
     */
    public static function send_ghost_warnings() {
        $ghost_jobs = self::get_ghost_jobs(self::DAYS_TO_WARNING);

        foreach ($ghost_jobs as $job_id) {
            // Check if warning already sent
            $warning_sent = get_post_meta($job_id, self::META_KEYS['ghost_warning_sent'], true);
            if ($warning_sent) {
                continue;
            }

            // Get job data
            $job = get_post($job_id);
            if (!$job) {
                continue;
            }

            // Mark warning as sent
            update_post_meta($job_id, self::META_KEYS['ghost_warning_sent'], current_time('mysql'));

            // Trigger warning action (for email notification)
            do_action('inspjob_ghost_warning_sent', $job_id, $job->post_author);
        }
    }

    /**
     * Close ghost positions automatically
     */
    public static function close_ghost_positions() {
        $ghost_jobs = self::get_ghost_jobs(self::DAYS_TO_CLOSE);

        foreach ($ghost_jobs as $job_id) {
            self::close_job($job_id, 'ghost_position');
        }
    }

    /**
     * Get jobs that qualify as ghost positions
     *
     * @param int $days_overdue Days past SLA deadline
     * @return array Job IDs
     */
    public static function get_ghost_jobs($days_overdue = 14) {
        global $wpdb;

        if (!class_exists('InspJob_Application_Tracker') || !class_exists('InspJob_SLA_Commitment')) {
            return [];
        }

        $applications_table = $wpdb->prefix . 'inspjob_applications';
        $sla_meta_key = InspJob_SLA_Commitment::META_KEYS['sla_days'];

        // Find jobs with pending applications past SLA + days_overdue
        $sql = "SELECT DISTINCT a.job_id
                FROM $applications_table a
                INNER JOIN {$wpdb->postmeta} pm ON a.job_id = pm.post_id AND pm.meta_key = %s
                INNER JOIN {$wpdb->posts} p ON a.job_id = p.ID
                WHERE a.status IN ('pending', 'viewed')
                AND a.responded_at IS NULL
                AND p.post_status = 'publish'
                AND DATE_ADD(a.created_at, INTERVAL (CAST(pm.meta_value AS UNSIGNED) + %d) DAY) < NOW()";

        $results = $wpdb->get_col($wpdb->prepare($sql, $sla_meta_key, $days_overdue));

        return $results ?: [];
    }

    /**
     * Close a job and notify applicants
     */
    public static function close_job($job_id, $reason = 'ghost_position') {
        $job = get_post($job_id);
        if (!$job) {
            return false;
        }

        // Update job status to expired
        wp_update_post([
            'ID'          => $job_id,
            'post_status' => 'expired',
        ]);

        // Record closure reason
        update_post_meta($job_id, self::META_KEYS['auto_closed_reason'], $reason);
        update_post_meta($job_id, self::META_KEYS['auto_closed_date'], current_time('mysql'));

        // Get pending applications and mark them as rejected
        if (class_exists('InspJob_Application_Tracker')) {
            $applications = InspJob_Application_Tracker::get_job_applications($job_id, ['status' => 'pending']);

            foreach ($applications as $app) {
                InspJob_Application_Tracker::update_status($app->id, 'rejected', [
                    'rejection_reason_id' => self::get_ghost_rejection_reason_id(),
                    'note' => 'Posicion cerrada automaticamente por falta de respuesta del empleador',
                ]);

                // Notify candidate
                do_action('inspjob_ghost_position_closed', $app->id, $job_id);
            }

            // Also handle viewed applications
            $viewed_applications = InspJob_Application_Tracker::get_job_applications($job_id, ['status' => 'viewed']);
            foreach ($viewed_applications as $app) {
                InspJob_Application_Tracker::update_status($app->id, 'rejected', [
                    'rejection_reason_id' => self::get_ghost_rejection_reason_id(),
                    'note' => 'Posicion cerrada automaticamente por falta de respuesta del empleador',
                ]);

                do_action('inspjob_ghost_position_closed', $app->id, $job_id);
            }
        }

        // Notify employer
        do_action('inspjob_job_auto_closed', $job_id, $job->post_author, $reason);

        // Penalize employer score
        if (class_exists('InspJob_Employer_Score')) {
            InspJob_Employer_Score::calculate_score($job->post_author);
        }

        return true;
    }

    /**
     * Get the rejection reason ID for ghost position closures
     */
    private static function get_ghost_rejection_reason_id() {
        global $wpdb;

        $table = $wpdb->prefix . 'inspjob_rejection_reasons';

        // Try to find existing reason
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE reason_key = %s",
            'process_cancelled'
        ));

        return $id ?: 7; // Default to 'process_cancelled' ID
    }

    /**
     * Show warnings in admin for employers
     */
    public static function show_employer_warnings() {
        if (!is_admin()) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-job_listing') {
            return;
        }

        $user_id = get_current_user_id();

        // Check for ghost positions
        $ghost_jobs = self::get_employer_ghost_jobs($user_id);

        if (!empty($ghost_jobs)) {
            $count = count($ghost_jobs);
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>Atencion:</strong> Tienes <?php echo esc_html($count); ?>
                    empleo<?php echo $count > 1 ? 's' : ''; ?> con aplicaciones sin responder que superan el plazo comprometido.
                    Por favor, revisa y responde a los candidatos para evitar el cierre automatico.
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=job_listing&ghost_warning=1')); ?>"
                       class="button">Ver empleos afectados</a>
                </p>
            </div>
            <?php
        }

        // Check for pending count
        if (class_exists('InspJob_Application_Tracker')) {
            $pending_count = InspJob_Application_Tracker::get_pending_count($user_id);

            if ($pending_count >= 5) {
                ?>
                <div class="notice notice-info">
                    <p>
                        Tienes <?php echo esc_html($pending_count); ?> aplicaciones pendientes de revision.
                        <a href="<?php echo esc_url(home_url('/gestionar-aplicaciones/')); ?>">Revisar aplicaciones</a>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Get ghost jobs for a specific employer
     */
    public static function get_employer_ghost_jobs($employer_id) {
        global $wpdb;

        if (!class_exists('InspJob_Application_Tracker') || !class_exists('InspJob_SLA_Commitment')) {
            return [];
        }

        $applications_table = $wpdb->prefix . 'inspjob_applications';
        $sla_meta_key = InspJob_SLA_Commitment::META_KEYS['sla_days'];

        $sql = "SELECT DISTINCT a.job_id
                FROM $applications_table a
                INNER JOIN {$wpdb->postmeta} pm ON a.job_id = pm.post_id AND pm.meta_key = %s
                INNER JOIN {$wpdb->posts} p ON a.job_id = p.ID
                WHERE a.employer_id = %d
                AND a.status IN ('pending', 'viewed')
                AND a.responded_at IS NULL
                AND p.post_status = 'publish'
                AND DATE_ADD(a.created_at, INTERVAL CAST(pm.meta_value AS UNSIGNED) DAY) < NOW()";

        $results = $wpdb->get_col($wpdb->prepare($sql, $sla_meta_key, $employer_id));

        return $results ?: [];
    }

    /**
     * Get statistics for ghost positions
     */
    public static function get_ghost_stats() {
        global $wpdb;

        $applications_table = $wpdb->prefix . 'inspjob_applications';

        $stats = [
            'total_ghost_jobs'     => count(self::get_ghost_jobs(0)),
            'warning_sent'         => 0,
            'closed_this_month'    => 0,
            'pending_applications' => 0,
        ];

        // Jobs with warning sent
        $stats['warning_sent'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
            self::META_KEYS['ghost_warning_sent']
        ));

        // Jobs closed this month
        $stats['closed_this_month'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = %s
             AND meta_value = 'ghost_position'
             AND post_id IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND meta_value >= %s
             )",
            self::META_KEYS['auto_closed_reason'],
            self::META_KEYS['auto_closed_date'],
            date('Y-m-01 00:00:00')
        ));

        // Total pending applications on ghost jobs
        $ghost_jobs = self::get_ghost_jobs(0);
        if (!empty($ghost_jobs)) {
            $placeholders = implode(',', array_fill(0, count($ghost_jobs), '%d'));
            $stats['pending_applications'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $applications_table WHERE job_id IN ($placeholders) AND status IN ('pending', 'viewed')",
                ...$ghost_jobs
            ));
        }

        return $stats;
    }
}

// Initialize
InspJob_Ghost_Cleanup::init();

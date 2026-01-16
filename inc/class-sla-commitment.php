<?php
/**
 * SLA Commitment System
 * Manages response time commitments for job listings
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_SLA_Commitment {

    /**
     * SLA options in days
     */
    const SLA_OPTIONS = [
        3  => '3 dias',
        5  => '5 dias',
        7  => '7 dias',
        14 => '14 dias',
    ];

    /**
     * Meta keys
     */
    const META_KEYS = [
        'sla_days'     => '_job_sla_response_days',
        'sla_committed' => '_job_sla_committed',
    ];

    /**
     * Initialize the system
     */
    public static function init() {
        // Add SLA field to job submission form
        add_filter('submit_job_form_fields', [__CLASS__, 'add_sla_field']);
        add_filter('job_manager_job_listing_data_fields', [__CLASS__, 'add_admin_sla_field']);

        // Save SLA field
        add_action('job_manager_update_job_data', [__CLASS__, 'save_sla_field'], 10, 2);

        // Validate SLA is set
        add_filter('submit_job_form_validate_fields', [__CLASS__, 'validate_sla'], 10, 3);

        // Display SLA on job listing
        add_action('single_job_listing_meta_end', [__CLASS__, 'display_sla_badge']);
    }

    /**
     * Add SLA field to frontend submission form
     */
    public static function add_sla_field($fields) {
        $fields['job']['job_sla_response_days'] = [
            'label'       => 'Compromiso de respuesta',
            'type'        => 'select',
            'required'    => true,
            'priority'    => 8,
            'options'     => array_merge(
                ['' => 'Selecciona tu compromiso de respuesta'],
                self::SLA_OPTIONS
            ),
            'description' => 'Te comprometes a responder a los candidatos en este plazo. Este dato sera visible para los candidatos.',
        ];

        return $fields;
    }

    /**
     * Add SLA field to admin
     */
    public static function add_admin_sla_field($fields) {
        $fields[self::META_KEYS['sla_days']] = [
            'label'       => 'Compromiso de respuesta (dias)',
            'type'        => 'select',
            'options'     => self::SLA_OPTIONS,
            'placeholder' => 'Seleccionar',
            'description' => 'Dias para responder a los candidatos',
        ];

        return $fields;
    }

    /**
     * Save SLA field
     */
    public static function save_sla_field($job_id, $values) {
        if (isset($_POST['job_sla_response_days'])) {
            $sla_days = absint($_POST['job_sla_response_days']);
            if (array_key_exists($sla_days, self::SLA_OPTIONS)) {
                update_post_meta($job_id, self::META_KEYS['sla_days'], $sla_days);
                update_post_meta($job_id, self::META_KEYS['sla_committed'], 1);
            }
        }
    }

    /**
     * Validate SLA is selected
     */
    public static function validate_sla($valid, $fields, $values) {
        if (is_wp_error($valid)) {
            return $valid;
        }

        $sla = isset($_POST['job_sla_response_days']) ? absint($_POST['job_sla_response_days']) : 0;

        if (!array_key_exists($sla, self::SLA_OPTIONS)) {
            return new WP_Error('validation-error', 'Por favor, selecciona un compromiso de respuesta.');
        }

        return $valid;
    }

    /**
     * Get SLA for a job
     */
    public static function get_job_sla($job_id) {
        $sla_days = get_post_meta($job_id, self::META_KEYS['sla_days'], true);
        $sla_committed = get_post_meta($job_id, self::META_KEYS['sla_committed'], true);

        if (!$sla_days || !$sla_committed) {
            return null;
        }

        return [
            'days'  => (int) $sla_days,
            'label' => self::SLA_OPTIONS[$sla_days] ?? $sla_days . ' dias',
        ];
    }

    /**
     * Check if application is within SLA
     */
    public static function is_within_sla($application) {
        $job_sla = self::get_job_sla($application->job_id);

        if (!$job_sla) {
            return true; // No SLA set
        }

        $created = strtotime($application->created_at);
        $deadline = $created + ($job_sla['days'] * DAY_IN_SECONDS);

        return current_time('timestamp') <= $deadline;
    }

    /**
     * Get SLA deadline for an application
     */
    public static function get_sla_deadline($application) {
        $job_sla = self::get_job_sla($application->job_id);

        if (!$job_sla) {
            return null;
        }

        $created = strtotime($application->created_at);
        $deadline = $created + ($job_sla['days'] * DAY_IN_SECONDS);

        return $deadline;
    }

    /**
     * Get remaining days until SLA deadline
     */
    public static function get_remaining_days($application) {
        $deadline = self::get_sla_deadline($application);

        if (!$deadline) {
            return null;
        }

        $now = current_time('timestamp');
        $remaining = $deadline - $now;

        if ($remaining <= 0) {
            return 0;
        }

        return ceil($remaining / DAY_IN_SECONDS);
    }

    /**
     * Display SLA badge on job listing
     */
    public static function display_sla_badge() {
        $job_id = get_the_ID();
        $sla = self::get_job_sla($job_id);

        if (!$sla) {
            return;
        }

        ?>
        <li class="sla-commitment">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>Respuesta en <?php echo esc_html($sla['label']); ?></span>
        </li>
        <?php
    }

    /**
     * Render SLA badge for job card
     */
    public static function render_sla_badge($job_id) {
        $sla = self::get_job_sla($job_id);

        if (!$sla) {
            return '';
        }

        ob_start();
        ?>
        <div class="inspjob-sla-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>Respuesta: <?php echo esc_html($sla['label']); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get applications nearing SLA deadline
     */
    public static function get_applications_nearing_deadline($employer_id = 0, $hours_before = 24) {
        global $wpdb;

        if (!class_exists('InspJob_Application_Tracker')) {
            return [];
        }

        $applications_table = $wpdb->prefix . 'inspjob_applications';

        // Get pending/viewed applications
        $sql = "SELECT a.*, pm.meta_value as sla_days
                FROM $applications_table a
                INNER JOIN {$wpdb->postmeta} pm ON a.job_id = pm.post_id AND pm.meta_key = %s
                WHERE a.status IN ('pending', 'viewed')
                AND a.responded_at IS NULL";

        $params = [self::META_KEYS['sla_days']];

        if ($employer_id) {
            $sql .= " AND a.employer_id = %d";
            $params[] = $employer_id;
        }

        $applications = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $nearing_deadline = [];

        foreach ($applications as $app) {
            $created = strtotime($app->created_at);
            $deadline = $created + ($app->sla_days * DAY_IN_SECONDS);
            $warning_time = $deadline - ($hours_before * HOUR_IN_SECONDS);

            if (current_time('timestamp') >= $warning_time && current_time('timestamp') < $deadline) {
                $app->deadline = $deadline;
                $app->hours_remaining = ceil(($deadline - current_time('timestamp')) / HOUR_IN_SECONDS);
                $nearing_deadline[] = $app;
            }
        }

        return $nearing_deadline;
    }

    /**
     * Get applications past SLA deadline
     */
    public static function get_overdue_applications($employer_id = 0) {
        global $wpdb;

        if (!class_exists('InspJob_Application_Tracker')) {
            return [];
        }

        $applications_table = $wpdb->prefix . 'inspjob_applications';

        $sql = "SELECT a.*, pm.meta_value as sla_days
                FROM $applications_table a
                INNER JOIN {$wpdb->postmeta} pm ON a.job_id = pm.post_id AND pm.meta_key = %s
                WHERE a.status IN ('pending', 'viewed')
                AND a.responded_at IS NULL";

        $params = [self::META_KEYS['sla_days']];

        if ($employer_id) {
            $sql .= " AND a.employer_id = %d";
            $params[] = $employer_id;
        }

        $applications = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $overdue = [];

        foreach ($applications as $app) {
            $created = strtotime($app->created_at);
            $deadline = $created + ($app->sla_days * DAY_IN_SECONDS);

            if (current_time('timestamp') > $deadline) {
                $app->deadline = $deadline;
                $app->days_overdue = ceil((current_time('timestamp') - $deadline) / DAY_IN_SECONDS);
                $overdue[] = $app;
            }
        }

        return $overdue;
    }
}

// Initialize
InspJob_SLA_Commitment::init();

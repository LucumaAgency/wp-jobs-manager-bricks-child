<?php
/**
 * Employer Score System
 * Tracks and displays employer responsiveness metrics
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Employer_Score {

    /**
     * User meta keys
     */
    const META_KEYS = [
        'response_rate'      => '_employer_response_rate',
        'feedback_rate'      => '_employer_feedback_rate',
        'avg_response_hours' => '_employer_avg_response_hours',
        'score'              => '_employer_score',
        'badge'              => '_employer_badge',
        'blocked_until'      => '_employer_blocked_until',
        'total_applications' => '_employer_total_applications',
        'total_responded'    => '_employer_total_responded',
    ];

    /**
     * Badge thresholds
     */
    const BADGES = [
        'top_employer'       => ['score' => 90, 'response_rate' => 95],
        'highly_responsive'  => ['score' => 75, 'response_rate' => 80],
        'responsive'         => ['score' => 0, 'response_rate' => 60],
    ];

    /**
     * Badge labels
     */
    const BADGE_LABELS = [
        'top_employer'      => 'Top Empleador',
        'highly_responsive' => 'Muy Responsivo',
        'responsive'        => 'Responsivo',
    ];

    /**
     * Initialize the system
     */
    public static function init() {
        // Register cron for daily score calculation
        add_action('inspjob_calculate_employer_scores', [__CLASS__, 'calculate_all_scores']);

        if (!wp_next_scheduled('inspjob_calculate_employer_scores')) {
            wp_schedule_event(time(), 'daily', 'inspjob_calculate_employer_scores');
        }

        // Update scores when application status changes
        add_action('inspjob_application_status_changed', [__CLASS__, 'on_application_status_changed'], 10, 4);

        // Check employer can post
        add_filter('submit_job_form_validate_fields', [__CLASS__, 'validate_can_post'], 10, 3);

        // Shortcodes
        add_shortcode('inspjob_employer_score', [__CLASS__, 'render_employer_score']);
        add_shortcode('inspjob_employer_metrics', [__CLASS__, 'render_employer_metrics']);
    }

    /**
     * Get employer metrics
     */
    public static function get_metrics($employer_id) {
        return [
            'response_rate'      => (float) get_user_meta($employer_id, self::META_KEYS['response_rate'], true) ?: 0,
            'feedback_rate'      => (float) get_user_meta($employer_id, self::META_KEYS['feedback_rate'], true) ?: 0,
            'avg_response_hours' => (float) get_user_meta($employer_id, self::META_KEYS['avg_response_hours'], true) ?: 0,
            'score'              => (int) get_user_meta($employer_id, self::META_KEYS['score'], true) ?: 0,
            'badge'              => get_user_meta($employer_id, self::META_KEYS['badge'], true) ?: '',
            'blocked_until'      => get_user_meta($employer_id, self::META_KEYS['blocked_until'], true) ?: '',
            'total_applications' => (int) get_user_meta($employer_id, self::META_KEYS['total_applications'], true) ?: 0,
            'total_responded'    => (int) get_user_meta($employer_id, self::META_KEYS['total_responded'], true) ?: 0,
        ];
    }

    /**
     * Calculate score for a single employer
     */
    public static function calculate_score($employer_id) {
        if (!class_exists('InspJob_Application_Tracker')) {
            return 0;
        }

        $stats = InspJob_Application_Tracker::get_employer_stats($employer_id);

        if (!$stats || $stats->total_applications == 0) {
            return 0;
        }

        // Calculate rates
        $response_rate = ($stats->responded_count / $stats->total_applications) * 100;
        $feedback_rate = $stats->rejected_count > 0
            ? ($stats->feedback_count / $stats->rejected_count) * 100
            : 100;
        $avg_response_hours = $stats->avg_response_hours ?: 0;

        // Calculate composite score
        // response_rate: 50%, feedback_rate: 30%, response_time: 20%
        $time_score = max(0, 100 - ($avg_response_hours / 2)); // 48 hours = 76 score, 168 hours = 16 score
        $time_score = min(100, $time_score);

        $score = ($response_rate * 0.5) + ($feedback_rate * 0.3) + ($time_score * 0.2);
        $score = round(min(100, max(0, $score)));

        // Determine badge
        $badge = '';
        foreach (self::BADGES as $badge_key => $thresholds) {
            if ($score >= $thresholds['score'] && $response_rate >= $thresholds['response_rate']) {
                $badge = $badge_key;
                break;
            }
        }

        // Save metrics
        update_user_meta($employer_id, self::META_KEYS['response_rate'], round($response_rate, 1));
        update_user_meta($employer_id, self::META_KEYS['feedback_rate'], round($feedback_rate, 1));
        update_user_meta($employer_id, self::META_KEYS['avg_response_hours'], round($avg_response_hours, 1));
        update_user_meta($employer_id, self::META_KEYS['score'], $score);
        update_user_meta($employer_id, self::META_KEYS['badge'], $badge);
        update_user_meta($employer_id, self::META_KEYS['total_applications'], $stats->total_applications);
        update_user_meta($employer_id, self::META_KEYS['total_responded'], $stats->responded_count);

        // Trigger badge earned action for gamification
        if ($badge) {
            do_action('inspjob_employer_badge_earned', $employer_id, $badge);
        }

        return $score;
    }

    /**
     * Calculate scores for all employers (cron job)
     */
    public static function calculate_all_scores() {
        $employers = get_users([
            'role__in' => ['employer', 'administrator'],
            'fields'   => 'ID',
        ]);

        foreach ($employers as $employer_id) {
            self::calculate_score($employer_id);
        }
    }

    /**
     * Handle application status change
     */
    public static function on_application_status_changed($application_id, $from_status, $to_status, $application) {
        // Recalculate employer score when there's a response
        $responded_statuses = ['shortlisted', 'interviewing', 'offered', 'hired', 'rejected'];

        if (in_array($to_status, $responded_statuses)) {
            self::calculate_score($application->employer_id);
        }
    }

    /**
     * Check if employer can post new jobs
     */
    public static function can_post_job($employer_id) {
        // Check if blocked
        $blocked_until = get_user_meta($employer_id, self::META_KEYS['blocked_until'], true);
        if ($blocked_until && strtotime($blocked_until) > current_time('timestamp')) {
            return new WP_Error(
                'blocked',
                sprintf(
                    'Tu cuenta esta temporalmente bloqueada para publicar empleos hasta el %s. Por favor, responde a las aplicaciones pendientes.',
                    date_i18n('d/m/Y', strtotime($blocked_until))
                )
            );
        }

        // Check pending applications count
        if (!class_exists('InspJob_Application_Tracker')) {
            return true;
        }

        $pending_count = InspJob_Application_Tracker::get_pending_count($employer_id);
        $response_rate = (float) get_user_meta($employer_id, self::META_KEYS['response_rate'], true);

        // Block if too many pending and low response rate
        if ($pending_count >= 10 && $response_rate < 50) {
            self::block_employer($employer_id, '+7 days');

            return new WP_Error(
                'low_response',
                'Tienes demasiadas aplicaciones sin responder. Por favor, revisa y responde a los candidatos antes de publicar nuevos empleos.'
            );
        }

        return true;
    }

    /**
     * Validate employer can post (hook)
     */
    public static function validate_can_post($is_valid, $fields, $values) {
        if (!is_user_logged_in()) {
            return $is_valid;
        }

        $result = self::can_post_job(get_current_user_id());

        if (is_wp_error($result)) {
            return new WP_Error('validation-error', $result->get_error_message());
        }

        return $is_valid;
    }

    /**
     * Block employer from posting
     */
    public static function block_employer($employer_id, $duration = '+7 days') {
        $blocked_until = date('Y-m-d H:i:s', strtotime($duration));
        update_user_meta($employer_id, self::META_KEYS['blocked_until'], $blocked_until);

        // Notify employer
        do_action('inspjob_employer_blocked', $employer_id, $blocked_until);
    }

    /**
     * Unblock employer
     */
    public static function unblock_employer($employer_id) {
        delete_user_meta($employer_id, self::META_KEYS['blocked_until']);
    }

    /**
     * Get badge label
     */
    public static function get_badge_label($badge) {
        return self::BADGE_LABELS[$badge] ?? '';
    }

    /**
     * Render employer score shortcode
     */
    public static function render_employer_score($atts) {
        $atts = shortcode_atts(['employer_id' => 0], $atts);

        $employer_id = $atts['employer_id'];

        if (!$employer_id) {
            // Try to get from job context
            $job_id = get_the_ID();
            if ($job_id && get_post_type($job_id) === 'job_listing') {
                $employer_id = get_post_field('post_author', $job_id);
            }
        }

        if (!$employer_id) {
            return '';
        }

        $metrics = self::get_metrics($employer_id);

        if ($metrics['total_applications'] < 3) {
            return ''; // Not enough data
        }

        $badge = $metrics['badge'];

        if (!$badge) {
            return '';
        }

        ob_start();
        ?>
        <div class="inspjob-employer-badge badge-<?php echo esc_attr($badge); ?>">
            <span class="badge-icon">
                <?php if ($badge === 'top_employer'): ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                <?php elseif ($badge === 'highly_responsive'): ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                <?php endif; ?>
            </span>
            <span class="badge-label"><?php echo esc_html(self::get_badge_label($badge)); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render detailed employer metrics
     */
    public static function render_employer_metrics($atts) {
        $atts = shortcode_atts(['employer_id' => 0], $atts);

        $employer_id = $atts['employer_id'];

        if (!$employer_id) {
            $job_id = get_the_ID();
            if ($job_id && get_post_type($job_id) === 'job_listing') {
                $employer_id = get_post_field('post_author', $job_id);
            }
        }

        if (!$employer_id) {
            return '';
        }

        $metrics = self::get_metrics($employer_id);
        $employer = get_userdata($employer_id);

        if (!$employer || $metrics['total_applications'] < 3) {
            return '';
        }

        // Format response time
        $response_time = '';
        if ($metrics['avg_response_hours'] <= 24) {
            $response_time = 'menos de 24 horas';
        } elseif ($metrics['avg_response_hours'] <= 48) {
            $response_time = '1-2 dias';
        } elseif ($metrics['avg_response_hours'] <= 72) {
            $response_time = '2-3 dias';
        } elseif ($metrics['avg_response_hours'] <= 168) {
            $response_time = 'menos de 1 semana';
        } else {
            $response_time = 'mas de 1 semana';
        }

        ob_start();
        ?>
        <div class="inspjob-employer-metrics">
            <h4>Metricas del empleador</h4>

            <?php if ($metrics['badge']): ?>
                <div class="employer-badge badge-<?php echo esc_attr($metrics['badge']); ?>">
                    <span class="badge-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </span>
                    <span class="badge-text"><?php echo esc_html(self::get_badge_label($metrics['badge'])); ?></span>
                </div>
            <?php endif; ?>

            <div class="metrics-grid">
                <div class="metric-item">
                    <div class="metric-value"><?php echo esc_html($metrics['response_rate']); ?>%</div>
                    <div class="metric-label">Tasa de respuesta</div>
                    <div class="metric-bar">
                        <div class="bar-fill" style="width: <?php echo esc_attr($metrics['response_rate']); ?>%"></div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-value"><?php echo esc_html($response_time); ?></div>
                    <div class="metric-label">Tiempo promedio de respuesta</div>
                </div>

                <div class="metric-item">
                    <div class="metric-value"><?php echo esc_html($metrics['feedback_rate']); ?>%</div>
                    <div class="metric-label">Proporciona feedback</div>
                    <div class="metric-bar">
                        <div class="bar-fill" style="width: <?php echo esc_attr($metrics['feedback_rate']); ?>%"></div>
                    </div>
                </div>
            </div>

            <p class="metrics-note">
                Basado en <?php echo esc_html($metrics['total_applications']); ?> aplicaciones recibidas
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get employer score for display in job cards
     */
    public static function get_score_for_job($job_id) {
        $employer_id = get_post_field('post_author', $job_id);
        if (!$employer_id) {
            return null;
        }

        $metrics = self::get_metrics($employer_id);

        if ($metrics['total_applications'] < 3) {
            return null;
        }

        return [
            'score'          => $metrics['score'],
            'badge'          => $metrics['badge'],
            'badge_label'    => self::get_badge_label($metrics['badge']),
            'response_rate'  => $metrics['response_rate'],
        ];
    }
}

// Initialize
InspJob_Employer_Score::init();

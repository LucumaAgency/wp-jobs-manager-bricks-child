<?php
/**
 * Gamification System
 * Manages badges, points, and levels for job seekers and employers
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Gamification {

    /**
     * Levels for job seekers
     */
    const LEVELS = [
        'bronze'   => ['min' => 0, 'label' => 'Bronce', 'color' => '#CD7F32'],
        'silver'   => ['min' => 100, 'label' => 'Plata', 'color' => '#C0C0C0'],
        'gold'     => ['min' => 300, 'label' => 'Oro', 'color' => '#FFD700'],
        'platinum' => ['min' => 600, 'label' => 'Platino', 'color' => '#E5E4E2'],
    ];

    /**
     * Initialize the gamification system
     */
    public static function init() {
        // Check badges on various events
        add_action('inspjob_application_created', [__CLASS__, 'on_application_created'], 10, 4);
        add_action('inspjob_application_status_changed', [__CLASS__, 'on_status_changed'], 10, 4);
        add_action('inspjob_job_seeker_registered', [__CLASS__, 'on_job_seeker_registered']);
        add_action('profile_update', [__CLASS__, 'check_profile_badges']);

        // Hourly badge check cron
        add_action('inspjob_check_badges', [__CLASS__, 'check_all_badges']);

        if (!wp_next_scheduled('inspjob_check_badges')) {
            wp_schedule_event(time(), 'hourly', 'inspjob_check_badges');
        }

        // Shortcodes
        add_shortcode('inspjob_user_badges', [__CLASS__, 'render_user_badges']);
        add_shortcode('inspjob_user_level', [__CLASS__, 'render_user_level']);
        add_shortcode('inspjob_leaderboard', [__CLASS__, 'render_leaderboard']);
    }

    /**
     * Get badges table name
     */
    private static function get_table($table = 'badges') {
        global $wpdb;
        return $wpdb->prefix . 'inspjob_' . $table;
    }

    /**
     * Get all badges
     */
    public static function get_all_badges($type = '') {
        global $wpdb;

        $table = self::get_table();
        $sql = "SELECT * FROM $table";

        if ($type) {
            $sql .= $wpdb->prepare(" WHERE badge_type = %s", $type);
        }

        $sql .= " ORDER BY points ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * Get user badges
     */
    public static function get_user_badges($user_id) {
        global $wpdb;

        $badges_table = self::get_table();
        $user_badges_table = self::get_table('user_badges');

        $sql = $wpdb->prepare(
            "SELECT b.*, ub.earned_at
             FROM $user_badges_table ub
             INNER JOIN $badges_table b ON ub.badge_id = b.id
             WHERE ub.user_id = %d
             ORDER BY ub.earned_at DESC",
            $user_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Check if user has a badge
     */
    public static function user_has_badge($user_id, $badge_key) {
        global $wpdb;

        $badges_table = self::get_table();
        $user_badges_table = self::get_table('user_badges');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $user_badges_table ub
             INNER JOIN $badges_table b ON ub.badge_id = b.id
             WHERE ub.user_id = %d AND b.badge_key = %s",
            $user_id,
            $badge_key
        ));

        return $count > 0;
    }

    /**
     * Award badge to user
     */
    public static function award_badge($user_id, $badge_key) {
        global $wpdb;

        // Already has badge?
        if (self::user_has_badge($user_id, $badge_key)) {
            return false;
        }

        // Get badge
        $badge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . " WHERE badge_key = %s",
            $badge_key
        ));

        if (!$badge) {
            return false;
        }

        // Award badge
        $result = $wpdb->insert(
            self::get_table('user_badges'),
            [
                'user_id'   => $user_id,
                'badge_id'  => $badge->id,
                'earned_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        if ($result) {
            // Add points
            if (class_exists('InspJob_Job_Seeker') && $badge->badge_type === 'job_seeker') {
                InspJob_Job_Seeker::add_points($user_id, $badge->points);
            }

            // Trigger action for notifications
            do_action('inspjob_badge_earned', $user_id, $badge);

            return true;
        }

        return false;
    }

    /**
     * Check and award badges for a user
     */
    public static function check_badges($user_id) {
        // Determine user type
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        if (in_array('job_seeker', (array) $user->roles)) {
            self::check_job_seeker_badges($user_id);
        }

        if (in_array('employer', (array) $user->roles) || in_array('administrator', (array) $user->roles)) {
            self::check_employer_badges($user_id);
        }
    }

    /**
     * Check job seeker specific badges
     */
    public static function check_job_seeker_badges($user_id) {
        if (!class_exists('InspJob_Job_Seeker')) {
            return;
        }

        $profile = InspJob_Job_Seeker::get_profile($user_id);
        if (!$profile) {
            return;
        }

        // Profile Complete badge
        if ($profile['profile_completion'] >= 100) {
            self::award_badge($user_id, 'profile_complete');
        }

        // Skills badge
        $skills = $profile['skills'] ?? [];
        if (count($skills) >= 5) {
            self::award_badge($user_id, 'skilled');
        }

        // Application badges
        if (class_exists('InspJob_Application_Tracker')) {
            $stats = InspJob_Application_Tracker::get_user_stats($user_id);

            if ($stats['total'] >= 1) {
                self::award_badge($user_id, 'first_application');
            }

            if ($stats['total'] >= 10) {
                self::award_badge($user_id, 'active_seeker');
            }

            if ($stats['hired'] >= 1) {
                self::award_badge($user_id, 'hired');
            }
        }
    }

    /**
     * Check employer specific badges
     */
    public static function check_employer_badges($user_id) {
        if (!class_exists('InspJob_Employer_Score')) {
            return;
        }

        $metrics = InspJob_Employer_Score::get_metrics($user_id);

        if ($metrics['total_applications'] < 5) {
            return; // Not enough data
        }

        // Responsive Employer
        if ($metrics['response_rate'] >= 80) {
            self::award_badge($user_id, 'responsive_employer');
        }

        // Feedback Champion
        if ($metrics['feedback_rate'] >= 90) {
            self::award_badge($user_id, 'feedback_champion');
        }

        // Fast Responder
        if ($metrics['avg_response_hours'] > 0 && $metrics['avg_response_hours'] <= 48) {
            self::award_badge($user_id, 'fast_responder');
        }

        // Top Employer
        if ($metrics['score'] >= 90) {
            self::award_badge($user_id, 'top_employer');
        }
    }

    /**
     * Event handler: Application created
     */
    public static function on_application_created($application_id, $job_id, $applicant_id, $employer_id) {
        self::check_job_seeker_badges($applicant_id);
    }

    /**
     * Event handler: Status changed
     */
    public static function on_status_changed($application_id, $from_status, $to_status, $application) {
        if ($to_status === 'hired') {
            self::check_job_seeker_badges($application->applicant_id);
        }

        // Always check employer badges on status change
        self::check_employer_badges($application->employer_id);
    }

    /**
     * Event handler: Job seeker registered
     */
    public static function on_job_seeker_registered($user_id) {
        // Award welcome badge or similar if exists
        // For now, just trigger initial check
        self::check_badges($user_id);
    }

    /**
     * Event handler: Profile update
     */
    public static function check_profile_badges($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        if (in_array('job_seeker', (array) $user->roles)) {
            self::check_job_seeker_badges($user_id);
        }
    }

    /**
     * Check all badges (cron)
     */
    public static function check_all_badges() {
        // Job seekers
        $job_seekers = get_users(['role' => 'job_seeker', 'fields' => 'ID']);
        foreach ($job_seekers as $user_id) {
            self::check_job_seeker_badges($user_id);
        }

        // Employers
        $employers = get_users(['role__in' => ['employer', 'administrator'], 'fields' => 'ID']);
        foreach ($employers as $user_id) {
            self::check_employer_badges($user_id);
        }
    }

    /**
     * Get level info for points
     */
    public static function get_level_for_points($points) {
        $current_level = 'bronze';
        $next_level = 'silver';
        $points_to_next = self::LEVELS['silver']['min'];

        foreach (array_reverse(self::LEVELS, true) as $key => $level) {
            if ($points >= $level['min']) {
                $current_level = $key;
                break;
            }
        }

        // Find next level
        $found_current = false;
        foreach (self::LEVELS as $key => $level) {
            if ($found_current) {
                $next_level = $key;
                $points_to_next = $level['min'] - $points;
                break;
            }
            if ($key === $current_level) {
                $found_current = true;
            }
        }

        if ($current_level === 'platinum') {
            $next_level = null;
            $points_to_next = 0;
        }

        return [
            'level'          => $current_level,
            'label'          => self::LEVELS[$current_level]['label'],
            'color'          => self::LEVELS[$current_level]['color'],
            'points'         => $points,
            'next_level'     => $next_level,
            'next_label'     => $next_level ? self::LEVELS[$next_level]['label'] : null,
            'points_to_next' => max(0, $points_to_next),
            'progress'       => self::calculate_level_progress($points, $current_level),
        ];
    }

    /**
     * Calculate progress within current level
     */
    private static function calculate_level_progress($points, $current_level) {
        $levels = array_keys(self::LEVELS);
        $current_index = array_search($current_level, $levels);

        if ($current_index === false || $current_level === 'platinum') {
            return 100;
        }

        $current_min = self::LEVELS[$current_level]['min'];
        $next_level = $levels[$current_index + 1] ?? null;

        if (!$next_level) {
            return 100;
        }

        $next_min = self::LEVELS[$next_level]['min'];
        $range = $next_min - $current_min;

        if ($range <= 0) {
            return 100;
        }

        return min(100, round((($points - $current_min) / $range) * 100));
    }

    /**
     * Render user badges shortcode
     */
    public static function render_user_badges($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'limit'   => 10,
            'show_all' => false,
        ], $atts);

        $user_id = absint($atts['user_id']);
        if (!$user_id) {
            return '';
        }

        $user_badges = self::get_user_badges($user_id);

        if (empty($user_badges) && !$atts['show_all']) {
            return '<p class="inspjob-no-badges">Aun no tienes insignias. Completa tu perfil y aplica a empleos para ganarlas.</p>';
        }

        ob_start();
        ?>
        <div class="inspjob-badges-grid">
            <?php foreach ($user_badges as $badge): ?>
                <div class="badge-item earned">
                    <div class="badge-icon">
                        <?php echo self::get_badge_icon($badge->badge_icon); ?>
                    </div>
                    <div class="badge-info">
                        <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
                        <span class="badge-description"><?php echo esc_html($badge->badge_description); ?></span>
                        <span class="badge-earned">Obtenido: <?php echo esc_html(date_i18n('d M Y', strtotime($badge->earned_at))); ?></span>
                    </div>
                    <div class="badge-points">+<?php echo esc_html($badge->points); ?></div>
                </div>
            <?php endforeach; ?>

            <?php if ($atts['show_all']):
                // Show unearned badges
                $user = get_userdata($user_id);
                $type = in_array('job_seeker', (array) $user->roles) ? 'job_seeker' : 'employer';
                $all_badges = self::get_all_badges($type);
                $earned_keys = array_column($user_badges, 'badge_key');

                foreach ($all_badges as $badge):
                    if (in_array($badge->badge_key, $earned_keys)) continue;
            ?>
                <div class="badge-item locked">
                    <div class="badge-icon">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="badge-info">
                        <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
                        <span class="badge-description"><?php echo esc_html($badge->badge_description); ?></span>
                    </div>
                    <div class="badge-points">+<?php echo esc_html($badge->points); ?></div>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render user level shortcode
     */
    public static function render_user_level($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'compact' => false,
        ], $atts);

        $user_id = absint($atts['user_id']);
        if (!$user_id) {
            return '';
        }

        $points = 0;
        if (class_exists('InspJob_Job_Seeker')) {
            $points = (int) get_user_meta($user_id, '_job_seeker_points', true);
        }

        $level_info = self::get_level_for_points($points);

        ob_start();

        if ($atts['compact']):
        ?>
            <div class="inspjob-level-compact level-<?php echo esc_attr($level_info['level']); ?>">
                <span class="level-badge" style="background-color: <?php echo esc_attr($level_info['color']); ?>">
                    <?php echo esc_html($level_info['label']); ?>
                </span>
            </div>
        <?php else: ?>
            <div class="inspjob-level-card level-<?php echo esc_attr($level_info['level']); ?>">
                <div class="level-header">
                    <div class="level-icon" style="background-color: <?php echo esc_attr($level_info['color']); ?>">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <div class="level-info">
                        <span class="level-label"><?php echo esc_html($level_info['label']); ?></span>
                        <span class="points-count"><?php echo esc_html($level_info['points']); ?> puntos</span>
                    </div>
                </div>

                <?php if ($level_info['next_level']): ?>
                    <div class="level-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($level_info['progress']); ?>%"></div>
                        </div>
                        <span class="progress-text">
                            <?php echo esc_html($level_info['points_to_next']); ?> puntos para <?php echo esc_html($level_info['next_label']); ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="level-max">
                        <span>Nivel maximo alcanzado</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php
        endif;

        return ob_get_clean();
    }

    /**
     * Render leaderboard shortcode
     */
    public static function render_leaderboard($atts) {
        $atts = shortcode_atts([
            'limit' => 10,
            'type'  => 'job_seeker',
        ], $atts);

        $users = get_users([
            'role'       => $atts['type'],
            'meta_key'   => '_job_seeker_points',
            'orderby'    => 'meta_value_num',
            'order'      => 'DESC',
            'number'     => $atts['limit'],
        ]);

        if (empty($users)) {
            return '<p>No hay datos de clasificacion aun.</p>';
        }

        ob_start();
        ?>
        <div class="inspjob-leaderboard">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>Nivel</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user):
                        $points = (int) get_user_meta($user->ID, '_job_seeker_points', true);
                        $level_info = self::get_level_for_points($points);
                    ?>
                        <tr class="<?php echo $user->ID === get_current_user_id() ? 'current-user' : ''; ?>">
                            <td class="rank"><?php echo $index + 1; ?></td>
                            <td class="user">
                                <?php echo get_avatar($user->ID, 32); ?>
                                <span><?php echo esc_html($user->display_name); ?></span>
                            </td>
                            <td class="level">
                                <span class="level-badge" style="background-color: <?php echo esc_attr($level_info['color']); ?>">
                                    <?php echo esc_html($level_info['label']); ?>
                                </span>
                            </td>
                            <td class="points"><?php echo esc_html($points); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get badge icon SVG
     */
    private static function get_badge_icon($icon_name) {
        $icons = [
            'user-check' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
            'send' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
            'search' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
            'star' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
            'award' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>',
            'message-circle' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
            'thumbs-up' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>',
            'zap' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
            'trophy' => '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>',
        ];

        return $icons[$icon_name] ?? '<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>';
    }
}

// Initialize
InspJob_Gamification::init();

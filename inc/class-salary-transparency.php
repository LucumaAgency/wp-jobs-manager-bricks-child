<?php
/**
 * Salary Transparency System
 * Provides salary benchmarking and market comparison
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Salary_Transparency {

    /**
     * Salary status labels
     */
    const SALARY_LABELS = [
        'below_market'  => 'Por debajo del mercado',
        'competitive'   => 'Competitivo',
        'above_market'  => 'Por encima del mercado',
    ];

    /**
     * Initialize the system
     */
    public static function init() {
        // Register cron for benchmark updates
        add_action('inspjob_update_salary_benchmarks', [__CLASS__, 'update_benchmarks']);

        if (!wp_next_scheduled('inspjob_update_salary_benchmarks')) {
            wp_schedule_event(time(), 'weekly', 'inspjob_update_salary_benchmarks');
        }

        // Add salary comparison to job display
        add_filter('inspjob_single_job_data', [__CLASS__, 'add_salary_comparison'], 10, 2);

        // Shortcode for salary info
        add_shortcode('inspjob_salary_info', [__CLASS__, 'render_salary_info']);
    }

    /**
     * Get salary benchmark table name
     */
    private static function get_table() {
        global $wpdb;
        return $wpdb->prefix . 'inspjob_salary_benchmarks';
    }

    /**
     * Get salary status for a job
     *
     * @param int $job_id
     * @return array|null
     */
    public static function get_salary_status($job_id) {
        $salary_min = (int) get_post_meta($job_id, '_job_salary_min', true);
        $salary_max = (int) get_post_meta($job_id, '_job_salary_max', true);

        if ($salary_min == 0 && $salary_max == 0) {
            return null;
        }

        // Get benchmark data
        $categories = wp_get_post_terms($job_id, 'job_listing_category', ['fields' => 'ids']);
        $category_id = !empty($categories) ? $categories[0] : 0;
        $experience = get_post_meta($job_id, '_job_experience', true) ?: 'mid';
        $location = get_post_meta($job_id, '_job_location', true);

        $benchmark = self::get_benchmark($category_id, $experience, $location);

        if (!$benchmark) {
            // Fall back to general benchmark
            $benchmark = self::calculate_live_benchmark($category_id, $experience);
        }

        if (!$benchmark) {
            return null;
        }

        // Calculate job's midpoint
        $job_salary = ($salary_min + $salary_max) / 2;
        if ($salary_max == 0) {
            $job_salary = $salary_min;
        } elseif ($salary_min == 0) {
            $job_salary = $salary_max;
        }

        // Determine status
        $status = 'competitive';
        $percentile = 50;

        if ($job_salary < $benchmark['percentile_25']) {
            $status = 'below_market';
            $percentile = 25;
        } elseif ($job_salary > $benchmark['percentile_75']) {
            $status = 'above_market';
            $percentile = 75;
        } elseif ($job_salary < $benchmark['percentile_50']) {
            $percentile = 25 + (($job_salary - $benchmark['percentile_25']) / ($benchmark['percentile_50'] - $benchmark['percentile_25'])) * 25;
        } else {
            $percentile = 50 + (($job_salary - $benchmark['percentile_50']) / ($benchmark['percentile_75'] - $benchmark['percentile_50'])) * 25;
        }

        return [
            'status'      => $status,
            'label'       => self::SALARY_LABELS[$status],
            'percentile'  => round($percentile),
            'benchmark'   => $benchmark,
            'job_salary'  => $job_salary,
            'sample_size' => $benchmark['sample_size'] ?? 0,
        ];
    }

    /**
     * Get stored benchmark
     */
    private static function get_benchmark($category_id, $experience_level, $location = '') {
        global $wpdb;

        $table = self::get_table();

        // Try with location first
        if (!empty($location)) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE category_id = %d AND experience_level = %s AND location LIKE %s ORDER BY updated_at DESC LIMIT 1",
                $category_id,
                $experience_level,
                '%' . $wpdb->esc_like(explode(',', $location)[0]) . '%'
            ), ARRAY_A);

            if ($result) {
                return $result;
            }
        }

        // Fall back to category + experience only
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE category_id = %d AND experience_level = %s AND (location = '' OR location IS NULL) ORDER BY updated_at DESC LIMIT 1",
            $category_id,
            $experience_level
        ), ARRAY_A);

        return $result;
    }

    /**
     * Calculate live benchmark from current job listings
     */
    public static function calculate_live_benchmark($category_id = 0, $experience_level = '', $location = '') {
        global $wpdb;

        $meta_query = [];

        // Build meta query for salary
        $meta_query[] = [
            'key'     => '_job_salary_min',
            'value'   => 0,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ];

        if (!empty($experience_level)) {
            $meta_query[] = [
                'key'   => '_job_experience',
                'value' => $experience_level,
            ];
        }

        $args = [
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => $meta_query,
            'fields'         => 'ids',
        ];

        if ($category_id) {
            $args['tax_query'] = [[
                'taxonomy' => 'job_listing_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ]];
        }

        $job_ids = get_posts($args);

        if (empty($job_ids)) {
            return null;
        }

        // Collect salaries
        $salaries = [];
        foreach ($job_ids as $job_id) {
            $min = (int) get_post_meta($job_id, '_job_salary_min', true);
            $max = (int) get_post_meta($job_id, '_job_salary_max', true);

            if ($min > 0) {
                $avg = $max > 0 ? ($min + $max) / 2 : $min;
                $salaries[] = $avg;
            }
        }

        if (empty($salaries)) {
            return null;
        }

        sort($salaries);
        $count = count($salaries);

        return [
            'percentile_25' => (int) $salaries[max(0, floor($count * 0.25) - 1)],
            'percentile_50' => (int) $salaries[max(0, floor($count * 0.50) - 1)],
            'percentile_75' => (int) $salaries[max(0, floor($count * 0.75) - 1)],
            'sample_size'   => $count,
        ];
    }

    /**
     * Update all benchmarks (cron job)
     */
    public static function update_benchmarks() {
        global $wpdb;

        $table = self::get_table();

        // Get all categories
        $categories = get_terms([
            'taxonomy'   => 'job_listing_category',
            'hide_empty' => true,
        ]);

        $experience_levels = ['entry', 'junior', 'mid', 'senior', 'expert'];
        $now = current_time('mysql');

        foreach ($categories as $category) {
            foreach ($experience_levels as $level) {
                $benchmark = self::calculate_live_benchmark($category->term_id, $level);

                if (!$benchmark || $benchmark['sample_size'] < 3) {
                    continue;
                }

                // Check if exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE category_id = %d AND experience_level = %s AND (location = '' OR location IS NULL)",
                    $category->term_id,
                    $level
                ));

                $data = [
                    'category_id'      => $category->term_id,
                    'experience_level' => $level,
                    'location'         => '',
                    'percentile_25'    => $benchmark['percentile_25'],
                    'percentile_50'    => $benchmark['percentile_50'],
                    'percentile_75'    => $benchmark['percentile_75'],
                    'sample_size'      => $benchmark['sample_size'],
                    'updated_at'       => $now,
                ];

                if ($existing) {
                    $wpdb->update($table, $data, ['id' => $existing]);
                } else {
                    $wpdb->insert($table, $data);
                }
            }
        }

        // Also calculate general benchmark (no category filter)
        foreach ($experience_levels as $level) {
            $benchmark = self::calculate_live_benchmark(0, $level);

            if (!$benchmark || $benchmark['sample_size'] < 5) {
                continue;
            }

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE category_id = 0 AND experience_level = %s AND (location = '' OR location IS NULL)",
                $level
            ));

            $data = [
                'category_id'      => 0,
                'experience_level' => $level,
                'location'         => '',
                'percentile_25'    => $benchmark['percentile_25'],
                'percentile_50'    => $benchmark['percentile_50'],
                'percentile_75'    => $benchmark['percentile_75'],
                'sample_size'      => $benchmark['sample_size'],
                'updated_at'       => $now,
            ];

            if ($existing) {
                $wpdb->update($table, $data, ['id' => $existing]);
            } else {
                $wpdb->insert($table, $data);
            }
        }
    }

    /**
     * Add salary comparison to job data
     */
    public static function add_salary_comparison($data, $job_id) {
        $data['salary_status'] = self::get_salary_status($job_id);
        return $data;
    }

    /**
     * Render salary info shortcode
     */
    public static function render_salary_info($atts) {
        $atts = shortcode_atts(['job_id' => 0], $atts);

        $job_id = $atts['job_id'] ?: get_the_ID();

        if (!$job_id) {
            return '';
        }

        $salary_status = self::get_salary_status($job_id);

        if (!$salary_status) {
            return '';
        }

        $status = $salary_status['status'];
        $benchmark = $salary_status['benchmark'];

        ob_start();
        ?>
        <div class="inspjob-salary-info status-<?php echo esc_attr($status); ?>">
            <div class="salary-badge">
                <span class="badge-icon">
                    <?php if ($status === 'above_market'): ?>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                    <?php elseif ($status === 'below_market'): ?>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                            <polyline points="17 18 23 18 23 12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    <?php endif; ?>
                </span>
                <span class="badge-text"><?php echo esc_html($salary_status['label']); ?></span>
            </div>

            <?php if (!empty($benchmark['sample_size']) && $benchmark['sample_size'] >= 3): ?>
                <div class="salary-tooltip">
                    <div class="tooltip-content">
                        <h5>Comparacion salarial</h5>
                        <div class="benchmark-chart">
                            <div class="chart-bar">
                                <div class="bar-segment p25" style="width: 25%;">
                                    <span class="segment-value">S/<?php echo number_format($benchmark['percentile_25'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="bar-segment p50" style="width: 25%;">
                                    <span class="segment-value">S/<?php echo number_format($benchmark['percentile_50'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="bar-segment p75" style="width: 25%;">
                                    <span class="segment-value">S/<?php echo number_format($benchmark['percentile_75'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="bar-segment p100" style="width: 25%;"></div>
                                <div class="job-marker" style="left: <?php echo esc_attr($salary_status['percentile']); ?>%;">
                                    <span class="marker-label">Este empleo</span>
                                </div>
                            </div>
                            <div class="chart-labels">
                                <span>P25</span>
                                <span>P50 (Mediana)</span>
                                <span>P75</span>
                            </div>
                        </div>
                        <p class="benchmark-note">Basado en <?php echo esc_html($benchmark['sample_size']); ?> ofertas similares</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get salary range for display
     */
    public static function format_salary_range($min, $max) {
        if ($min && $max && $min != $max) {
            return 'S/ ' . number_format($min, 0, ',', '.') . ' - ' . number_format($max, 0, ',', '.');
        } elseif ($min) {
            return 'S/ ' . number_format($min, 0, ',', '.');
        } elseif ($max) {
            return 'S/ ' . number_format($max, 0, ',', '.');
        }
        return 'Salario competitivo';
    }
}

// Initialize
InspJob_Salary_Transparency::init();

<?php
/**
 * Matching Engine - Job-Candidate Matching Algorithm
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Matching_Engine {

    /**
     * Weights for different matching criteria
     */
    const WEIGHTS = [
        'salary'     => 25,
        'experience' => 20,
        'category'   => 15,
        'skills'     => 15,
        'location'   => 10,
        'remote'     => 10,
        'job_type'   => 5,
    ];

    /**
     * Experience level order for comparison
     */
    const EXPERIENCE_ORDER = [
        'entry'  => 1,
        'junior' => 2,
        'mid'    => 3,
        'senior' => 4,
        'expert' => 5,
    ];

    /**
     * Initialize the matching engine
     */
    public static function init() {
        add_shortcode('inspjob_recommended_jobs', [__CLASS__, 'render_recommended_jobs']);
        add_shortcode('inspjob_match_score', [__CLASS__, 'render_match_score']);

        // Add match score to job cards
        add_filter('inspjob_job_card_data', [__CLASS__, 'add_match_score_to_card'], 10, 2);
    }

    /**
     * Calculate match score between a candidate and a job
     *
     * @param int $user_id Candidate user ID
     * @param int $job_id Job listing ID
     * @return int Match score (0-100)
     */
    public static function calculate_match_score($user_id, $job_id) {
        // Get candidate profile
        $profile = null;
        if (class_exists('InspJob_Job_Seeker')) {
            $profile = InspJob_Job_Seeker::get_profile($user_id);
        }

        if (!$profile) {
            return 0;
        }

        // Get job data
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'job_listing') {
            return 0;
        }

        $job_data = self::get_job_data($job_id);

        // Calculate individual scores
        $scores = [
            'salary'     => self::score_salary($profile, $job_data),
            'experience' => self::score_experience($profile, $job_data),
            'category'   => self::score_category($profile, $job_data),
            'skills'     => self::score_skills($profile, $job_data),
            'location'   => self::score_location($profile, $job_data),
            'remote'     => self::score_remote($profile, $job_data),
            'job_type'   => self::score_job_type($profile, $job_data),
        ];

        // Calculate weighted average
        $total_weight = array_sum(self::WEIGHTS);
        $weighted_score = 0;

        foreach ($scores as $key => $score) {
            $weighted_score += $score * self::WEIGHTS[$key];
        }

        $final_score = round(($weighted_score / $total_weight) * 100);

        return min(100, max(0, $final_score));
    }

    /**
     * Get structured job data
     */
    private static function get_job_data($job_id) {
        $data = [
            'salary_min'       => (int) get_post_meta($job_id, '_job_salary_min', true),
            'salary_max'       => (int) get_post_meta($job_id, '_job_salary_max', true),
            'experience'       => get_post_meta($job_id, '_job_experience', true),
            'location'         => get_post_meta($job_id, '_job_location', true),
            'remote'           => get_post_meta($job_id, '_remote_work', true),
            'skills'           => [],
            'categories'       => [],
            'job_types'        => [],
        ];

        // Get skills from job meta (if stored)
        $skills_meta = get_post_meta($job_id, '_job_skills', true);
        if ($skills_meta) {
            $data['skills'] = is_array($skills_meta) ? $skills_meta : (json_decode($skills_meta, true) ?: []);
        }

        // Get categories
        $categories = wp_get_post_terms($job_id, 'job_listing_category', ['fields' => 'ids']);
        $data['categories'] = is_wp_error($categories) ? [] : $categories;

        // Get job types
        $types = wp_get_post_terms($job_id, 'job_listing_type', ['fields' => 'slugs']);
        $data['job_types'] = is_wp_error($types) ? [] : $types;

        return $data;
    }

    /**
     * Score salary overlap
     * Returns 1.0 for perfect overlap, 0 for no overlap
     */
    private static function score_salary($profile, $job_data) {
        $candidate_min = (int) ($profile['salary_min'] ?? 0);
        $candidate_max = (int) ($profile['salary_max'] ?? 0);
        $job_min = $job_data['salary_min'];
        $job_max = $job_data['salary_max'];

        // If no salary data available, return neutral score
        if (($candidate_min == 0 && $candidate_max == 0) || ($job_min == 0 && $job_max == 0)) {
            return 0.5;
        }

        // Normalize: if only one value is set, use it for both
        if ($candidate_max == 0) $candidate_max = $candidate_min;
        if ($job_max == 0) $job_max = $job_min;
        if ($candidate_min == 0) $candidate_min = $candidate_max;
        if ($job_min == 0) $job_min = $job_max;

        // Check for overlap
        $overlap_start = max($candidate_min, $job_min);
        $overlap_end = min($candidate_max, $job_max);

        if ($overlap_start > $overlap_end) {
            // No overlap - calculate how far apart
            $gap = ($candidate_min > $job_max)
                ? $candidate_min - $job_max
                : $job_min - $candidate_max;

            $avg_salary = ($candidate_min + $candidate_max + $job_min + $job_max) / 4;
            $gap_percentage = $gap / max($avg_salary, 1);

            // Penalize based on gap
            return max(0, 1 - ($gap_percentage * 2));
        }

        // Calculate overlap percentage
        $candidate_range = $candidate_max - $candidate_min;
        $job_range = $job_max - $job_min;
        $overlap_range = $overlap_end - $overlap_start;

        if ($candidate_range == 0 && $job_range == 0) {
            return 1.0; // Exact match
        }

        $max_range = max($candidate_range, $job_range, 1);
        $overlap_score = $overlap_range / $max_range;

        return min(1.0, 0.5 + ($overlap_score * 0.5));
    }

    /**
     * Score experience level match
     * Returns 1.0 for exact match, decreasing for distance
     */
    private static function score_experience($profile, $job_data) {
        $candidate_level = $profile['experience_level'] ?? '';
        $job_level = $job_data['experience'];

        if (empty($candidate_level) || empty($job_level)) {
            return 0.5; // Neutral
        }

        $candidate_order = self::EXPERIENCE_ORDER[$candidate_level] ?? 3;
        $job_order = self::EXPERIENCE_ORDER[$job_level] ?? 3;

        $diff = abs($candidate_order - $job_order);

        switch ($diff) {
            case 0:
                return 1.0;
            case 1:
                return 0.7;
            case 2:
                return 0.3;
            default:
                return 0.1;
        }
    }

    /**
     * Score category match
     * Uses Jaccard similarity coefficient
     */
    private static function score_category($profile, $job_data) {
        $candidate_categories = $profile['categories'] ?? [];
        $job_categories = $job_data['categories'];

        if (empty($candidate_categories) || empty($job_categories)) {
            return 0.5;
        }

        $intersection = array_intersect($candidate_categories, $job_categories);
        $union = array_unique(array_merge($candidate_categories, $job_categories));

        if (empty($union)) {
            return 0.5;
        }

        return count($intersection) / count($union);
    }

    /**
     * Score skills match
     * Calculates what percentage of required skills the candidate has
     */
    private static function score_skills($profile, $job_data) {
        $candidate_skills = $profile['skills'] ?? [];
        $job_skills = $job_data['skills'];

        if (empty($job_skills)) {
            return empty($candidate_skills) ? 0.5 : 0.6;
        }

        if (empty($candidate_skills)) {
            return 0.2;
        }

        // Normalize skills for comparison (lowercase)
        $candidate_skills_lower = array_map('strtolower', $candidate_skills);
        $job_skills_lower = array_map('strtolower', $job_skills);

        $matches = 0;
        foreach ($job_skills_lower as $required_skill) {
            foreach ($candidate_skills_lower as $candidate_skill) {
                // Check for partial match
                if (strpos($candidate_skill, $required_skill) !== false ||
                    strpos($required_skill, $candidate_skill) !== false) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / count($job_skills_lower);
    }

    /**
     * Score location match
     */
    private static function score_location($profile, $job_data) {
        $candidate_location = strtolower(trim($profile['location'] ?? ''));
        $job_location = strtolower(trim($job_data['location']));

        if (empty($candidate_location) || empty($job_location)) {
            return 0.5;
        }

        // Exact match
        if ($candidate_location === $job_location) {
            return 1.0;
        }

        // City match (first part before comma)
        $candidate_city = explode(',', $candidate_location)[0];
        $job_city = explode(',', $job_location)[0];

        if (trim($candidate_city) === trim($job_city)) {
            return 0.8;
        }

        // Partial match
        if (strpos($candidate_location, $job_city) !== false ||
            strpos($job_location, $candidate_city) !== false) {
            return 0.5;
        }

        return 0.2;
    }

    /**
     * Score remote work preference match
     */
    private static function score_remote($profile, $job_data) {
        $candidate_pref = $profile['remote_preference'] ?? '';
        $job_remote = $job_data['remote'] ? 'yes' : 'no';

        if (empty($candidate_pref)) {
            return 0.5;
        }

        // Candidate wants remote, job is remote
        if ($candidate_pref === 'yes' && $job_remote === 'yes') {
            return 1.0;
        }

        // Candidate wants remote, job is not remote
        if ($candidate_pref === 'yes' && $job_remote === 'no') {
            return 0.0;
        }

        // Candidate wants presential, job is remote
        if ($candidate_pref === 'no' && $job_remote === 'yes') {
            return 0.3;
        }

        // Candidate wants presential, job is presential
        if ($candidate_pref === 'no' && $job_remote === 'no') {
            return 1.0;
        }

        // Hybrid is flexible
        if ($candidate_pref === 'hybrid') {
            return 0.8;
        }

        return 0.5;
    }

    /**
     * Score job type match
     */
    private static function score_job_type($profile, $job_data) {
        $candidate_types = $profile['job_types'] ?? [];
        $job_types = $job_data['job_types'];

        if (empty($candidate_types) || empty($job_types)) {
            return 0.5;
        }

        $intersection = array_intersect($candidate_types, $job_types);

        return empty($intersection) ? 0.0 : 1.0;
    }

    /**
     * Get recommended jobs for a user
     *
     * @param int $user_id
     * @param int $limit
     * @return array Array of jobs with match scores
     */
    public static function get_recommended_jobs($user_id, $limit = 10) {
        $profile = null;
        if (class_exists('InspJob_Job_Seeker')) {
            $profile = InspJob_Job_Seeker::get_profile($user_id);
        }

        if (!$profile || empty($profile['profile_completion']) || $profile['profile_completion'] < 30) {
            return [];
        }

        // Build query based on profile preferences
        $args = [
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => $limit * 3, // Get more to filter and sort by match
            'meta_query'     => [
                'relation' => 'AND',
            ],
        ];

        // Filter by categories if set
        if (!empty($profile['categories'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'job_listing_category',
                'field'    => 'term_id',
                'terms'    => $profile['categories'],
            ];
        }

        // Filter by job types if set
        if (!empty($profile['job_types'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'job_listing_type',
                'field'    => 'slug',
                'terms'    => $profile['job_types'],
            ];
        }

        $jobs = get_posts($args);

        // Calculate match scores
        $scored_jobs = [];
        foreach ($jobs as $job) {
            // Skip already applied jobs
            if (class_exists('InspJob_Application_Tracker') &&
                InspJob_Application_Tracker::has_applied($job->ID, $user_id)) {
                continue;
            }

            $score = self::calculate_match_score($user_id, $job->ID);

            $scored_jobs[] = [
                'job'         => $job,
                'match_score' => $score,
            ];
        }

        // Sort by match score descending
        usort($scored_jobs, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });

        // Return top results
        return array_slice($scored_jobs, 0, $limit);
    }

    /**
     * Get match score breakdown for display
     */
    public static function get_match_breakdown($user_id, $job_id) {
        $profile = null;
        if (class_exists('InspJob_Job_Seeker')) {
            $profile = InspJob_Job_Seeker::get_profile($user_id);
        }

        if (!$profile) {
            return null;
        }

        $job_data = self::get_job_data($job_id);

        $breakdown = [];
        $criteria_labels = [
            'salary'     => 'Salario',
            'experience' => 'Experiencia',
            'category'   => 'Categoria',
            'skills'     => 'Habilidades',
            'location'   => 'Ubicacion',
            'remote'     => 'Modalidad',
            'job_type'   => 'Tipo de trabajo',
        ];

        foreach (self::WEIGHTS as $key => $weight) {
            $method = 'score_' . $key;
            $score = self::$method($profile, $job_data);

            $breakdown[$key] = [
                'label'  => $criteria_labels[$key],
                'score'  => round($score * 100),
                'weight' => $weight,
            ];
        }

        return $breakdown;
    }

    /**
     * Add match score to job card data
     */
    public static function add_match_score_to_card($data, $job_id) {
        if (!is_user_logged_in()) {
            return $data;
        }

        $user = wp_get_current_user();
        if (!in_array('job_seeker', (array) $user->roles)) {
            return $data;
        }

        $data['match_score'] = self::calculate_match_score($user->ID, $job_id);

        return $data;
    }

    /**
     * Render recommended jobs shortcode
     */
    public static function render_recommended_jobs($atts) {
        $atts = shortcode_atts([
            'limit'   => 6,
            'columns' => 3,
        ], $atts);

        if (!is_user_logged_in()) {
            return '<p class="inspjob-notice">Inicia sesion para ver empleos recomendados para ti.</p>';
        }

        $user_id = get_current_user_id();
        $recommendations = self::get_recommended_jobs($user_id, $atts['limit']);

        if (empty($recommendations)) {
            return '<div class="inspjob-recommendations-empty">
                <p>Completa tu perfil para recibir recomendaciones personalizadas</p>
                <a href="' . esc_url(home_url('/mi-perfil/')) . '" class="inspjob-btn inspjob-btn-primary">Completar perfil</a>
            </div>';
        }

        ob_start();
        ?>
        <div class="inspjob-recommended-jobs inspjob-cols-<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($recommendations as $item):
                $job = $item['job'];
                $match_score = $item['match_score'];
                $company_name = get_post_meta($job->ID, '_company_name', true);
                $company_logo = get_post_meta($job->ID, '_company_logo', true);
                $job_location = get_post_meta($job->ID, '_job_location', true);
                $salary_min = get_post_meta($job->ID, '_job_salary_min', true);
                $salary_max = get_post_meta($job->ID, '_job_salary_max', true);
            ?>
                <div class="recommended-job-card">
                    <div class="card-header">
                        <div class="match-badge match-<?php echo $match_score >= 80 ? 'high' : ($match_score >= 50 ? 'medium' : 'low'); ?>">
                            <span class="match-value"><?php echo esc_html($match_score); ?>%</span>
                            <span class="match-text">Match</span>
                        </div>
                        <?php if ($company_logo): ?>
                            <img src="<?php echo esc_url($company_logo); ?>" alt="" class="company-logo">
                        <?php else: ?>
                            <span class="company-initials"><?php echo esc_html(substr($company_name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>

                    <h4 class="job-title">
                        <a href="<?php echo esc_url(get_permalink($job->ID)); ?>">
                            <?php echo esc_html($job->post_title); ?>
                        </a>
                    </h4>

                    <p class="company-name"><?php echo esc_html($company_name); ?></p>

                    <?php if ($job_location): ?>
                        <p class="job-location">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo esc_html($job_location); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($salary_min || $salary_max): ?>
                        <p class="job-salary">
                            S/ <?php
                            if ($salary_min && $salary_max) {
                                echo number_format($salary_min, 0, ',', '.') . ' - ' . number_format($salary_max, 0, ',', '.');
                            } elseif ($salary_min) {
                                echo number_format($salary_min, 0, ',', '.') . '+';
                            } else {
                                echo number_format($salary_max, 0, ',', '.');
                            }
                            ?>
                        </p>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="inspjob-btn inspjob-btn-primary inspjob-btn-sm inspjob-btn-block">
                        Ver empleo
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render match score for a specific job
     */
    public static function render_match_score($atts) {
        $atts = shortcode_atts(['job_id' => 0], $atts);

        $job_id = $atts['job_id'] ?: get_the_ID();

        if (!is_user_logged_in() || !$job_id) {
            return '';
        }

        $user = wp_get_current_user();
        if (!in_array('job_seeker', (array) $user->roles)) {
            return '';
        }

        $score = self::calculate_match_score($user->ID, $job_id);

        $class = 'match-low';
        if ($score >= 80) {
            $class = 'match-high';
        } elseif ($score >= 50) {
            $class = 'match-medium';
        }

        return '<div class="inspjob-match-score ' . esc_attr($class) . '">
            <span class="score-value">' . esc_html($score) . '%</span>
            <span class="score-label">Match con tu perfil</span>
        </div>';
    }
}

// Initialize
InspJob_Matching_Engine::init();

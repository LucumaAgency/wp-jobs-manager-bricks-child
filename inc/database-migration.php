<?php
/**
 * Database Migration Handler for InspJobPortal
 * Creates and manages custom database tables
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Database_Migration {

    /**
     * Database version for tracking migrations
     */
    const DB_VERSION = '1.0.0';

    /**
     * Option key for stored database version
     */
    const DB_VERSION_OPTION = 'inspjob_db_version';

    /**
     * Initialize the migration system
     */
    public static function init() {
        add_action('admin_init', [__CLASS__, 'check_and_run_migrations']);
        register_activation_hook(get_stylesheet_directory() . '/functions.php', [__CLASS__, 'run_migrations']);
    }

    /**
     * Check if migrations need to run and execute them
     */
    public static function check_and_run_migrations() {
        $current_version = get_option(self::DB_VERSION_OPTION, '0.0.0');

        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::run_migrations();
        }
    }

    /**
     * Run all database migrations
     */
    public static function run_migrations() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Applications table
        self::create_applications_table($charset_collate);

        // Application history table
        self::create_application_history_table($charset_collate);

        // Rejection reasons table
        self::create_rejection_reasons_table($charset_collate);

        // Salary benchmarks table
        self::create_salary_benchmarks_table($charset_collate);

        // Badges table
        self::create_badges_table($charset_collate);

        // User badges table
        self::create_user_badges_table($charset_collate);

        // Employer contacts table (for reverse applications)
        self::create_employer_contacts_table($charset_collate);

        // Seed initial data
        self::seed_rejection_reasons();
        self::seed_badges();

        // Update version
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Create applications table
     */
    private static function create_applications_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_applications';

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NOT NULL,
            applicant_id BIGINT UNSIGNED NOT NULL,
            employer_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            cover_letter TEXT,
            resume_url VARCHAR(255),
            match_score INT DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            viewed_at DATETIME NULL,
            responded_at DATETIME NULL,
            rejection_reason_id INT NULL,
            candidate_feedback TEXT,
            candidate_feedback_rating INT NULL,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY applicant_id (applicant_id),
            KEY employer_id (employer_id),
            KEY status (status),
            UNIQUE KEY job_applicant (job_id, applicant_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create application history table
     */
    private static function create_application_history_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_application_history';

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            application_id BIGINT UNSIGNED NOT NULL,
            from_status VARCHAR(50),
            to_status VARCHAR(50) NOT NULL,
            changed_by BIGINT UNSIGNED NOT NULL,
            note TEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY application_id (application_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create rejection reasons table
     */
    private static function create_rejection_reasons_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_rejection_reasons';

        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            reason_key VARCHAR(50) NOT NULL,
            reason_text VARCHAR(255) NOT NULL,
            display_order INT DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY reason_key (reason_key)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create salary benchmarks table
     */
    private static function create_salary_benchmarks_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_salary_benchmarks';

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT UNSIGNED,
            experience_level VARCHAR(50),
            location VARCHAR(255),
            percentile_25 INT,
            percentile_50 INT,
            percentile_75 INT,
            sample_size INT,
            updated_at DATETIME,
            PRIMARY KEY (id),
            KEY category_level (category_id, experience_level),
            KEY location (location)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create badges table
     */
    private static function create_badges_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_badges';

        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            badge_key VARCHAR(50) NOT NULL,
            badge_name VARCHAR(100) NOT NULL,
            badge_description VARCHAR(255),
            badge_icon VARCHAR(50),
            badge_type VARCHAR(20) NOT NULL,
            points INT DEFAULT 0,
            requirements TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY badge_key (badge_key)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create user badges table
     */
    private static function create_user_badges_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_user_badges';

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            badge_id INT NOT NULL,
            earned_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_badge (user_id, badge_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Create employer contacts table for reverse applications
     */
    private static function create_employer_contacts_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_employer_contacts';

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            availability_id BIGINT UNSIGNED NOT NULL,
            employer_id BIGINT UNSIGNED NOT NULL,
            candidate_id BIGINT UNSIGNED NOT NULL,
            job_id BIGINT UNSIGNED NULL,
            message TEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY availability_id (availability_id),
            KEY employer_id (employer_id),
            KEY candidate_id (candidate_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Seed rejection reasons
     */
    private static function seed_rejection_reasons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_rejection_reasons';

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $reasons = [
            ['experience_mismatch', 'El nivel de experiencia no coincide con los requisitos del puesto', 1],
            ['skills_mismatch', 'Las habilidades no coinciden con el perfil buscado', 2],
            ['salary_expectations', 'Las expectativas salariales no se alinean con el presupuesto', 3],
            ['location_issue', 'La ubicación no es compatible con los requisitos del puesto', 4],
            ['position_filled', 'El puesto ya ha sido cubierto', 5],
            ['better_fit_found', 'Se ha seleccionado a otro candidato que se ajusta mejor al perfil', 6],
            ['process_cancelled', 'El proceso de selección ha sido cancelado', 7],
            ['other', 'Otra razón', 8],
        ];

        foreach ($reasons as $reason) {
            $wpdb->insert(
                $table_name,
                [
                    'reason_key' => $reason[0],
                    'reason_text' => $reason[1],
                    'display_order' => $reason[2],
                ],
                ['%s', '%s', '%d']
            );
        }
    }

    /**
     * Seed badges
     */
    private static function seed_badges() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inspjob_badges';

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        // Job Seeker badges
        $job_seeker_badges = [
            [
                'badge_key' => 'profile_complete',
                'badge_name' => 'Perfil Completo',
                'badge_description' => 'Completaste el 100% de tu perfil',
                'badge_icon' => 'user-check',
                'badge_type' => 'job_seeker',
                'points' => 50,
                'requirements' => json_encode(['profile_completion' => 100]),
            ],
            [
                'badge_key' => 'first_application',
                'badge_name' => 'Primera Aplicacion',
                'badge_description' => 'Enviaste tu primera aplicacion',
                'badge_icon' => 'send',
                'badge_type' => 'job_seeker',
                'points' => 10,
                'requirements' => json_encode(['applications_count' => 1]),
            ],
            [
                'badge_key' => 'active_seeker',
                'badge_name' => 'Buscador Activo',
                'badge_description' => 'Has enviado 10 o mas aplicaciones',
                'badge_icon' => 'search',
                'badge_type' => 'job_seeker',
                'points' => 30,
                'requirements' => json_encode(['applications_count' => 10]),
            ],
            [
                'badge_key' => 'skilled',
                'badge_name' => 'Habilidoso',
                'badge_description' => 'Tienes 5 o mas habilidades en tu perfil',
                'badge_icon' => 'star',
                'badge_type' => 'job_seeker',
                'points' => 20,
                'requirements' => json_encode(['skills_count' => 5]),
            ],
            [
                'badge_key' => 'hired',
                'badge_name' => 'Contratado',
                'badge_description' => 'Has sido contratado a traves de la plataforma',
                'badge_icon' => 'award',
                'badge_type' => 'job_seeker',
                'points' => 100,
                'requirements' => json_encode(['hired_count' => 1]),
            ],
        ];

        // Employer badges
        $employer_badges = [
            [
                'badge_key' => 'responsive_employer',
                'badge_name' => 'Empleador Responsivo',
                'badge_description' => 'Tasa de respuesta del 80% o mas',
                'badge_icon' => 'message-circle',
                'badge_type' => 'employer',
                'points' => 50,
                'requirements' => json_encode(['response_rate' => 80]),
            ],
            [
                'badge_key' => 'feedback_champion',
                'badge_name' => 'Campeon del Feedback',
                'badge_description' => 'Proporcionas feedback en el 90% de los rechazos',
                'badge_icon' => 'thumbs-up',
                'badge_type' => 'employer',
                'points' => 75,
                'requirements' => json_encode(['feedback_rate' => 90]),
            ],
            [
                'badge_key' => 'fast_responder',
                'badge_name' => 'Respuesta Rapida',
                'badge_description' => 'Tiempo promedio de respuesta menor a 48 horas',
                'badge_icon' => 'zap',
                'badge_type' => 'employer',
                'points' => 60,
                'requirements' => json_encode(['avg_response_hours' => 48]),
            ],
            [
                'badge_key' => 'top_employer',
                'badge_name' => 'Top Empleador',
                'badge_description' => 'Score de empleador de 90 o mas',
                'badge_icon' => 'trophy',
                'badge_type' => 'employer',
                'points' => 100,
                'requirements' => json_encode(['employer_score' => 90]),
            ],
        ];

        foreach (array_merge($job_seeker_badges, $employer_badges) as $badge) {
            $wpdb->insert($table_name, $badge);
        }
    }

    /**
     * Drop all custom tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'inspjob_applications',
            $wpdb->prefix . 'inspjob_application_history',
            $wpdb->prefix . 'inspjob_rejection_reasons',
            $wpdb->prefix . 'inspjob_salary_benchmarks',
            $wpdb->prefix . 'inspjob_badges',
            $wpdb->prefix . 'inspjob_user_badges',
            $wpdb->prefix . 'inspjob_employer_contacts',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option(self::DB_VERSION_OPTION);
    }

    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'inspjob_' . $table;
    }
}

// Initialize
InspJob_Database_Migration::init();

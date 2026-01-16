<?php
/**
 * Job Seeker Dashboard Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Get application stats if the class exists
$stats = [
    'total' => 0,
    'pending' => 0,
    'viewed' => 0,
    'interviewing' => 0,
    'hired' => 0,
    'rejected' => 0,
];

if (class_exists('InspJob_Application_Tracker')) {
    $stats = InspJob_Application_Tracker::get_user_stats($user_id);
}

// Get user badges if the class exists
$user_badges = [];
if (class_exists('InspJob_Gamification')) {
    $user_badges = InspJob_Gamification::get_user_badges($user_id);
}

// Get saved jobs
$saved_jobs = get_user_meta($user_id, '_saved_jobs', true) ?: [];
?>

<div class="inspjob-dashboard">
    <!-- Dashboard Header -->
    <div class="inspjob-dashboard-header">
        <div class="dashboard-welcome">
            <div class="welcome-avatar">
                <?php echo get_avatar($user_id, 80); ?>
            </div>
            <div class="welcome-info">
                <h2>Hola, <?php echo esc_html($profile['display_name']); ?></h2>
                <p class="welcome-headline"><?php echo esc_html($profile['headline'] ?: 'Completa tu perfil para mejorar tu visibilidad'); ?></p>
            </div>
        </div>
        <div class="dashboard-quick-actions">
            <a href="<?php echo esc_url(home_url('/mi-perfil/')); ?>" class="inspjob-btn inspjob-btn-outline">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Editar perfil
            </a>
            <a href="<?php echo esc_url(home_url('/empleos/')); ?>" class="inspjob-btn inspjob-btn-primary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Buscar empleos
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="inspjob-stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($stats['total']); ?></span>
                <span class="stat-label">Aplicaciones enviadas</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-info">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($stats['viewed']); ?></span>
                <span class="stat-label">Vistas por empleadores</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-warning">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($stats['interviewing']); ?></span>
                <span class="stat-label">En proceso de entrevista</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-success">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($stats['hired']); ?></span>
                <span class="stat-label">Contrataciones</span>
            </div>
        </div>
    </div>

    <!-- Profile Completion -->
    <?php if ($profile['profile_completion'] < 100): ?>
        <div class="inspjob-profile-alert">
            <div class="alert-content">
                <div class="alert-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="alert-text">
                    <h4>Tu perfil esta al <?php echo esc_html($profile['profile_completion']); ?>%</h4>
                    <p>Completa tu perfil para mejorar tu visibilidad y recibir mejores recomendaciones de empleo.</p>
                </div>
            </div>
            <a href="<?php echo esc_url(home_url('/mi-perfil/')); ?>" class="inspjob-btn inspjob-btn-outline">
                Completar perfil
            </a>
        </div>
    <?php endif; ?>

    <div class="inspjob-dashboard-grid">
        <!-- Recent Applications -->
        <div class="inspjob-dashboard-section">
            <div class="section-header">
                <h3>Mis aplicaciones recientes</h3>
                <a href="<?php echo esc_url(home_url('/mis-aplicaciones/')); ?>" class="section-link">Ver todas</a>
            </div>
            <div class="section-content">
                <?php
                if (class_exists('InspJob_Application_Tracker')):
                    $applications = InspJob_Application_Tracker::get_user_applications($user_id, ['limit' => 5]);
                    if (!empty($applications)):
                ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $app):
                            $job = get_post($app->job_id);
                            if (!$job) continue;
                        ?>
                            <div class="application-item">
                                <div class="application-job">
                                    <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="job-title">
                                        <?php echo esc_html($job->post_title); ?>
                                    </a>
                                    <span class="job-company"><?php echo esc_html(get_post_meta($job->ID, '_company_name', true)); ?></span>
                                </div>
                                <div class="application-status status-<?php echo esc_attr($app->status); ?>">
                                    <?php echo esc_html(InspJob_Application_Tracker::get_status_label($app->status)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <p>No has enviado ninguna aplicacion aun</p>
                        <a href="<?php echo esc_url(home_url('/empleos/')); ?>" class="inspjob-btn inspjob-btn-primary">Explorar empleos</a>
                    </div>
                <?php
                    endif;
                else:
                ?>
                    <div class="empty-state">
                        <p>Las aplicaciones estaran disponibles pronto</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Saved Jobs -->
        <div class="inspjob-dashboard-section">
            <div class="section-header">
                <h3>Empleos guardados</h3>
                <a href="<?php echo esc_url(home_url('/empleos-guardados/')); ?>" class="section-link">Ver todos</a>
            </div>
            <div class="section-content">
                <?php if (!empty($saved_jobs)): ?>
                    <div class="saved-jobs-list">
                        <?php
                        $saved_jobs_posts = get_posts([
                            'post_type' => 'job_listing',
                            'post__in' => array_slice($saved_jobs, 0, 5),
                            'posts_per_page' => 5,
                            'post_status' => 'publish',
                        ]);
                        foreach ($saved_jobs_posts as $job):
                        ?>
                            <div class="saved-job-item">
                                <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="job-title">
                                    <?php echo esc_html($job->post_title); ?>
                                </a>
                                <span class="job-company"><?php echo esc_html(get_post_meta($job->ID, '_company_name', true)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <p>No has guardado ningun empleo aun</p>
                        <a href="<?php echo esc_url(home_url('/empleos/')); ?>" class="inspjob-btn inspjob-btn-outline">Explorar empleos</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Badges Section -->
    <?php if (!empty($user_badges)): ?>
        <div class="inspjob-dashboard-section inspjob-badges-section">
            <div class="section-header">
                <h3>Mis insignias</h3>
                <div class="level-badge level-<?php echo esc_attr($profile['level']); ?>">
                    Nivel: <?php echo esc_html(InspJob_Job_Seeker::get_level_label($profile['level'])); ?>
                </div>
            </div>
            <div class="section-content">
                <div class="badges-grid">
                    <?php foreach ($user_badges as $badge): ?>
                        <div class="badge-item">
                            <div class="badge-icon">
                                <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="7"></circle>
                                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                </svg>
                            </div>
                            <div class="badge-info">
                                <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
                                <span class="badge-description"><?php echo esc_html($badge->badge_description); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recommended Jobs -->
    <div class="inspjob-dashboard-section inspjob-recommended-section">
        <div class="section-header">
            <h3>Empleos recomendados para ti</h3>
            <a href="<?php echo esc_url(home_url('/empleos/')); ?>" class="section-link">Ver todos</a>
        </div>
        <div class="section-content">
            <?php
            if (class_exists('InspJob_Matching_Engine')):
                $recommended = InspJob_Matching_Engine::get_recommended_jobs($user_id, 3);
                if (!empty($recommended)):
            ?>
                <div class="recommended-jobs-grid">
                    <?php foreach ($recommended as $job_data):
                        $job = $job_data['job'];
                        $match_score = $job_data['match_score'];
                    ?>
                        <div class="recommended-job-card">
                            <div class="match-score">
                                <span class="match-value"><?php echo esc_html($match_score); ?>%</span>
                                <span class="match-label">Match</span>
                            </div>
                            <h4 class="job-title">
                                <a href="<?php echo esc_url(get_permalink($job->ID)); ?>">
                                    <?php echo esc_html($job->post_title); ?>
                                </a>
                            </h4>
                            <p class="job-company"><?php echo esc_html(get_post_meta($job->ID, '_company_name', true)); ?></p>
                            <p class="job-location"><?php echo esc_html(get_post_meta($job->ID, '_job_location', true)); ?></p>
                            <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="inspjob-btn inspjob-btn-outline inspjob-btn-sm">Ver empleo</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php
                else:
            ?>
                <div class="empty-state">
                    <p>Completa tu perfil para recibir recomendaciones personalizadas</p>
                </div>
            <?php
                endif;
            else:
            ?>
                <div class="empty-state">
                    <p>Las recomendaciones estaran disponibles pronto</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
/**
 * Single Job Listing - Diseño de 2 columnas
 * Meta información en card a la derecha
 *
 * Color: #164FC9
 * Fuente: Montserrat
 */

global $post;

// Obtener campos personalizados
$salary_min = get_post_meta($post->ID, '_job_salary_min', true);
$salary_max = get_post_meta($post->ID, '_job_salary_max', true);
$experience = get_post_meta($post->ID, '_job_experience', true);
$benefits = get_post_meta($post->ID, '_job_benefits', true);
$remote_work = get_post_meta($post->ID, '_remote_work', true);
$urgency = get_post_meta($post->ID, '_job_urgency', true);

// Formatear salario
$salary_display = '';
if ($salary_min || $salary_max) {
    if ($salary_min && $salary_max) {
        $salary_display = '€' . number_format($salary_min, 0, ',', '.') . ' - €' . number_format($salary_max, 0, ',', '.');
    } elseif ($salary_min) {
        $salary_display = 'Desde €' . number_format($salary_min, 0, ',', '.');
    } elseif ($salary_max) {
        $salary_display = 'Hasta €' . number_format($salary_max, 0, ',', '.');
    }
}

// Labels de experiencia
$experience_labels = array(
    'entry' => 'Sin experiencia',
    'junior' => '1-2 años',
    'mid' => '3-5 años',
    'senior' => '5-10 años',
    'expert' => '10+ años'
);

// Obtener el logo de la empresa o las iniciales
$company_name = get_the_company_name();
$company_initials = '';
if ($company_name) {
    $words = explode(' ', $company_name);
    foreach ($words as $word) {
        if (!empty($word)) {
            $company_initials .= strtoupper($word[0]);
        }
    }
    $company_initials = substr($company_initials, 0, 2);
}
?>

<div class="single-job-listing">

    <?php if (get_option('job_manager_hide_expired_content', 1) && 'expired' === $post->post_status) : ?>

        <div class="job-expired-notice">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>Esta oferta de trabajo ha expirado</p>
        </div>

    <?php else : ?>

        <!-- Header del trabajo -->
        <div class="single-job-header">
            <div class="container">
                <?php if ($urgency || is_position_featured()) : ?>
                    <div class="job-badges-top">
                        <?php if ($urgency) : ?>
                            <span class="badge-urgent">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                </svg>
                                Urgente
                            </span>
                        <?php endif; ?>
                        <?php if (is_position_featured()) : ?>
                            <span class="badge-featured">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                Destacado
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h1 class="job-title"><?php wpjm_the_job_title(); ?></h1>

                <div class="job-header-meta">
                    <span class="company-name">
                        <?php the_company_name(); ?>
                    </span>
                    <span class="separator">•</span>
                    <span class="job-location">
                        <?php the_job_location(); ?>
                    </span>
                    <span class="separator">•</span>
                    <span class="job-date">
                        Publicado <?php the_job_publish_date(); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Contenido principal - 2 columnas -->
        <div class="single-job-content">
            <div class="container">
                <div class="content-grid">

                    <!-- Columna izquierda - Contenido principal -->
                    <div class="content-main">

                        <!-- Descripción del puesto -->
                        <div class="content-section">
                            <h2>Descripción del puesto</h2>
                            <div class="job-description">
                                <?php wpjm_the_job_description(); ?>
                            </div>
                        </div>

                        <!-- Beneficios -->
                        <?php if ($benefits) : ?>
                            <div class="content-section">
                                <h2>Beneficios</h2>
                                <div class="benefits-content">
                                    <?php echo wpautop(esc_html($benefits)); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Requisitos/Categorías -->
                        <?php
                        $categories = get_the_terms($post->ID, 'job_listing_category');
                        if ($categories && !is_wp_error($categories)) : ?>
                            <div class="content-section">
                                <h2>Habilidades requeridas</h2>
                                <div class="skills-list">
                                    <?php foreach($categories as $category) : ?>
                                        <span class="skill-tag-large"><?php echo esc_html($category->name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Columna derecha - Meta información en card -->
                    <div class="content-sidebar">

                        <!-- Card de información del trabajo -->
                        <div class="job-meta-card">

                            <!-- Logo y empresa -->
                            <div class="company-section">
                                <div class="company-logo-large">
                                    <?php if (get_the_company_logo()) : ?>
                                        <?php the_company_logo('thumbnail'); ?>
                                    <?php else : ?>
                                        <div class="logo-placeholder"><?php echo esc_html($company_initials); ?></div>
                                    <?php endif; ?>
                                </div>

                                <h3><?php the_company_name(); ?></h3>

                                <?php if (get_the_company_tagline()) : ?>
                                    <p class="company-tagline"><?php the_company_tagline(); ?></p>
                                <?php endif; ?>

                                <?php if (get_the_company_website()) : ?>
                                    <a href="<?php echo esc_url(get_the_company_website()); ?>" class="company-website" target="_blank">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                        </svg>
                                        Visitar sitio web
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="meta-divider"></div>

                            <!-- Información del trabajo -->
                            <div class="job-info-list">

                                <!-- Tipo de trabajo -->
                                <?php if (get_the_job_types()) : ?>
                                    <div class="info-item">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                        <div>
                                            <span class="info-label">Tipo de empleo</span>
                                            <?php $types = wpjm_get_the_job_types(); ?>
                                            <span class="info-value">
                                                <?php foreach($types as $type) : ?>
                                                    <?php echo esc_html($type->name); ?>
                                                <?php endforeach; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Ubicación -->
                                <?php if (get_the_job_location()) : ?>
                                    <div class="info-item">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <div>
                                            <span class="info-label">Ubicación</span>
                                            <span class="info-value"><?php the_job_location(); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Modalidad -->
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <?php if ($remote_work) : ?>
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        <?php else : ?>
                                            <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                                        <?php endif; ?>
                                    </svg>
                                    <div>
                                        <span class="info-label">Modalidad</span>
                                        <span class="info-value"><?php echo $remote_work ? 'Trabajo Remoto' : 'Presencial'; ?></span>
                                    </div>
                                </div>

                                <!-- Salario -->
                                <?php if ($salary_display) : ?>
                                    <div class="info-item">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <div>
                                            <span class="info-label">Salario</span>
                                            <span class="info-value highlight"><?php echo esc_html($salary_display); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Experiencia -->
                                <?php if ($experience && isset($experience_labels[$experience])) : ?>
                                    <div class="info-item">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                        </svg>
                                        <div>
                                            <span class="info-label">Experiencia</span>
                                            <span class="info-value"><?php echo esc_html($experience_labels[$experience]); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Fecha de publicación -->
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <div>
                                        <span class="info-label">Publicado</span>
                                        <span class="info-value"><?php the_job_publish_date(); ?></span>
                                    </div>
                                </div>

                            </div>

                            <div class="meta-divider"></div>

                            <!-- Botón de aplicar -->
                            <?php if (candidates_can_apply()) : ?>
                                <div class="apply-section">
                                    <?php get_job_manager_template('job-application.php'); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Trabajos relacionados -->
                        <?php
                        $related_args = array(
                            'post_type' => 'job_listing',
                            'posts_per_page' => 3,
                            'post__not_in' => array($post->ID),
                            'meta_query' => array(
                                array(
                                    'key' => '_job_location',
                                    'value' => get_post_meta($post->ID, '_job_location', true),
                                    'compare' => 'LIKE'
                                )
                            )
                        );
                        $related_jobs = new WP_Query($related_args);

                        if ($related_jobs->have_posts()) : ?>
                            <div class="related-jobs-card">
                                <h3>Trabajos similares</h3>
                                <div class="related-jobs-list">
                                    <?php while ($related_jobs->have_posts()) : $related_jobs->the_post(); ?>
                                        <a href="<?php the_job_permalink(); ?>" class="related-job">
                                            <h4><?php wpjm_the_job_title(); ?></h4>
                                            <p class="related-company"><?php the_company_name(); ?></p>
                                            <p class="related-location"><?php the_job_location(false); ?></p>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <?php wp_reset_postdata(); ?>
                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>

</div>
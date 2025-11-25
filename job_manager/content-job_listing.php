<?php
/**
 * Template personalizado para mostrar cada trabajo en el listado
 * Estructura de card específica con el diseño solicitado
 * Color principal: #164FC9
 * Fuente: Montserrat
 *
 * Este template sobrescribe el original de WP Job Manager
 * Original: /wp-content/plugins/wp-job-manager/templates/content-job_listing.php
 */

global $post;

// Obtener campos personalizados
$salary_min = get_post_meta($post->ID, '_job_salary_min', true);
$salary_max = get_post_meta($post->ID, '_job_salary_max', true);
$experience = get_post_meta($post->ID, '_job_experience', true);
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
    // Limitar a 2 caracteres
    $company_initials = substr($company_initials, 0, 2);
}

// Obtener tipos de trabajo
$job_types = wpjm_get_the_job_types();
$job_type_names = array();
if (!empty($job_types)) {
    foreach ($job_types as $job_type) {
        $job_type_names[] = $job_type->name;
    }
}

// Obtener categorías/skills
$categories = get_the_terms($post->ID, 'job_listing_category');
?>

<li <?php job_listing_class('job-listing-card'); ?> data-longitude="<?php echo esc_attr($post->geolocation_lat); ?>" data-latitude="<?php echo esc_attr($post->geolocation_long); ?>">

    <div class="job-card">

        <!-- Header con logo y badges -->
        <div class="job-header">
            <div class="company-logo">
                <?php if (get_the_company_logo()) : ?>
                    <?php the_company_logo('thumbnail'); ?>
                <?php else : ?>
                    <div class="logo-placeholder"><?php echo esc_html($company_initials); ?></div>
                <?php endif; ?>
            </div>

            <div class="job-meta">
                <?php if (!empty($job_type_names)) : ?>
                    <span class="job-type"><?php echo esc_html($job_type_names[0]); ?></span>
                <?php endif; ?>

                <?php if ($remote_work) : ?>
                    <span class="job-remote">Remoto</span>
                <?php elseif (get_the_job_location()) : ?>
                    <span class="job-onsite">Presencial</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información principal del trabajo -->
        <h3 class="job-title">
            <a href="<?php the_job_permalink(); ?>">
                <?php wpjm_the_job_title(); ?>
            </a>
        </h3>

        <p class="company-name">
            <?php the_company_name(); ?>
        </p>

        <?php if (get_the_job_location()) : ?>
            <p class="job-location">
                <?php the_job_location(false); ?>
            </p>
        <?php endif; ?>

        <!-- Descripción breve -->
        <p class="job-description">
            <?php echo wp_trim_words(wpjm_get_the_job_description(), 20, '...'); ?>
        </p>

        <!-- Tags de habilidades -->
        <?php if ($categories && !is_wp_error($categories) && count($categories) > 0) : ?>
            <div class="job-tags">
                <?php
                $shown = 0;
                foreach($categories as $category) :
                    if ($shown < 3) : // Mostrar máximo 3 tags
                ?>
                    <span class="skill-tag"><?php echo esc_html($category->name); ?></span>
                <?php
                    $shown++;
                    endif;
                endforeach;

                if (count($categories) > 3) : ?>
                    <span class="skill-tag">+<?php echo (count($categories) - 3); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer con salario y botón -->
        <div class="job-footer">
            <?php if ($salary_display) : ?>
                <span class="salary"><?php echo esc_html($salary_display); ?></span>
            <?php else : ?>
                <span class="salary">Salario competitivo</span>
            <?php endif; ?>

            <a href="<?php the_job_permalink(); ?>" class="btn-apply">
                Aplicar
            </a>
        </div>

        <?php if ($urgency) : ?>
            <div class="urgent-indicator"></div>
        <?php endif; ?>

        <?php if (is_position_featured()) : ?>
            <div class="featured-indicator"></div>
        <?php endif; ?>

    </div>

</li>
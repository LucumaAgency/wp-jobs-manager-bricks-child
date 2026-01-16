<?php
/**
 * Single Candidate Availability Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$post_id = get_the_ID();
$data = InspJob_Reverse_Application::get_availability_data($post_id);
$profile = $data['profile'] ?? [];
$user = get_userdata($data['user_id']);
?>

<div class="inspjob-single-candidate">
    <div class="candidate-container">
        <!-- Main Content -->
        <div class="candidate-main">
            <!-- Header -->
            <div class="candidate-header-card">
                <div class="header-top">
                    <div class="candidate-avatar-large">
                        <?php echo get_avatar($data['user_id'], 120); ?>
                        <?php if (!empty($profile['level'])): ?>
                            <span class="level-badge level-<?php echo esc_attr($profile['level']); ?>">
                                <?php echo esc_html(InspJob_Job_Seeker::get_level_label($profile['level'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="candidate-info">
                        <h1><?php echo esc_html($user->display_name); ?></h1>
                        <p class="candidate-title"><?php echo esc_html($data['title']); ?></p>

                        <div class="candidate-meta-list">
                            <?php if (!empty($data['location'])): ?>
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?php echo esc_html($data['location']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($data['experience'])): ?>
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                    <?php echo esc_html(InspJob_Job_Seeker::EXPERIENCE_LEVELS[$data['experience']] ?? ''); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($data['remote_pref'])): ?>
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                        <line x1="8" y1="21" x2="16" y2="21"></line>
                                        <line x1="12" y1="17" x2="12" y2="21"></line>
                                    </svg>
                                    <?php echo esc_html(InspJob_Job_Seeker::REMOTE_PREFERENCES[$data['remote_pref']] ?? ''); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (is_user_logged_in() && get_current_user_id() !== $data['user_id']): ?>
                    <div class="header-actions">
                        <button type="button" id="contact-candidate-btn" class="inspjob-btn inspjob-btn-primary">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Contactar candidato
                        </button>
                    </div>
                <?php elseif (!is_user_logged_in()): ?>
                    <div class="header-actions">
                        <a href="<?php echo esc_url(home_url('/iniciar-sesion/')); ?>" class="inspjob-btn inspjob-btn-primary">
                            Iniciar sesion para contactar
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- About -->
            <?php if (!empty($data['description']) || !empty($profile['bio'])): ?>
                <div class="candidate-section">
                    <h3>Sobre mi</h3>
                    <div class="section-content">
                        <?php echo wpautop(esc_html($data['description'] ?: $profile['bio'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Skills -->
            <?php if (!empty($profile['skills'])): ?>
                <div class="candidate-section">
                    <h3>Habilidades</h3>
                    <div class="skills-grid">
                        <?php foreach ($profile['skills'] as $skill): ?>
                            <span class="skill-tag"><?php echo esc_html($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Job Types Interested -->
            <?php if (!empty($data['job_types'])): ?>
                <div class="candidate-section">
                    <h3>Tipos de trabajo de interes</h3>
                    <div class="tags-grid">
                        <?php
                        foreach ($data['job_types'] as $type_slug):
                            $type = get_term_by('slug', $type_slug, 'job_listing_type');
                            if ($type):
                        ?>
                            <span class="tag"><?php echo esc_html($type->name); ?></span>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Categories Interested -->
            <?php if (!empty($data['categories'])): ?>
                <div class="candidate-section">
                    <h3>Categorias de interes</h3>
                    <div class="tags-grid">
                        <?php
                        foreach ($data['categories'] as $cat_id):
                            $cat = get_term($cat_id, 'job_listing_category');
                            if ($cat && !is_wp_error($cat)):
                        ?>
                            <span class="tag"><?php echo esc_html($cat->name); ?></span>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Badges -->
            <?php
            if (class_exists('InspJob_Gamification')):
                $badges = InspJob_Gamification::get_user_badges($data['user_id']);
                if (!empty($badges)):
            ?>
                <div class="candidate-section">
                    <h3>Insignias</h3>
                    <div class="badges-grid">
                        <?php foreach ($badges as $badge): ?>
                            <div class="badge-item">
                                <div class="badge-icon">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="7"></circle>
                                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                    </svg>
                                </div>
                                <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php
                endif;
            endif;
            ?>
        </div>

        <!-- Sidebar -->
        <div class="candidate-sidebar">
            <!-- Expectations Card -->
            <div class="sidebar-card">
                <h4>Expectativas</h4>

                <div class="info-list">
                    <?php if (!empty($data['salary_min']) || !empty($data['salary_max'])): ?>
                        <div class="info-item salary-highlight">
                            <span class="info-label">Expectativa salarial</span>
                            <span class="info-value">
                                <?php
                                $salary_min = $data['salary_min'];
                                $salary_max = $data['salary_max'];
                                if ($salary_min && $salary_max) {
                                    echo 'S/ ' . number_format($salary_min, 0, ',', '.') . ' - ' . number_format($salary_max, 0, ',', '.');
                                } elseif ($salary_min) {
                                    echo 'Desde S/ ' . number_format($salary_min, 0, ',', '.');
                                } elseif ($salary_max) {
                                    echo 'Hasta S/ ' . number_format($salary_max, 0, ',', '.');
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($data['start_date'])): ?>
                        <div class="info-item">
                            <span class="info-label">Disponibilidad</span>
                            <span class="info-value">
                                <?php echo esc_html(InspJob_Job_Seeker::AVAILABILITY_OPTIONS[$data['start_date']] ?? $data['start_date']); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <span class="info-label">Modalidad</span>
                        <span class="info-value">
                            <?php echo esc_html(InspJob_Job_Seeker::REMOTE_PREFERENCES[$data['remote_pref']] ?? 'Flexible'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="sidebar-card">
                <h4>Estadisticas</h4>

                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($data['views'] ?: 0); ?></span>
                        <span class="stat-label">Vistas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($data['contacts'] ?: 0); ?></span>
                        <span class="stat-label">Contactos</span>
                    </div>
                </div>
            </div>

            <!-- Links -->
            <?php if (!empty($profile['linkedin']) || !empty($profile['portfolio'])): ?>
                <div class="sidebar-card">
                    <h4>Enlaces</h4>
                    <div class="links-list">
                        <?php if (!empty($profile['linkedin'])): ?>
                            <a href="<?php echo esc_url($profile['linkedin']); ?>" target="_blank" class="link-item">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect x="2" y="9" width="4" height="12"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($profile['portfolio'])): ?>
                            <a href="<?php echo esc_url($profile['portfolio']); ?>" target="_blank" class="link-item">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                                Portfolio
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Published Date -->
            <div class="sidebar-meta">
                <span>Publicado: <?php echo esc_html(date_i18n('d M Y', strtotime($data['created']))); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div id="contact-modal" class="inspjob-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Contactar a <?php echo esc_html($user->display_name); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="contact-form">
            <?php wp_nonce_field('inspjob_contact_candidate', 'nonce'); ?>
            <input type="hidden" name="availability_id" value="<?php echo esc_attr($post_id); ?>">

            <div class="modal-body">
                <p>El candidato recibira una notificacion con tu informacion de contacto.</p>

                <div class="inspjob-form-group">
                    <label for="contact-message">Mensaje</label>
                    <textarea id="contact-message" name="message" rows="4"
                        placeholder="Cuentale al candidato sobre la oportunidad que tienes para el/ella..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="inspjob-btn inspjob-btn-outline modal-cancel">Cancelar</button>
                <button type="submit" class="inspjob-btn inspjob-btn-primary">Enviar contacto</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactBtn = document.getElementById('contact-candidate-btn');
    const contactModal = document.getElementById('contact-modal');
    const contactForm = document.getElementById('contact-form');

    // Track view
    fetch(inspjob_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'inspjob_track_view',
            availability_id: <?php echo esc_js($post_id); ?>
        })
    });

    if (contactBtn) {
        contactBtn.addEventListener('click', function() {
            contactModal.style.display = 'flex';
        });
    }

    // Close modal
    document.querySelectorAll('.modal-close, .modal-cancel, .modal-overlay').forEach(el => {
        el.addEventListener('click', function() {
            contactModal.style.display = 'none';
        });
    });

    // Submit contact
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'inspjob_contact_candidate');

            fetch(inspjob_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    contactModal.style.display = 'none';
                } else {
                    alert(data.data.message);
                }
            });
        });
    }
});
</script>

<?php get_footer(); ?>

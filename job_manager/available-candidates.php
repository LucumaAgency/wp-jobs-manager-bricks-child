<?php
/**
 * Available Candidates List Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$candidates = $results['candidates'];
$total = $results['total'];
$max_pages = $results['max_pages'];
$current_page = $results['current_page'];
?>

<div class="inspjob-candidates-container">
    <!-- Filters -->
    <div class="candidates-filters">
        <form method="get" class="filters-form">
            <div class="filter-group">
                <label for="experience">Experiencia</label>
                <select name="experience" id="experience">
                    <option value="">Todos los niveles</option>
                    <?php foreach (InspJob_Job_Seeker::EXPERIENCE_LEVELS as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                            <?php selected($filters['experience'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="remote">Modalidad</label>
                <select name="remote" id="remote">
                    <option value="">Todas</option>
                    <?php foreach (InspJob_Job_Seeker::REMOTE_PREFERENCES as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                            <?php selected($filters['remote_pref'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="location">Ubicacion</label>
                <input type="text" name="location" id="location"
                    value="<?php echo esc_attr($filters['location']); ?>"
                    placeholder="Ciudad o region">
            </div>

            <div class="filter-group">
                <label for="salary_max">Presupuesto maximo (S/)</label>
                <input type="number" name="salary_max" id="salary_max"
                    value="<?php echo esc_attr($filters['salary_max']); ?>"
                    placeholder="Ej: 5000" min="0" step="100">
            </div>

            <button type="submit" class="inspjob-btn inspjob-btn-primary">Filtrar</button>
            <a href="<?php echo esc_url(remove_query_arg(['experience', 'remote', 'location', 'salary_max'])); ?>"
               class="inspjob-btn inspjob-btn-text">Limpiar</a>
        </form>
    </div>

    <!-- Results Count -->
    <div class="candidates-count">
        <?php if ($total > 0): ?>
            <p><?php echo esc_html($total); ?> candidato<?php echo $total > 1 ? 's' : ''; ?> disponible<?php echo $total > 1 ? 's' : ''; ?></p>
        <?php endif; ?>
    </div>

    <!-- Candidates Grid -->
    <?php if (empty($candidates)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>No se encontraron candidatos</h3>
            <p>Intenta ajustar los filtros de busqueda</p>
        </div>
    <?php else: ?>
        <div class="candidates-grid">
            <?php foreach ($candidates as $candidate):
                $profile = $candidate['profile'] ?? [];
                $user = get_userdata($candidate['user_id']);
            ?>
                <div class="candidate-card" data-id="<?php echo esc_attr($candidate['id']); ?>">
                    <div class="candidate-header">
                        <div class="candidate-avatar">
                            <?php echo get_avatar($candidate['user_id'], 60); ?>
                        </div>
                        <div class="candidate-info">
                            <h4 class="candidate-name"><?php echo esc_html($user->display_name); ?></h4>
                            <p class="candidate-title"><?php echo esc_html($candidate['title']); ?></p>
                        </div>
                    </div>

                    <?php if (!empty($profile['level'])): ?>
                        <div class="candidate-level level-<?php echo esc_attr($profile['level']); ?>">
                            <?php echo esc_html(InspJob_Job_Seeker::get_level_label($profile['level'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="candidate-meta">
                        <?php if (!empty($candidate['location'])): ?>
                            <span class="meta-item">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo esc_html($candidate['location']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($candidate['experience'])): ?>
                            <span class="meta-item">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                                <?php echo esc_html(InspJob_Job_Seeker::EXPERIENCE_LEVELS[$candidate['experience']] ?? ''); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($candidate['remote_pref'])): ?>
                            <span class="meta-item">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <?php if ($candidate['remote_pref'] === 'yes'): ?>
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                        <line x1="8" y1="21" x2="16" y2="21"></line>
                                        <line x1="12" y1="17" x2="12" y2="21"></line>
                                    <?php else: ?>
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <?php endif; ?>
                                </svg>
                                <?php echo esc_html(InspJob_Job_Seeker::REMOTE_PREFERENCES[$candidate['remote_pref']] ?? ''); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($profile['skills']) && is_array($profile['skills'])): ?>
                        <div class="candidate-skills">
                            <?php
                            $display_skills = array_slice($profile['skills'], 0, 4);
                            foreach ($display_skills as $skill):
                            ?>
                                <span class="skill-tag"><?php echo esc_html($skill); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($profile['skills']) > 4): ?>
                                <span class="skill-more">+<?php echo count($profile['skills']) - 4; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($candidate['salary_min']) || !empty($candidate['salary_max'])): ?>
                        <div class="candidate-salary">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <?php
                            $salary_min = $candidate['salary_min'];
                            $salary_max = $candidate['salary_max'];
                            if ($salary_min && $salary_max) {
                                echo 'S/ ' . number_format($salary_min, 0, ',', '.') . ' - ' . number_format($salary_max, 0, ',', '.');
                            } elseif ($salary_min) {
                                echo 'Desde S/ ' . number_format($salary_min, 0, ',', '.');
                            } elseif ($salary_max) {
                                echo 'Hasta S/ ' . number_format($salary_max, 0, ',', '.');
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="candidate-actions">
                        <a href="<?php echo esc_url(get_permalink($candidate['id'])); ?>"
                           class="inspjob-btn inspjob-btn-outline inspjob-btn-sm">
                            Ver perfil
                        </a>
                        <?php if (is_user_logged_in()): ?>
                            <button type="button" class="inspjob-btn inspjob-btn-primary inspjob-btn-sm contact-btn"
                                data-id="<?php echo esc_attr($candidate['id']); ?>"
                                data-name="<?php echo esc_attr($user->display_name); ?>">
                                Contactar
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(home_url('/iniciar-sesion/')); ?>"
                               class="inspjob-btn inspjob-btn-primary inspjob-btn-sm">
                                Iniciar sesion
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($max_pages > 1): ?>
            <div class="inspjob-pagination">
                <?php
                echo paginate_links([
                    'total'     => $max_pages,
                    'current'   => $current_page,
                    'prev_text' => '&laquo; Anterior',
                    'next_text' => 'Siguiente &raquo;',
                ]);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Contact Modal -->
<div id="contact-modal" class="inspjob-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Contactar a <span id="contact-name"></span></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="contact-form">
            <?php wp_nonce_field('inspjob_contact_candidate', 'nonce'); ?>
            <input type="hidden" name="availability_id" id="contact-availability-id">

            <div class="modal-body">
                <div class="inspjob-form-group">
                    <label for="contact-message">Mensaje (opcional)</label>
                    <textarea id="contact-message" name="message" rows="4"
                        placeholder="Cuentale al candidato sobre la oportunidad que tienes..."></textarea>
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
    const contactModal = document.getElementById('contact-modal');
    const contactForm = document.getElementById('contact-form');

    // Track views
    document.querySelectorAll('.candidate-card').forEach(card => {
        const id = card.dataset.id;
        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'inspjob_track_view',
                availability_id: id
            })
        });
    });

    // Open contact modal
    document.querySelectorAll('.contact-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('contact-availability-id').value = this.dataset.id;
            document.getElementById('contact-name').textContent = this.dataset.name;
            contactModal.style.display = 'flex';
        });
    });

    // Close modal
    document.querySelectorAll('.modal-close, .modal-cancel, .modal-overlay').forEach(el => {
        el.addEventListener('click', function() {
            contactModal.style.display = 'none';
        });
    });

    // Submit contact
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
                contactForm.reset();
            } else {
                alert(data.data.message);
            }
        });
    });
});
</script>

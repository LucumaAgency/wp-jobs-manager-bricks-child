<?php
/**
 * Employer Applications Management Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$employer_id = get_current_user_id();
$current_job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 0;
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Get employer's jobs
$employer_jobs = get_posts([
    'post_type'      => 'job_listing',
    'author'         => $employer_id,
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'expired'],
]);

// Get rejection reasons for the modal
$rejection_reasons = InspJob_Application_Tracker::get_rejection_reasons();
?>

<div class="inspjob-employer-applications">
    <!-- Filters -->
    <div class="applications-filters">
        <div class="filter-group">
            <label for="job-filter">Empleo:</label>
            <select id="job-filter" onchange="updateFilters()">
                <option value="">Todos los empleos</option>
                <?php foreach ($employer_jobs as $job): ?>
                    <option value="<?php echo esc_attr($job->ID); ?>" <?php selected($current_job_id, $job->ID); ?>>
                        <?php echo esc_html($job->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="status-filter">Estado:</label>
            <select id="status-filter" onchange="updateFilters()">
                <option value="">Todos</option>
                <?php foreach (InspJob_Application_Tracker::STATUSES as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>No hay aplicaciones <?php echo $current_status || $current_job_id ? 'con estos filtros' : 'aun'; ?></h3>
            <p>Las aplicaciones apareceran aqui cuando los candidatos apliquen a tus empleos</p>
        </div>
    <?php else: ?>
        <div class="applications-grid">
            <?php foreach ($applications as $application):
                $job = get_post($application->job_id);
                if (!$job) continue;

                $applicant = get_userdata($application->applicant_id);
                if (!$applicant) continue;

                // Get applicant profile
                $profile = [];
                if (class_exists('InspJob_Job_Seeker')) {
                    $profile = InspJob_Job_Seeker::get_profile($application->applicant_id);
                }
            ?>
                <div class="applicant-card" data-application-id="<?php echo esc_attr($application->id); ?>">
                    <div class="applicant-header">
                        <div class="applicant-avatar">
                            <?php echo get_avatar($application->applicant_id, 60); ?>
                            <?php if ($application->match_score >= 80): ?>
                                <span class="high-match-badge" title="Alta coincidencia">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="applicant-info">
                            <h4 class="applicant-name"><?php echo esc_html($applicant->display_name); ?></h4>
                            <?php if (!empty($profile['headline'])): ?>
                                <p class="applicant-headline"><?php echo esc_html($profile['headline']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($profile['location'])): ?>
                                <p class="applicant-location">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?php echo esc_html($profile['location']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="match-score match-<?php echo $application->match_score >= 80 ? 'high' : ($application->match_score >= 50 ? 'medium' : 'low'); ?>">
                            <span class="match-value"><?php echo esc_html($application->match_score); ?>%</span>
                            <span class="match-label">Match</span>
                        </div>
                    </div>

                    <?php if (!$current_job_id && $job): ?>
                        <div class="applied-to-job">
                            <span class="label">Aplicado a:</span>
                            <a href="<?php echo esc_url(get_permalink($job->ID)); ?>"><?php echo esc_html($job->post_title); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['skills']) && is_array($profile['skills'])): ?>
                        <div class="applicant-skills">
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

                    <?php if (!empty($profile['experience_level'])): ?>
                        <div class="applicant-experience">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            <?php echo esc_html(InspJob_Job_Seeker::EXPERIENCE_LEVELS[$profile['experience_level']] ?? ''); ?>
                        </div>
                    <?php endif; ?>

                    <div class="applicant-meta">
                        <span class="applied-date">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html(human_time_diff(strtotime($application->created_at), current_time('timestamp'))); ?> atras
                        </span>
                        <span class="application-status status-<?php echo esc_attr($application->status); ?>">
                            <?php echo esc_html(InspJob_Application_Tracker::get_status_label($application->status)); ?>
                        </span>
                    </div>

                    <div class="applicant-actions">
                        <button type="button" class="inspjob-btn inspjob-btn-outline inspjob-btn-sm view-details-btn"
                            data-application-id="<?php echo esc_attr($application->id); ?>">
                            Ver detalles
                        </button>

                        <?php if (!empty($profile['resume_url']) || !empty($application->resume_url)): ?>
                            <a href="<?php echo esc_url($application->resume_url ?: $profile['resume_url']); ?>"
                               target="_blank" class="inspjob-btn inspjob-btn-outline inspjob-btn-sm">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                CV
                            </a>
                        <?php endif; ?>

                        <?php if (!in_array($application->status, ['hired', 'rejected', 'withdrawn'])): ?>
                            <div class="status-dropdown">
                                <button type="button" class="inspjob-btn inspjob-btn-primary inspjob-btn-sm dropdown-toggle">
                                    Cambiar estado
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <div class="dropdown-menu">
                                    <?php
                                    $valid_transitions = InspJob_Application_Tracker::VALID_TRANSITIONS[$application->status] ?? [];
                                    foreach ($valid_transitions as $status):
                                        if ($status === 'withdrawn') continue;
                                    ?>
                                        <button type="button" class="dropdown-item change-status-btn"
                                            data-application-id="<?php echo esc_attr($application->id); ?>"
                                            data-status="<?php echo esc_attr($status); ?>">
                                            <?php echo esc_html(InspJob_Application_Tracker::get_status_label($status)); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Rejection Reason Modal -->
<div id="rejection-modal" class="inspjob-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Motivo de rechazo</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="rejection-form">
            <input type="hidden" name="application_id" id="rejection-application-id">
            <?php wp_nonce_field('inspjob_manage_applications', 'nonce'); ?>

            <div class="modal-body">
                <p>Selecciona el motivo del rechazo. El candidato vera esta informacion.</p>
                <div class="rejection-reasons">
                    <?php foreach ($rejection_reasons as $reason): ?>
                        <label class="rejection-option">
                            <input type="radio" name="rejection_reason_id" value="<?php echo esc_attr($reason->id); ?>" required>
                            <span><?php echo esc_html($reason->reason_text); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="inspjob-form-group">
                    <label for="rejection-note">Nota adicional (opcional, no visible para el candidato)</label>
                    <textarea id="rejection-note" name="note" rows="3" placeholder="Notas internas..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="inspjob-btn inspjob-btn-outline modal-cancel">Cancelar</button>
                <button type="submit" class="inspjob-btn inspjob-btn-danger">Confirmar rechazo</button>
            </div>
        </form>
    </div>
</div>

<!-- Application Details Modal -->
<div id="details-modal" class="inspjob-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Detalles de la aplicacion</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="details-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectionModal = document.getElementById('rejection-modal');
    const detailsModal = document.getElementById('details-modal');

    // Update filters
    window.updateFilters = function() {
        const jobId = document.getElementById('job-filter').value;
        const status = document.getElementById('status-filter').value;
        let url = window.location.pathname;
        const params = new URLSearchParams();

        if (jobId) params.set('job_id', jobId);
        if (status) params.set('status', status);

        if (params.toString()) {
            url += '?' + params.toString();
        }
        window.location.href = url;
    };

    // Toggle dropdown
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.status-dropdown');
            dropdown.classList.toggle('open');
        });
    });

    document.addEventListener('click', function() {
        document.querySelectorAll('.status-dropdown.open').forEach(d => d.classList.remove('open'));
    });

    // Change status
    document.querySelectorAll('.change-status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const applicationId = this.dataset.applicationId;
            const status = this.dataset.status;

            if (status === 'rejected') {
                // Show rejection modal
                document.getElementById('rejection-application-id').value = applicationId;
                rejectionModal.style.display = 'flex';
                return;
            }

            updateApplicationStatus(applicationId, status);
        });
    });

    // Submit rejection
    document.getElementById('rejection-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'inspjob_update_application_status');
        formData.append('status', 'rejected');

        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.data.message);
            }
        });
    });

    // View details
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const applicationId = this.dataset.applicationId;
            const content = document.getElementById('details-content');
            content.innerHTML = '<div class="loading">Cargando...</div>';
            detailsModal.style.display = 'flex';

            fetch(inspjob_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'inspjob_get_application_details',
                    nonce: '<?php echo wp_create_nonce('inspjob_manage_applications'); ?>',
                    application_id: applicationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderApplicationDetails(data.data, content);
                } else {
                    content.innerHTML = '<p class="error">' + data.data.message + '</p>';
                }
            });
        });
    });

    function renderApplicationDetails(data, container) {
        const app = data.application;
        const applicant = data.applicant;
        const profile = data.profile || {};
        const history = data.history || [];

        let html = `
            <div class="details-grid">
                <div class="details-main">
                    <div class="applicant-profile">
                        <img src="${applicant.avatar}" alt="${applicant.name}" class="profile-avatar">
                        <div class="profile-info">
                            <h4>${applicant.name}</h4>
                            <p>${profile.headline || ''}</p>
                            <a href="mailto:${applicant.email}">${applicant.email}</a>
                        </div>
                    </div>

                    ${app.cover_letter ? `
                        <div class="detail-section">
                            <h5>Carta de presentacion</h5>
                            <p>${app.cover_letter}</p>
                        </div>
                    ` : ''}

                    ${profile.bio ? `
                        <div class="detail-section">
                            <h5>Sobre el candidato</h5>
                            <p>${profile.bio}</p>
                        </div>
                    ` : ''}

                    ${profile.skills && profile.skills.length ? `
                        <div class="detail-section">
                            <h5>Habilidades</h5>
                            <div class="skills-list">
                                ${profile.skills.map(s => `<span class="skill-tag">${s}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="details-sidebar">
                    <div class="detail-section">
                        <h5>Informacion</h5>
                        <ul class="info-list">
                            <li><strong>Match:</strong> ${app.match_score}%</li>
                            ${profile.experience_level ? `<li><strong>Experiencia:</strong> ${profile.experience_level}</li>` : ''}
                            ${profile.location ? `<li><strong>Ubicacion:</strong> ${profile.location}</li>` : ''}
                            ${profile.availability ? `<li><strong>Disponibilidad:</strong> ${profile.availability}</li>` : ''}
                        </ul>
                    </div>

                    <div class="detail-section">
                        <h5>Historial</h5>
                        <div class="timeline-mini">
                            ${history.map(h => `
                                <div class="timeline-item">
                                    <span class="status">${h.to_status}</span>
                                    <span class="date">${new Date(h.created_at).toLocaleDateString('es-PE')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    function updateApplicationStatus(applicationId, status) {
        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'inspjob_update_application_status',
                nonce: '<?php echo wp_create_nonce('inspjob_manage_applications'); ?>',
                application_id: applicationId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.data.message);
            }
        });
    }

    // Close modals
    document.querySelectorAll('.modal-close, .modal-cancel, .modal-overlay').forEach(el => {
        el.addEventListener('click', function() {
            rejectionModal.style.display = 'none';
            detailsModal.style.display = 'none';
        });
    });
});
</script>

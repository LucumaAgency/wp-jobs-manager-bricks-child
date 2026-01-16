<?php
/**
 * My Applications Template (Job Seeker View)
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = InspJob_Application_Tracker::get_user_stats(get_current_user_id());
$current_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
?>

<div class="inspjob-my-applications">
    <!-- Stats Bar -->
    <div class="applications-stats-bar">
        <a href="<?php echo esc_url(remove_query_arg('status')); ?>" class="stat-item <?php echo empty($current_filter) ? 'active' : ''; ?>">
            <span class="stat-count"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label">Todas</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'pending')); ?>" class="stat-item <?php echo $current_filter === 'pending' ? 'active' : ''; ?>">
            <span class="stat-count"><?php echo esc_html($stats['pending']); ?></span>
            <span class="stat-label">Pendientes</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'viewed')); ?>" class="stat-item <?php echo $current_filter === 'viewed' ? 'active' : ''; ?>">
            <span class="stat-count"><?php echo esc_html($stats['viewed']); ?></span>
            <span class="stat-label">Vistas</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'interviewing')); ?>" class="stat-item <?php echo $current_filter === 'interviewing' ? 'active' : ''; ?>">
            <span class="stat-count"><?php echo esc_html($stats['interviewing']); ?></span>
            <span class="stat-label">En entrevista</span>
        </a>
    </div>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <h3>No tienes aplicaciones <?php echo $current_filter ? 'con este estado' : 'aun'; ?></h3>
            <p>Explora empleos y envia tu primera aplicacion</p>
            <a href="<?php echo esc_url(home_url('/empleos/')); ?>" class="inspjob-btn inspjob-btn-primary">Buscar empleos</a>
        </div>
    <?php else: ?>
        <div class="applications-list">
            <?php foreach ($applications as $application):
                $job = get_post($application->job_id);
                if (!$job) continue;

                $company_name = get_post_meta($job->ID, '_company_name', true);
                $company_logo = get_post_meta($job->ID, '_company_logo', true);
                $job_location = get_post_meta($job->ID, '_job_location', true);

                // Get history for timeline
                $history = InspJob_Application_Tracker::get_history($application->id);

                // Get rejection reason if rejected
                $rejection_reason = null;
                if ($application->status === 'rejected' && $application->rejection_reason_id) {
                    $rejection_reason = InspJob_Application_Tracker::get_rejection_reason($application->rejection_reason_id);
                }
            ?>
                <div class="application-card" data-application-id="<?php echo esc_attr($application->id); ?>">
                    <div class="application-header">
                        <div class="company-logo">
                            <?php if ($company_logo): ?>
                                <img src="<?php echo esc_url($company_logo); ?>" alt="<?php echo esc_attr($company_name); ?>">
                            <?php else: ?>
                                <span class="logo-placeholder"><?php echo esc_html(substr($company_name, 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="application-info">
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
                        </div>
                        <div class="application-status status-<?php echo esc_attr($application->status); ?>">
                            <?php echo esc_html(InspJob_Application_Tracker::get_status_label($application->status)); ?>
                        </div>
                    </div>

                    <?php if ($application->match_score > 0): ?>
                        <div class="match-score-bar">
                            <div class="match-label">Match:</div>
                            <div class="match-bar">
                                <div class="match-fill" style="width: <?php echo esc_attr($application->match_score); ?>%"></div>
                            </div>
                            <div class="match-value"><?php echo esc_html($application->match_score); ?>%</div>
                        </div>
                    <?php endif; ?>

                    <div class="application-timeline">
                        <?php foreach ($history as $event): ?>
                            <div class="timeline-item status-<?php echo esc_attr($event->to_status); ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <span class="timeline-status"><?php echo esc_html(InspJob_Application_Tracker::get_status_label($event->to_status)); ?></span>
                                    <span class="timeline-date"><?php echo esc_html(date_i18n('d M Y, H:i', strtotime($event->created_at))); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($rejection_reason): ?>
                        <div class="rejection-feedback">
                            <h5>Motivo de rechazo:</h5>
                            <p><?php echo esc_html($rejection_reason->reason_text); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="application-footer">
                        <span class="application-date">
                            Aplicaste el <?php echo esc_html(date_i18n('d \d\e F, Y', strtotime($application->created_at))); ?>
                        </span>

                        <?php if (in_array($application->status, ['pending', 'viewed', 'shortlisted'])): ?>
                            <button type="button" class="inspjob-btn inspjob-btn-text inspjob-btn-danger withdraw-btn"
                                data-application-id="<?php echo esc_attr($application->id); ?>">
                                Retirar aplicacion
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Withdraw application
    document.querySelectorAll('.withdraw-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Estas seguro de que deseas retirar esta aplicacion?')) {
                return;
            }

            const applicationId = this.dataset.applicationId;
            const card = this.closest('.application-card');

            fetch(inspjob_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'inspjob_withdraw_application',
                    nonce: inspjob_ajax.my_applications_nonce,
                    application_id: applicationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    card.querySelector('.application-status').className = 'application-status status-withdrawn';
                    card.querySelector('.application-status').textContent = 'Retirado';
                    this.remove();
                } else {
                    alert(data.data.message);
                }
            });
        });
    });
});
</script>

<?php
/**
 * Application Timeline Template
 *
 * @package InspJobPortal
 *
 * Variables available:
 * $data - Timeline data from InspJob_Application_Timeline::get_timeline_data()
 */

if (!defined('ABSPATH')) {
    exit;
}

$application = $data['application'];
$job = $data['job'];
$events = $data['events'];
$current_status = $data['current_status'];
$sla = $data['sla'];
$remaining_days = $data['remaining_days'];
$employer_metrics = $data['employer_metrics'];
$next_step = $data['next_step'];

// Get rejection reason if applicable
$rejection_reason = null;
if ($current_status === 'rejected' && $application->rejection_reason_id) {
    $rejection_reason = InspJob_Application_Tracker::get_rejection_reason($application->rejection_reason_id);
}

// Determine if SLA is overdue
$is_overdue = $remaining_days !== null && $remaining_days <= 0 &&
              !in_array($current_status, ['hired', 'rejected', 'withdrawn']) &&
              empty($application->responded_at);
?>

<div class="inspjob-application-timeline">
    <!-- Header -->
    <div class="timeline-header">
        <div class="job-info">
            <h3><?php echo esc_html($job->post_title); ?></h3>
            <p class="company-name"><?php echo esc_html(get_post_meta($job->ID, '_company_name', true)); ?></p>
        </div>
        <div class="current-status status-<?php echo esc_attr($current_status); ?>">
            <?php echo esc_html(InspJob_Application_Tracker::get_status_label($current_status)); ?>
        </div>
    </div>

    <!-- SLA Info -->
    <?php if ($sla && !in_array($current_status, ['hired', 'rejected', 'withdrawn'])): ?>
        <div class="sla-info <?php echo $is_overdue ? 'overdue' : ''; ?>">
            <div class="sla-icon">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="sla-content">
                <?php if ($is_overdue): ?>
                    <span class="sla-status overdue">El plazo de respuesta ha vencido</span>
                    <span class="sla-detail">El empleador se comprometio a responder en <?php echo esc_html($sla['label']); ?></span>
                <?php elseif ($remaining_days !== null): ?>
                    <span class="sla-status">
                        <?php if ($remaining_days <= 1): ?>
                            Respuesta esperada hoy
                        <?php else: ?>
                            <?php echo esc_html($remaining_days); ?> dias restantes para respuesta
                        <?php endif; ?>
                    </span>
                    <span class="sla-detail">Compromiso del empleador: <?php echo esc_html($sla['label']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Employer Metrics -->
    <?php if ($employer_metrics && $employer_metrics['total_applications'] >= 3): ?>
        <div class="employer-metrics-mini">
            <?php if ($employer_metrics['badge']): ?>
                <span class="employer-badge badge-<?php echo esc_attr($employer_metrics['badge']); ?>">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                        <circle cx="12" cy="8" r="7"></circle>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                    </svg>
                    <?php echo esc_html(InspJob_Employer_Score::get_badge_label($employer_metrics['badge'])); ?>
                </span>
            <?php endif; ?>
            <span class="response-rate">
                Tasa de respuesta: <?php echo esc_html($employer_metrics['response_rate']); ?>%
            </span>
        </div>
    <?php endif; ?>

    <!-- Timeline Events -->
    <div class="timeline-events">
        <?php foreach ($events as $index => $event): ?>
            <div class="timeline-event status-<?php echo esc_attr($event['status']); ?>">
                <div class="event-marker" style="background-color: <?php echo esc_attr($event['color']); ?>;">
                    <?php echo self::get_status_icon($event['status']); ?>
                </div>
                <?php if ($index < count($events) - 1): ?>
                    <div class="event-line"></div>
                <?php endif; ?>
                <div class="event-content">
                    <span class="event-label"><?php echo esc_html($event['label']); ?></span>
                    <span class="event-date"><?php echo esc_html($event['date_human']); ?></span>
                    <?php if (!empty($event['note'])): ?>
                        <p class="event-note"><?php echo esc_html($event['note']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Projected Next Step -->
        <?php if ($next_step): ?>
            <div class="timeline-event projected">
                <div class="event-marker projected">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </div>
                <div class="event-content">
                    <span class="event-label projected-label"><?php echo esc_html($next_step['label']); ?></span>
                    <span class="event-estimate"><?php echo esc_html($next_step['estimate']); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rejection Feedback -->
    <?php if ($rejection_reason): ?>
        <div class="rejection-feedback-box">
            <h4>Motivo del rechazo</h4>
            <p class="feedback-reason"><?php echo esc_html($rejection_reason->reason_text); ?></p>
            <?php if ($application->candidate_feedback): ?>
                <div class="candidate-feedback">
                    <h5>Tu feedback (opcional):</h5>
                    <p><?php echo esc_html($application->candidate_feedback); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Match Score -->
    <?php if ($application->match_score > 0): ?>
        <div class="match-score-section">
            <div class="match-label">Match con tu perfil</div>
            <div class="match-bar">
                <div class="match-fill" style="width: <?php echo esc_attr($application->match_score); ?>%"></div>
                <span class="match-value"><?php echo esc_html($application->match_score); ?>%</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="timeline-actions">
        <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="inspjob-btn inspjob-btn-outline">
            Ver empleo
        </a>
        <?php if (in_array($current_status, ['pending', 'viewed', 'shortlisted'])): ?>
            <button type="button" class="inspjob-btn inspjob-btn-text inspjob-btn-danger"
                onclick="if(confirm('Estas seguro?')) withdrawApplication(<?php echo esc_attr($application->id); ?>)">
                Retirar aplicacion
            </button>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to get SVG icons
function get_status_icon($status) {
    $icons = [
        'pending' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
        'viewed' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        'shortlisted' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
        'interviewing' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
        'offered' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>',
        'hired' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        'rejected' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        'withdrawn' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>',
    ];

    return $icons[$status] ?? '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
}
?>

<script>
function withdrawApplication(applicationId) {
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
            location.reload();
        } else {
            alert(data.data.message);
        }
    });
}
</script>

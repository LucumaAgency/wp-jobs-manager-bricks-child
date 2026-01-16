<?php
/**
 * Application Timeline
 * Provides visual timeline for application status tracking
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Application_Timeline {

    /**
     * Status icons (Feather icons)
     */
    const STATUS_ICONS = [
        'pending'      => 'send',
        'viewed'       => 'eye',
        'shortlisted'  => 'star',
        'interviewing' => 'calendar',
        'offered'      => 'gift',
        'hired'        => 'check-circle',
        'rejected'     => 'x-circle',
        'withdrawn'    => 'arrow-left-circle',
    ];

    /**
     * Status colors
     */
    const STATUS_COLORS = [
        'pending'      => '#6B7280',
        'viewed'       => '#3B82F6',
        'shortlisted'  => '#F59E0B',
        'interviewing' => '#8B5CF6',
        'offered'      => '#10B981',
        'hired'        => '#059669',
        'rejected'     => '#EF4444',
        'withdrawn'    => '#9CA3AF',
    ];

    /**
     * Initialize
     */
    public static function init() {
        add_shortcode('inspjob_application_timeline', [__CLASS__, 'render_timeline']);
    }

    /**
     * Get timeline data for an application
     */
    public static function get_timeline_data($application_id) {
        if (!class_exists('InspJob_Application_Tracker')) {
            return null;
        }

        $application = InspJob_Application_Tracker::get_application($application_id);
        if (!$application) {
            return null;
        }

        $history = InspJob_Application_Tracker::get_history($application_id);
        $job = get_post($application->job_id);

        // Get SLA info
        $sla = null;
        $sla_deadline = null;
        $remaining_days = null;

        if (class_exists('InspJob_SLA_Commitment')) {
            $sla = InspJob_SLA_Commitment::get_job_sla($application->job_id);
            $sla_deadline = InspJob_SLA_Commitment::get_sla_deadline($application);
            $remaining_days = InspJob_SLA_Commitment::get_remaining_days($application);
        }

        // Get employer metrics
        $employer_metrics = null;
        if (class_exists('InspJob_Employer_Score')) {
            $employer_metrics = InspJob_Employer_Score::get_metrics($application->employer_id);
        }

        // Build timeline events
        $events = [];
        foreach ($history as $item) {
            $events[] = [
                'status'     => $item->to_status,
                'label'      => InspJob_Application_Tracker::get_status_label($item->to_status),
                'date'       => $item->created_at,
                'date_human' => self::format_date($item->created_at),
                'note'       => $item->note,
                'icon'       => self::STATUS_ICONS[$item->to_status] ?? 'circle',
                'color'      => self::STATUS_COLORS[$item->to_status] ?? '#6B7280',
            ];
        }

        // Add projected next step if applicable
        $next_step = self::get_projected_next_step($application, $sla, $employer_metrics);

        return [
            'application'      => $application,
            'job'              => $job,
            'events'           => $events,
            'current_status'   => $application->status,
            'sla'              => $sla,
            'sla_deadline'     => $sla_deadline,
            'remaining_days'   => $remaining_days,
            'employer_metrics' => $employer_metrics,
            'next_step'        => $next_step,
        ];
    }

    /**
     * Get projected next step
     */
    private static function get_projected_next_step($application, $sla, $employer_metrics) {
        $status = $application->status;

        // Final states have no next step
        if (in_array($status, ['hired', 'rejected', 'withdrawn'])) {
            return null;
        }

        $next_step = [
            'label'    => '',
            'estimate' => '',
        ];

        switch ($status) {
            case 'pending':
                $next_step['label'] = 'Revision de tu aplicacion';
                if ($employer_metrics && $employer_metrics['avg_response_hours'] > 0) {
                    $days = ceil($employer_metrics['avg_response_hours'] / 24);
                    $next_step['estimate'] = "Tiempo promedio: {$days} dias";
                } elseif ($sla) {
                    $next_step['estimate'] = "Compromiso: {$sla['label']}";
                }
                break;

            case 'viewed':
                $next_step['label'] = 'Evaluacion en curso';
                $next_step['estimate'] = 'El empleador esta revisando tu perfil';
                break;

            case 'shortlisted':
                $next_step['label'] = 'Posible contacto para entrevista';
                $next_step['estimate'] = 'Has sido preseleccionado';
                break;

            case 'interviewing':
                $next_step['label'] = 'Decision final';
                $next_step['estimate'] = 'Proceso de entrevista en curso';
                break;

            case 'offered':
                $next_step['label'] = 'Pendiente de tu respuesta';
                $next_step['estimate'] = 'Tienes una oferta de trabajo';
                break;
        }

        return $next_step;
    }

    /**
     * Format date for display
     */
    private static function format_date($date) {
        $timestamp = strtotime($date);
        $now = current_time('timestamp');
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'Hace un momento';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "Hace {$mins} " . ($mins == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Hace {$hours} " . ($hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "Hace {$days} " . ($days == 1 ? 'dia' : 'dias');
        } else {
            return date_i18n('d M Y, H:i', $timestamp);
        }
    }

    /**
     * Render timeline shortcode
     */
    public static function render_timeline($atts) {
        $atts = shortcode_atts([
            'application_id' => 0,
            'compact'        => false,
        ], $atts);

        $application_id = absint($atts['application_id']);

        if (!$application_id) {
            return '<p class="inspjob-notice">ID de aplicacion no valido.</p>';
        }

        // Verify access
        $application = InspJob_Application_Tracker::get_application($application_id);
        if (!$application) {
            return '<p class="inspjob-notice">Aplicacion no encontrada.</p>';
        }

        $user_id = get_current_user_id();
        if ($application->applicant_id != $user_id && $application->employer_id != $user_id) {
            return '<p class="inspjob-notice">No tienes permiso para ver esta aplicacion.</p>';
        }

        $data = self::get_timeline_data($application_id);

        if (!$data) {
            return '<p class="inspjob-notice">No se pudo cargar el timeline.</p>';
        }

        ob_start();
        include get_stylesheet_directory() . '/job_manager/application-timeline.php';
        return ob_get_clean();
    }

    /**
     * Render inline timeline for application cards
     */
    public static function render_inline_timeline($application_id) {
        $data = self::get_timeline_data($application_id);

        if (!$data || empty($data['events'])) {
            return '';
        }

        ob_start();
        ?>
        <div class="inspjob-timeline-inline">
            <?php foreach ($data['events'] as $index => $event): ?>
                <div class="timeline-dot status-<?php echo esc_attr($event['status']); ?>"
                     style="background-color: <?php echo esc_attr($event['color']); ?>;"
                     title="<?php echo esc_attr($event['label'] . ' - ' . $event['date_human']); ?>">
                </div>
                <?php if ($index < count($data['events']) - 1): ?>
                    <div class="timeline-connector"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize
InspJob_Application_Timeline::init();

<?php
/**
 * Email Notifications System
 * Handles all email notifications for the job portal
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

class InspJob_Email_Notifications {

    /**
     * From email address
     */
    private static $from_email;

    /**
     * From name
     */
    private static $from_name;

    /**
     * Initialize the notification system
     */
    public static function init() {
        self::$from_email = get_option('admin_email');
        self::$from_name = get_bloginfo('name');

        // Application notifications
        add_action('inspjob_application_created', [__CLASS__, 'notify_employer_new_application'], 10, 4);
        add_action('inspjob_application_status_changed', [__CLASS__, 'notify_candidate_status_change'], 10, 4);

        // SLA notifications
        add_action('inspjob_sla_reminder', [__CLASS__, 'notify_employer_sla_reminder']);
        add_action('inspjob_sla_violation', [__CLASS__, 'notify_employer_sla_violation']);

        // Ghost position notifications
        add_action('inspjob_ghost_warning_sent', [__CLASS__, 'notify_employer_ghost_warning'], 10, 2);
        add_action('inspjob_ghost_position_closed', [__CLASS__, 'notify_candidate_ghost_closed'], 10, 2);
        add_action('inspjob_job_auto_closed', [__CLASS__, 'notify_employer_job_closed'], 10, 3);

        // Employer block notification
        add_action('inspjob_employer_blocked', [__CLASS__, 'notify_employer_blocked'], 10, 2);

        // Badge notifications
        add_action('inspjob_badge_earned', [__CLASS__, 'notify_user_badge_earned'], 10, 2);

        // Reverse application - contact notification
        add_action('inspjob_candidate_contacted', [__CLASS__, 'notify_candidate_contact'], 10, 3);

        // Email styling filter
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);
    }

    /**
     * Set HTML content type for emails
     */
    public static function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Get email template
     */
    private static function get_template($content, $subject) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background-color: #164FC9; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;'>{$site_name}</h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    {$content}
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                    <p style='margin: 0 0 10px; color: #6b7280; font-size: 14px;'>
                                        Este email fue enviado desde <a href='{$site_url}' style='color: #164FC9; text-decoration: none;'>{$site_name}</a>
                                    </p>
                                    <p style='margin: 0; color: #9ca3af; font-size: 12px;'>
                                        Si tienes preguntas, contactanos en <a href='mailto:" . self::$from_email . "' style='color: #164FC9;'>" . self::$from_email . "</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    /**
     * Send email
     */
    private static function send($to, $subject, $content) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::$from_name . ' <' . self::$from_email . '>',
        ];

        $html = self::get_template($content, $subject);

        return wp_mail($to, $subject, $html, $headers);
    }

    /**
     * Notify employer of new application
     */
    public static function notify_employer_new_application($application_id, $job_id, $applicant_id, $employer_id) {
        $employer = get_userdata($employer_id);
        $applicant = get_userdata($applicant_id);
        $job = get_post($job_id);

        if (!$employer || !$applicant || !$job) {
            return;
        }

        $application = null;
        if (class_exists('InspJob_Application_Tracker')) {
            $application = InspJob_Application_Tracker::get_application($application_id);
        }

        $match_score = $application ? $application->match_score : 0;
        $manage_url = home_url('/gestionar-aplicaciones/?job_id=' . $job_id);

        $content = "
            <h2 style='margin: 0 0 20px; color: #111827; font-size: 20px;'>Nueva aplicacion recibida</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                <strong>{$applicant->display_name}</strong> ha aplicado a tu oferta de empleo:
            </p>
            <div style='background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <h3 style='margin: 0 0 10px; color: #111827; font-size: 18px;'>{$job->post_title}</h3>
                " . ($match_score > 0 ? "<p style='margin: 0; color: #059669; font-size: 14px; font-weight: 600;'>Match: {$match_score}%</p>" : "") . "
            </div>
            <p style='margin: 0 0 30px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Revisa la aplicacion y responde al candidato lo antes posible.
            </p>
            <a href='{$manage_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Ver aplicacion
            </a>
        ";

        self::send($employer->user_email, 'Nueva aplicacion: ' . $job->post_title, $content);
    }

    /**
     * Notify candidate of status change
     */
    public static function notify_candidate_status_change($application_id, $from_status, $to_status, $application) {
        $candidate = get_userdata($application->applicant_id);
        $job = get_post($application->job_id);

        if (!$candidate || !$job) {
            return;
        }

        $status_label = '';
        if (class_exists('InspJob_Application_Tracker')) {
            $status_label = InspJob_Application_Tracker::get_status_label($to_status);
        }

        $company_name = get_post_meta($application->job_id, '_company_name', true);
        $dashboard_url = home_url('/mis-aplicaciones/');

        $status_messages = [
            'viewed'       => 'Tu aplicacion ha sido vista por el empleador.',
            'shortlisted'  => 'Has sido preseleccionado para la siguiente etapa del proceso.',
            'interviewing' => 'Has avanzado a la etapa de entrevistas.',
            'offered'      => 'Has recibido una oferta de trabajo.',
            'hired'        => 'Felicitaciones! Has sido contratado.',
            'rejected'     => 'Tu aplicacion no ha sido seleccionada en esta ocasion.',
        ];

        $message = $status_messages[$to_status] ?? 'Tu aplicacion ha sido actualizada.';

        // Get rejection reason if applicable
        $rejection_text = '';
        if ($to_status === 'rejected' && $application->rejection_reason_id && class_exists('InspJob_Application_Tracker')) {
            $reason = InspJob_Application_Tracker::get_rejection_reason($application->rejection_reason_id);
            if ($reason) {
                $rejection_text = "<p style='margin: 20px 0; padding: 15px; background-color: #fef2f2; border-radius: 6px; color: #991b1b;'>
                    <strong>Motivo:</strong> {$reason->reason_text}
                </p>";
            }
        }

        $status_color = '#164FC9';
        if ($to_status === 'rejected') {
            $status_color = '#DC2626';
        } elseif ($to_status === 'hired') {
            $status_color = '#059669';
        }

        $content = "
            <h2 style='margin: 0 0 20px; color: #111827; font-size: 20px;'>Actualizacion de tu aplicacion</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$candidate->display_name},
            </p>
            <div style='background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <h3 style='margin: 0 0 10px; color: #111827; font-size: 18px;'>{$job->post_title}</h3>
                <p style='margin: 0 0 10px; color: #6b7280; font-size: 14px;'>{$company_name}</p>
                <p style='margin: 0; display: inline-block; background-color: {$status_color}; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-size: 14px; font-weight: 600;'>
                    {$status_label}
                </p>
            </div>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                {$message}
            </p>
            {$rejection_text}
            <a href='{$dashboard_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Ver mis aplicaciones
            </a>
        ";

        self::send($candidate->user_email, 'Actualizacion: ' . $job->post_title, $content);
    }

    /**
     * Notify employer of SLA reminder
     */
    public static function notify_employer_sla_reminder($application) {
        $employer = get_userdata($application->employer_id);
        $job = get_post($application->job_id);

        if (!$employer || !$job) {
            return;
        }

        $hours_remaining = $application->hours_remaining ?? 24;
        $manage_url = home_url('/gestionar-aplicaciones/?job_id=' . $application->job_id);

        $content = "
            <h2 style='margin: 0 0 20px; color: #DC2626; font-size: 20px;'>Recordatorio: Aplicacion pendiente de respuesta</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Tienes una aplicacion pendiente de respuesta para <strong>{$job->post_title}</strong>.
            </p>
            <div style='background-color: #fef2f2; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #991b1b; font-size: 16px;'>
                    <strong>Tiempo restante:</strong> {$hours_remaining} horas
                </p>
            </div>
            <p style='margin: 0 0 30px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Responder a tiempo mejora tu reputacion como empleador.
            </p>
            <a href='{$manage_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Responder ahora
            </a>
        ";

        self::send($employer->user_email, 'Recordatorio: Aplicacion pendiente - ' . $job->post_title, $content);
    }

    /**
     * Notify employer of SLA violation
     */
    public static function notify_employer_sla_violation($application) {
        $employer = get_userdata($application->employer_id);
        $job = get_post($application->job_id);

        if (!$employer || !$job) {
            return;
        }

        $manage_url = home_url('/gestionar-aplicaciones/?job_id=' . $application->job_id);

        $content = "
            <h2 style='margin: 0 0 20px; color: #DC2626; font-size: 20px;'>Plazo de respuesta vencido</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                El plazo de respuesta para una aplicacion a <strong>{$job->post_title}</strong> ha vencido.
            </p>
            <div style='background-color: #fef2f2; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #991b1b; font-size: 14px;'>
                    Esto afecta tu puntuacion como empleador. Responder a los candidatos es importante para mantener una buena reputacion.
                </p>
            </div>
            <a href='{$manage_url}' style='display: inline-block; background-color: #DC2626; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Responder urgentemente
            </a>
        ";

        self::send($employer->user_email, 'URGENTE: Plazo vencido - ' . $job->post_title, $content);
    }

    /**
     * Notify employer of ghost warning
     */
    public static function notify_employer_ghost_warning($job_id, $employer_id) {
        $employer = get_userdata($employer_id);
        $job = get_post($job_id);

        if (!$employer || !$job) {
            return;
        }

        $manage_url = home_url('/gestionar-aplicaciones/?job_id=' . $job_id);

        $content = "
            <h2 style='margin: 0 0 20px; color: #F59E0B; font-size: 20px;'>Advertencia: Empleo en riesgo de cierre automatico</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Tu oferta de empleo <strong>{$job->post_title}</strong> tiene aplicaciones sin responder que exceden significativamente el plazo comprometido.
            </p>
            <div style='background-color: #fffbeb; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #92400e; font-size: 14px;'>
                    <strong>Si no respondes en los proximos 7 dias</strong>, el empleo sera cerrado automaticamente y los candidatos seran notificados.
                </p>
            </div>
            <a href='{$manage_url}' style='display: inline-block; background-color: #F59E0B; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Gestionar aplicaciones
            </a>
        ";

        self::send($employer->user_email, 'Advertencia: ' . $job->post_title . ' sera cerrado', $content);
    }

    /**
     * Notify candidate when ghost position is closed
     */
    public static function notify_candidate_ghost_closed($application_id, $job_id) {
        if (!class_exists('InspJob_Application_Tracker')) {
            return;
        }

        $application = InspJob_Application_Tracker::get_application($application_id);
        if (!$application) {
            return;
        }

        $candidate = get_userdata($application->applicant_id);
        $job = get_post($job_id);

        if (!$candidate || !$job) {
            return;
        }

        $jobs_url = home_url('/empleos/');

        $content = "
            <h2 style='margin: 0 0 20px; color: #111827; font-size: 20px;'>Posicion cerrada</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$candidate->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Lamentamos informarte que la posicion <strong>{$job->post_title}</strong> a la que aplicaste ha sido cerrada debido a la falta de respuesta del empleador.
            </p>
            <p style='margin: 0 0 30px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Te animamos a seguir buscando oportunidades. Hay muchas empresas buscando candidatos como tu.
            </p>
            <a href='{$jobs_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Ver mas empleos
            </a>
        ";

        self::send($candidate->user_email, 'Actualizacion: ' . $job->post_title, $content);
    }

    /**
     * Notify employer when job is auto-closed
     */
    public static function notify_employer_job_closed($job_id, $employer_id, $reason) {
        $employer = get_userdata($employer_id);
        $job = get_post($job_id);

        if (!$employer || !$job) {
            return;
        }

        $content = "
            <h2 style='margin: 0 0 20px; color: #DC2626; font-size: 20px;'>Empleo cerrado automaticamente</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Tu oferta de empleo <strong>{$job->post_title}</strong> ha sido cerrada automaticamente debido a la falta de respuesta a los candidatos.
            </p>
            <div style='background-color: #fef2f2; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #991b1b; font-size: 14px;'>
                    Todos los candidatos pendientes han sido notificados. Tu puntuacion como empleador ha sido afectada.
                </p>
            </div>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Para futuras publicaciones, te recomendamos responder a los candidatos dentro del plazo comprometido.
            </p>
        ";

        self::send($employer->user_email, 'Empleo cerrado: ' . $job->post_title, $content);
    }

    /**
     * Notify employer of account block
     */
    public static function notify_employer_blocked($employer_id, $blocked_until) {
        $employer = get_userdata($employer_id);

        if (!$employer) {
            return;
        }

        $blocked_date = date_i18n('d/m/Y', strtotime($blocked_until));
        $manage_url = home_url('/gestionar-aplicaciones/');

        $content = "
            <h2 style='margin: 0 0 20px; color: #DC2626; font-size: 20px;'>Cuenta temporalmente bloqueada</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$employer->display_name},
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Tu cuenta ha sido temporalmente bloqueada para publicar nuevos empleos debido a una baja tasa de respuesta a los candidatos.
            </p>
            <div style='background-color: #fef2f2; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #991b1b; font-size: 14px;'>
                    <strong>Bloqueado hasta:</strong> {$blocked_date}
                </p>
            </div>
            <p style='margin: 0 0 30px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Para desbloquear tu cuenta mas rapido, responde a las aplicaciones pendientes.
            </p>
            <a href='{$manage_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Gestionar aplicaciones
            </a>
        ";

        self::send($employer->user_email, 'Cuenta bloqueada temporalmente', $content);
    }

    /**
     * Notify user of badge earned
     */
    public static function notify_user_badge_earned($user_id, $badge) {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        $dashboard_url = home_url('/mi-dashboard/');

        $content = "
            <h2 style='margin: 0 0 20px; color: #059669; font-size: 20px;'>Has ganado una insignia!</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Felicitaciones {$user->display_name}!
            </p>
            <div style='background-color: #ecfdf5; border-radius: 8px; padding: 30px; margin: 0 0 20px; text-align: center;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>🏆</div>
                <h3 style='margin: 0 0 10px; color: #059669; font-size: 22px;'>{$badge->badge_name}</h3>
                <p style='margin: 0; color: #065f46; font-size: 14px;'>{$badge->badge_description}</p>
                <p style='margin: 15px 0 0; color: #059669; font-weight: 600;'>+{$badge->points} puntos</p>
            </div>
            <a href='{$dashboard_url}' style='display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;'>
                Ver mis insignias
            </a>
        ";

        self::send($user->user_email, 'Has ganado: ' . $badge->badge_name, $content);
    }

    /**
     * Notify candidate when contacted by employer
     */
    public static function notify_candidate_contact($availability_id, $employer_id, $candidate_id) {
        $candidate = get_userdata($candidate_id);
        $employer = get_userdata($employer_id);
        $availability = get_post($availability_id);

        if (!$candidate || !$employer || !$availability) {
            return;
        }

        // Get employer company info
        $company_name = get_user_meta($employer_id, '_company_name', true) ?: $employer->display_name;

        $manage_url = home_url('/mi-disponibilidad/');

        $content = "
            <h2 style='margin: 0 0 20px; color: #059669; font-size: 20px;'>Un empleador te ha contactado!</h2>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Hola {$candidate->display_name}!
            </p>
            <p style='margin: 0 0 20px; color: #4b5563; font-size: 16px; line-height: 1.6;'>
                <strong>{$company_name}</strong> ha visto tu perfil de disponibilidad y quiere contactarte.
            </p>
            <div style='background-color: #ecfdf5; border-radius: 8px; padding: 20px; margin: 0 0 20px;'>
                <p style='margin: 0; color: #065f46; font-size: 14px;'>
                    Esto significa que tu perfil les ha parecido interesante. Te recomendamos responder lo antes posible.
                </p>
            </div>
            <p style='margin: 0 0 10px; color: #4b5563; font-size: 14px;'>
                <strong>Email del empleador:</strong> {$employer->user_email}
            </p>
            <a href='{$manage_url}' style='display: inline-block; background-color: #164FC9; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin-top: 20px;'>
                Ver mis contactos
            </a>
        ";

        self::send($candidate->user_email, 'Un empleador quiere contactarte', $content);
    }
}

// Initialize
InspJob_Email_Notifications::init();

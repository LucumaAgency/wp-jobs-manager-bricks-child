<?php
/**
 * Application Form Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$job = get_post($job_id);
$resume_url = get_user_meta($user_id, '_job_seeker_resume_url', true);

// Calculate match score if available
$match_score = 0;
if (class_exists('InspJob_Matching_Engine')) {
    $match_score = InspJob_Matching_Engine::calculate_match_score($user_id, $job_id);
}
?>

<div class="inspjob-apply-container">
    <div class="inspjob-apply-header">
        <h3>Aplicar a: <?php echo esc_html($job->post_title); ?></h3>
        <p class="company-name"><?php echo esc_html(get_post_meta($job_id, '_company_name', true)); ?></p>

        <?php if ($match_score > 0): ?>
            <div class="match-score-indicator">
                <span class="match-value"><?php echo esc_html($match_score); ?>%</span>
                <span class="match-label">Match con tu perfil</span>
            </div>
        <?php endif; ?>
    </div>

    <form id="inspjob-apply-form" class="inspjob-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('inspjob_apply', 'nonce'); ?>
        <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">

        <div class="inspjob-form-group">
            <label for="cover_letter">Carta de presentacion</label>
            <textarea id="cover_letter" name="cover_letter" rows="6"
                placeholder="Cuentale al empleador por que eres el candidato ideal para este puesto..."></textarea>
            <span class="inspjob-form-hint">Una buena carta de presentacion aumenta tus posibilidades de ser seleccionado</span>
        </div>

        <div class="inspjob-form-group">
            <label>Curriculum Vitae</label>
            <?php if ($resume_url): ?>
                <div class="current-resume">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>Se usara tu CV guardado: <a href="<?php echo esc_url($resume_url); ?>" target="_blank">Ver CV</a></span>
                </div>
                <div class="upload-new-resume">
                    <label class="inspjob-checkbox-label">
                        <input type="checkbox" id="upload_new_resume" name="upload_new_resume">
                        <span>Subir un CV diferente</span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="resume-upload-field" <?php echo $resume_url ? 'style="display: none;"' : ''; ?>>
                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                <span class="inspjob-form-hint">Formatos: PDF, DOC, DOCX. Maximo 5MB</span>
            </div>
        </div>

        <div class="inspjob-form-actions">
            <button type="submit" class="inspjob-btn inspjob-btn-primary inspjob-btn-block">
                <span class="btn-text">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    Enviar aplicacion
                </span>
                <span class="btn-loading" style="display: none;">
                    <svg class="spinner" viewBox="0 0 24 24" width="20" height="20">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="10">
                            <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                    Enviando...
                </span>
            </button>
        </div>

        <div class="inspjob-form-message" style="display: none;"></div>
    </form>

    <div class="inspjob-apply-tips">
        <h4>Consejos para una buena aplicacion</h4>
        <ul>
            <li>Personaliza tu carta de presentacion para este puesto especifico</li>
            <li>Destaca tus habilidades mas relevantes para el trabajo</li>
            <li>Menciona logros concretos y cuantificables</li>
            <li>Asegurate de que tu CV este actualizado</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('inspjob-apply-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const messageDiv = form.querySelector('.inspjob-form-message');

    // Toggle resume upload field
    const uploadCheckbox = document.getElementById('upload_new_resume');
    const uploadField = document.querySelector('.resume-upload-field');

    if (uploadCheckbox) {
        uploadCheckbox.addEventListener('change', function() {
            uploadField.style.display = this.checked ? 'block' : 'none';
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'inspjob_submit_application');

        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                form.innerHTML = '<div class="inspjob-success-message">' +
                    '<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#10b981" stroke-width="2">' +
                        '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>' +
                        '<polyline points="22 4 12 14.01 9 11.01"></polyline>' +
                    '</svg>' +
                    '<h3>Aplicacion enviada</h3>' +
                    '<p>' + data.data.message + '</p>' +
                    '<a href="' + inspjob_ajax.dashboard_url + '" class="inspjob-btn inspjob-btn-outline">Ver mis aplicaciones</a>' +
                '</div>';
            } else {
                showMessage(data.data.message, 'error');
                btnText.style.display = 'inline-flex';
                btnLoading.style.display = 'none';
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            showMessage('Error de conexion. Por favor intenta de nuevo.', 'error');
            btnText.style.display = 'inline-flex';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
        });
    });

    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'inspjob-form-message inspjob-message-' + type;
        messageDiv.style.display = 'block';
    }
});
</script>

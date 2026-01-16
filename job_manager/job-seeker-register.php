<?php
/**
 * Job Seeker Registration Form Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="inspjob-register-container">
    <div class="inspjob-register-card">
        <div class="inspjob-register-header">
            <h2>Crea tu cuenta de candidato</h2>
            <p>Encuentra el trabajo de tus suenos</p>
        </div>

        <form id="inspjob-register-form" class="inspjob-form" method="post">
            <?php wp_nonce_field('inspjob_register_job_seeker', 'nonce'); ?>

            <div class="inspjob-form-row">
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="first_name">Nombre <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" required placeholder="Tu nombre">
                </div>
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="last_name">Apellido</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Tu apellido">
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required placeholder="tu@email.com">
            </div>

            <div class="inspjob-form-group">
                <label for="password">Contrasena <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="Minimo 8 caracteres">
                <span class="inspjob-form-hint">Minimo 8 caracteres</span>
            </div>

            <div class="inspjob-form-group">
                <label for="password_confirm">Confirmar contrasena <span class="required">*</span></label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contrasena">
            </div>

            <div class="inspjob-form-group inspjob-checkbox-group">
                <label class="inspjob-checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>Acepto los <a href="<?php echo esc_url(home_url('/terminos-y-condiciones/')); ?>" target="_blank">terminos y condiciones</a> y la <a href="<?php echo esc_url(home_url('/politica-de-privacidad/')); ?>" target="_blank">politica de privacidad</a></span>
                </label>
            </div>

            <div class="inspjob-form-group">
                <button type="submit" class="inspjob-btn inspjob-btn-primary inspjob-btn-block">
                    <span class="btn-text">Crear cuenta</span>
                    <span class="btn-loading" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24" width="20" height="20">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="10">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        Creando cuenta...
                    </span>
                </button>
            </div>

            <div class="inspjob-form-message" style="display: none;"></div>
        </form>

        <div class="inspjob-register-footer">
            <p>Ya tienes una cuenta? <a href="<?php echo esc_url(home_url('/iniciar-sesion/')); ?>">Inicia sesion</a></p>
            <p class="inspjob-employer-link">Eres empleador? <a href="<?php echo esc_url(home_url('/registrar-empresa/')); ?>">Registra tu empresa</a></p>
        </div>
    </div>

    <div class="inspjob-register-benefits">
        <h3>Ventajas de registrarte</h3>
        <ul>
            <li>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Accede a miles de ofertas de empleo</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Recibe alertas personalizadas de nuevos trabajos</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Guarda tus empleos favoritos</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Seguimiento de tus aplicaciones</span>
            </li>
            <li>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Ve tu porcentaje de match con cada oferta</span>
            </li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('inspjob-register-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const messageDiv = form.querySelector('.inspjob-form-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate passwords match
        const password = form.querySelector('#password').value;
        const passwordConfirm = form.querySelector('#password_confirm').value;

        if (password !== passwordConfirm) {
            showMessage('Las contrasenas no coinciden', 'error');
            return;
        }

        // Show loading state
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'inspjob_register_job_seeker');

        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.data.redirect;
                }, 1000);
            } else {
                showMessage(data.data.message, 'error');
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            showMessage('Error de conexion. Por favor intenta de nuevo.', 'error');
            btnText.style.display = 'inline';
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

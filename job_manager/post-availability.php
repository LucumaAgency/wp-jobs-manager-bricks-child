<?php
/**
 * Post Availability Form Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = get_terms(['taxonomy' => 'job_listing_category', 'hide_empty' => false]);
$job_types = get_terms(['taxonomy' => 'job_listing_type', 'hide_empty' => false]);
?>

<div class="inspjob-availability-form-container">
    <div class="form-header">
        <h2><?php echo $data ? 'Editar mi disponibilidad' : 'Publicar mi disponibilidad'; ?></h2>
        <p>Completa tu perfil de disponibilidad para que los empleadores puedan encontrarte y contactarte directamente.</p>
    </div>

    <form id="inspjob-availability-form" class="inspjob-form" method="post">
        <?php wp_nonce_field('inspjob_availability', 'nonce'); ?>

        <div class="inspjob-form-section">
            <h3 class="section-title">Informacion principal</h3>

            <div class="inspjob-form-group">
                <label for="title">Titulo profesional <span class="required">*</span></label>
                <input type="text" id="title" name="title" required
                    value="<?php echo esc_attr($data['title'] ?? ''); ?>"
                    placeholder="Ej: Desarrollador Full Stack con 5 anos de experiencia">
            </div>

            <div class="inspjob-form-group">
                <label for="description">Sobre mi y lo que busco</label>
                <textarea id="description" name="description" rows="5"
                    placeholder="Describe tu experiencia, habilidades clave y el tipo de oportunidad que buscas..."><?php echo esc_textarea($data['description'] ?? ''); ?></textarea>
            </div>

            <div class="inspjob-form-group">
                <label for="location">Ubicacion</label>
                <input type="text" id="location" name="location"
                    value="<?php echo esc_attr($data['location'] ?? ''); ?>"
                    placeholder="Ej: Lima, Peru">
            </div>
        </div>

        <div class="inspjob-form-section">
            <h3 class="section-title">Preferencias laborales</h3>

            <div class="inspjob-form-group">
                <label for="experience">Nivel de experiencia</label>
                <select id="experience" name="experience">
                    <option value="">Selecciona tu nivel</option>
                    <?php foreach (InspJob_Job_Seeker::EXPERIENCE_LEVELS as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                            <?php selected($data['experience'] ?? '', $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="inspjob-form-group">
                <label>Tipo de trabajo</label>
                <div class="inspjob-checkbox-grid">
                    <?php
                    $selected_types = $data['job_types'] ?? [];
                    foreach ($job_types as $type):
                    ?>
                        <label class="inspjob-checkbox-label">
                            <input type="checkbox" name="job_types[]" value="<?php echo esc_attr($type->slug); ?>"
                                <?php checked(in_array($type->slug, $selected_types)); ?>>
                            <span><?php echo esc_html($type->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="inspjob-form-group">
                <label>Categorias de interes</label>
                <div class="inspjob-checkbox-grid">
                    <?php
                    $selected_cats = $data['categories'] ?? [];
                    foreach ($categories as $category):
                    ?>
                        <label class="inspjob-checkbox-label">
                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr($category->term_id); ?>"
                                <?php checked(in_array($category->term_id, $selected_cats)); ?>>
                            <span><?php echo esc_html($category->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="remote_pref">Modalidad de trabajo</label>
                <select id="remote_pref" name="remote_pref">
                    <option value="">Selecciona tu preferencia</option>
                    <?php foreach (InspJob_Job_Seeker::REMOTE_PREFERENCES as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                            <?php selected($data['remote_pref'] ?? '', $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="inspjob-form-section">
            <h3 class="section-title">Expectativas</h3>

            <div class="inspjob-form-row">
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="salary_min">Expectativa salarial minima (S/)</label>
                    <input type="number" id="salary_min" name="salary_min" min="0" step="100"
                        value="<?php echo esc_attr($data['salary_min'] ?? ''); ?>"
                        placeholder="Ej: 3000">
                </div>
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="salary_max">Expectativa salarial maxima (S/)</label>
                    <input type="number" id="salary_max" name="salary_max" min="0" step="100"
                        value="<?php echo esc_attr($data['salary_max'] ?? ''); ?>"
                        placeholder="Ej: 5000">
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="start_date">Disponibilidad para comenzar</label>
                <select id="start_date" name="start_date">
                    <option value="">Selecciona tu disponibilidad</option>
                    <?php foreach (InspJob_Job_Seeker::AVAILABILITY_OPTIONS as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"
                            <?php selected($data['start_date'] ?? '', $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="inspjob-form-actions">
            <button type="submit" class="inspjob-btn inspjob-btn-primary">
                <span class="btn-text">
                    <?php echo $data ? 'Guardar cambios' : 'Publicar disponibilidad'; ?>
                </span>
                <span class="btn-loading" style="display: none;">
                    <svg class="spinner" viewBox="0 0 24 24" width="20" height="20">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="10">
                            <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                    Guardando...
                </span>
            </button>
        </div>

        <div class="inspjob-form-message" style="display: none;"></div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('inspjob-availability-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const messageDiv = form.querySelector('.inspjob-form-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'inspjob_save_availability');

        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.data.message, 'success');
                setTimeout(() => {
                    window.location.href = '<?php echo esc_url(home_url('/mi-disponibilidad/')); ?>';
                }, 1500);
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
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

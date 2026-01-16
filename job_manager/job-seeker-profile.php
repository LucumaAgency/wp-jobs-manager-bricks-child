<?php
/**
 * Job Seeker Profile Form Template
 *
 * @package InspJobPortal
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
$categories = get_terms(['taxonomy' => 'job_listing_category', 'hide_empty' => false]);
$job_types = get_terms(['taxonomy' => 'job_listing_type', 'hide_empty' => false]);
?>

<div class="inspjob-profile-container">
    <!-- Profile Completion Card -->
    <div class="inspjob-profile-completion-card">
        <div class="completion-header">
            <h3>Completitud del perfil</h3>
            <div class="completion-badge level-<?php echo esc_attr($profile['level']); ?>">
                <?php echo esc_html(InspJob_Job_Seeker::get_level_label($profile['level'])); ?>
            </div>
        </div>
        <div class="completion-bar-container">
            <div class="completion-bar" style="width: <?php echo esc_attr($profile['profile_completion']); ?>%"></div>
        </div>
        <div class="completion-info">
            <span class="completion-percentage"><?php echo esc_html($profile['profile_completion']); ?>% completado</span>
            <span class="completion-points"><?php echo esc_html($profile['points'] ?: 0); ?> puntos</span>
        </div>
        <?php if ($profile['profile_completion'] < 100): ?>
            <p class="completion-hint">Completa tu perfil para mejorar tus oportunidades de ser contactado</p>
        <?php endif; ?>
    </div>

    <!-- Profile Form -->
    <form id="inspjob-profile-form" class="inspjob-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('inspjob_update_profile', 'nonce'); ?>

        <!-- Basic Info Section -->
        <div class="inspjob-form-section">
            <h3 class="section-title">Informacion basica</h3>

            <div class="inspjob-form-row">
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="first_name">Nombre <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
                </div>
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="last_name">Apellido</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>">
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="headline">Titulo profesional</label>
                <input type="text" id="headline" name="headline" value="<?php echo esc_attr($profile['headline']); ?>" placeholder="Ej: Desarrollador Full Stack Senior">
            </div>

            <div class="inspjob-form-group">
                <label for="bio">Sobre mi</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Cuentanos sobre tu experiencia, logros y lo que buscas..."><?php echo esc_textarea($profile['bio']); ?></textarea>
            </div>

            <div class="inspjob-form-row">
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="phone">Telefono</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($profile['phone']); ?>" placeholder="+51 999 999 999">
                </div>
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="location">Ubicacion</label>
                    <input type="text" id="location" name="location" value="<?php echo esc_attr($profile['location']); ?>" placeholder="Ej: Lima, Peru">
                </div>
            </div>
        </div>

        <!-- Experience Section -->
        <div class="inspjob-form-section">
            <h3 class="section-title">Experiencia y habilidades</h3>

            <div class="inspjob-form-group">
                <label for="experience_level">Nivel de experiencia</label>
                <select id="experience_level" name="experience_level">
                    <option value="">Selecciona tu nivel</option>
                    <?php foreach (InspJob_Job_Seeker::EXPERIENCE_LEVELS as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($profile['experience_level'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="inspjob-form-group">
                <label for="skills-input">Habilidades</label>
                <div class="skills-input-container">
                    <input type="text" id="skills-input" placeholder="Escribe una habilidad y presiona Enter">
                    <div class="skills-tags" id="skills-tags">
                        <?php
                        $skills = $profile['skills'] ?: [];
                        foreach ($skills as $skill):
                        ?>
                            <span class="skill-tag">
                                <?php echo esc_html($skill); ?>
                                <button type="button" class="remove-skill" data-skill="<?php echo esc_attr($skill); ?>">&times;</button>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="skills" id="skills-hidden" value="<?php echo esc_attr(json_encode($skills)); ?>">
                </div>
                <span class="inspjob-form-hint">Agrega habilidades relevantes para tu busqueda (Ej: JavaScript, Gestion de Proyectos, Excel)</span>
            </div>

            <div class="inspjob-form-group">
                <label>Categorias de interes</label>
                <div class="inspjob-checkbox-grid">
                    <?php
                    $selected_categories = $profile['categories'] ?: [];
                    foreach ($categories as $category):
                    ?>
                        <label class="inspjob-checkbox-label">
                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr($category->term_id); ?>"
                                <?php checked(in_array($category->term_id, $selected_categories)); ?>>
                            <span><?php echo esc_html($category->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Preferences Section -->
        <div class="inspjob-form-section">
            <h3 class="section-title">Preferencias laborales</h3>

            <div class="inspjob-form-group">
                <label>Tipo de trabajo</label>
                <div class="inspjob-checkbox-grid">
                    <?php
                    $selected_job_types = $profile['job_types'] ?: [];
                    foreach ($job_types as $type):
                    ?>
                        <label class="inspjob-checkbox-label">
                            <input type="checkbox" name="job_types[]" value="<?php echo esc_attr($type->slug); ?>"
                                <?php checked(in_array($type->slug, $selected_job_types)); ?>>
                            <span><?php echo esc_html($type->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="remote_preference">Modalidad de trabajo</label>
                <select id="remote_preference" name="remote_preference">
                    <option value="">Selecciona tu preferencia</option>
                    <?php foreach (InspJob_Job_Seeker::REMOTE_PREFERENCES as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($profile['remote_preference'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="inspjob-form-row">
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="salary_min">Expectativa salarial minima (S/)</label>
                    <input type="number" id="salary_min" name="salary_min" value="<?php echo esc_attr($profile['salary_min']); ?>" min="0" step="100" placeholder="Ej: 3000">
                </div>
                <div class="inspjob-form-group inspjob-form-half">
                    <label for="salary_max">Expectativa salarial maxima (S/)</label>
                    <input type="number" id="salary_max" name="salary_max" value="<?php echo esc_attr($profile['salary_max']); ?>" min="0" step="100" placeholder="Ej: 5000">
                </div>
            </div>

            <div class="inspjob-form-group">
                <label for="availability">Disponibilidad</label>
                <select id="availability" name="availability">
                    <option value="">Selecciona tu disponibilidad</option>
                    <?php foreach (InspJob_Job_Seeker::AVAILABILITY_OPTIONS as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($profile['availability'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Links Section -->
        <div class="inspjob-form-section">
            <h3 class="section-title">Enlaces y documentos</h3>

            <div class="inspjob-form-group">
                <label for="linkedin">LinkedIn</label>
                <input type="url" id="linkedin" name="linkedin" value="<?php echo esc_url($profile['linkedin']); ?>" placeholder="https://linkedin.com/in/tu-perfil">
            </div>

            <div class="inspjob-form-group">
                <label for="portfolio">Portfolio / Sitio web</label>
                <input type="url" id="portfolio" name="portfolio" value="<?php echo esc_url($profile['portfolio']); ?>" placeholder="https://tu-portfolio.com">
            </div>

            <div class="inspjob-form-group">
                <label for="resume">Curriculum Vitae</label>
                <div class="resume-upload-container">
                    <?php if (!empty($profile['resume_url'])): ?>
                        <div class="current-resume">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <a href="<?php echo esc_url($profile['resume_url']); ?>" target="_blank">Ver CV actual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                    <span class="inspjob-form-hint">Formatos aceptados: PDF, DOC, DOCX. Maximo 5MB</span>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="inspjob-form-actions">
            <button type="submit" class="inspjob-btn inspjob-btn-primary">
                <span class="btn-text">Guardar cambios</span>
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
    const form = document.getElementById('inspjob-profile-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const messageDiv = form.querySelector('.inspjob-form-message');

    // Skills management
    const skillsInput = document.getElementById('skills-input');
    const skillsHidden = document.getElementById('skills-hidden');
    const skillsTags = document.getElementById('skills-tags');
    let skills = <?php echo json_encode($skills); ?> || [];

    function updateSkillsHidden() {
        skillsHidden.value = JSON.stringify(skills);
    }

    function addSkill(skill) {
        skill = skill.trim();
        if (skill && !skills.includes(skill)) {
            skills.push(skill);
            const tag = document.createElement('span');
            tag.className = 'skill-tag';
            tag.innerHTML = skill + '<button type="button" class="remove-skill" data-skill="' + skill + '">&times;</button>';
            skillsTags.appendChild(tag);
            updateSkillsHidden();
        }
    }

    function removeSkill(skill) {
        skills = skills.filter(s => s !== skill);
        updateSkillsHidden();
    }

    skillsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill(this.value);
            this.value = '';
        }
    });

    skillsTags.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-skill')) {
            const skill = e.target.dataset.skill;
            removeSkill(skill);
            e.target.parentElement.remove();
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading state
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'inspjob_update_job_seeker_profile');

        // Handle skills array
        formData.delete('skills');
        skills.forEach(skill => formData.append('skills[]', skill));

        fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.data.message, 'success');
                // Update completion bar
                const completionBar = document.querySelector('.completion-bar');
                const completionPercentage = document.querySelector('.completion-percentage');
                if (completionBar && completionPercentage) {
                    completionBar.style.width = data.data.profile_completion + '%';
                    completionPercentage.textContent = data.data.profile_completion + '% completado';
                }
            } else {
                showMessage(data.data.message, 'error');
            }
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
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

        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }
    }

    // Resume upload handling
    const resumeInput = document.getElementById('resume');
    if (resumeInput) {
        resumeInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('El archivo es demasiado grande. Maximo 5MB', 'error');
                    this.value = '';
                }
            }
        });
    }
});
</script>

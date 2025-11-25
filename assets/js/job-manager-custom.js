/**
 * JavaScript personalizado para WP Job Manager
 * InspJobPortal - Bricks Child Theme
 */

jQuery(document).ready(function($) {

    // Formatear campos de salario mientras se escriben
    $('input[name="job_salary_min"], input[name="job_salary_max"]').on('input', function() {
        // Solo permitir números
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Añadir símbolo de euro a los campos de salario
    $('input[name="job_salary_min"]').wrap('<div class="salary-input-wrapper"></div>');
    $('input[name="job_salary_min"]').before('<span class="currency-symbol">€</span>');

    $('input[name="job_salary_max"]').wrap('<div class="salary-input-wrapper"></div>');
    $('input[name="job_salary_max"]').before('<span class="currency-symbol">€</span>');

    // AJAX para guardar trabajos
    $('.btn-save-job').on('click', function(e) {
        e.preventDefault();

        var button = $(this);
        var jobId = button.data('job-id');

        $.ajax({
            url: inspjob_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_job',
                job_id: jobId,
                nonce: inspjob_ajax.nonce
            },
            beforeSend: function() {
                button.text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    button.text('Guardado ✓');
                    button.addClass('saved');
                } else {
                    alert(response.data.message);
                    button.text('Guardar');
                }
            },
            error: function() {
                alert('Error al guardar el trabajo');
                button.text('Guardar');
            }
        });
    });

    // Mejorar la experiencia del formulario
    if ($('.job-manager-form').length) {

        // Validación en tiempo real para salarios
        $('input[name="job_salary_max"]').on('blur', function() {
            var min = parseInt($('input[name="job_salary_min"]').val());
            var max = parseInt($(this).val());

            if (min && max && min > max) {
                alert('El salario máximo debe ser mayor que el mínimo');
                $(this).val('');
            }
        });

        // Preview del salario
        function updateSalaryPreview() {
            var min = $('input[name="job_salary_min"]').val();
            var max = $('input[name="job_salary_max"]').val();
            var preview = '';

            if (min && max) {
                preview = '€' + parseInt(min).toLocaleString('es-ES') + ' - €' + parseInt(max).toLocaleString('es-ES');
            } else if (min) {
                preview = 'Desde €' + parseInt(min).toLocaleString('es-ES');
            } else if (max) {
                preview = 'Hasta €' + parseInt(max).toLocaleString('es-ES');
            }

            if (preview) {
                if (!$('#salary-preview').length) {
                    $('input[name="job_salary_max"]').closest('.fieldset-job_salary_max').after('<div id="salary-preview" style="padding: 10px; background: #EBF0FC; border-radius: 6px; margin-top: 10px; color: #164FC9; font-weight: 500;">Vista previa: <span></span></div>');
                }
                $('#salary-preview span').text(preview);
            }
        }

        $('input[name="job_salary_min"], input[name="job_salary_max"]').on('input', updateSalaryPreview);
    }

    // Animación para los cards al cargar
    $('.job-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('fade-in-up');
    });

    // Filtros dinámicos
    $('.job-manager-filter').on('change', function() {
        // WP Job Manager manejará esto automáticamente
        // Esta función es para futuras personalizaciones
    });

    // Manejar las categorías populares
    $('.category-pill').on('click', function() {
        var category = $(this).data('category');
        $(this).toggleClass('active');

        // Aquí puedes añadir la lógica para filtrar por categoría
        // Por ejemplo, añadir a un campo oculto y enviar el formulario
        if ($(this).hasClass('active')) {
            // Añadir categoría a los filtros
            var currentVal = $('#search_categories').val();
            if (currentVal) {
                $('#search_categories').val(currentVal + ',' + category);
            } else {
                $('#search_categories').val(category);
            }
        } else {
            // Remover categoría de los filtros
            var currentVal = $('#search_categories').val();
            var categories = currentVal.split(',');
            var index = categories.indexOf(category);
            if (index > -1) {
                categories.splice(index, 1);
            }
            $('#search_categories').val(categories.join(','));
        }
    });

    // Actualizar contador de resultados dinámicamente
    if ($('.modern-filters-fullwidth').length) {
        // Observar cambios en el DOM para actualizar el contador
        var observer = new MutationObserver(function(mutations) {
            var jobCount = $('.job_listings li.job_listing').length;
            $('.count-number').text(jobCount);
        });

        // Configurar el observador
        if ($('.job_listings').length) {
            observer.observe(document.querySelector('.job_listings'), {
                childList: true,
                subtree: true
            });
        }

        // Contador inicial
        setTimeout(function() {
            var jobCount = $('.job_listings li.job_listing').length;
            $('.count-number').text(jobCount);
        }, 1000);
    }

    // Efecto de hover mejorado para los checkboxes
    $('.job-type-checkbox').on('mouseenter', function() {
        $(this).find('input[type="checkbox"]').prop('checked', true);
    }).on('mouseleave', function() {
        // Solo quitar el check si no estaba seleccionado previamente
        if (!$(this).find('input[type="checkbox"]').data('was-checked')) {
            $(this).find('input[type="checkbox"]').prop('checked', false);
        }
    });

    // Guardar estado previo de checkboxes
    $('.job-type-checkbox input[type="checkbox"]').on('change', function() {
        $(this).data('was-checked', $(this).prop('checked'));
    });

});

// CSS para animaciones
var style = document.createElement('style');
style.innerHTML = `
    .salary-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .currency-symbol {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #164FC9;
        font-weight: 600;
        font-size: 16px;
        pointer-events: none;
    }

    .salary-input-wrapper input {
        padding-left: 30px !important;
    }

    .fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn-save-job.saved {
        background: #10b981 !important;
        color: white !important;
    }

    #salary-preview {
        font-family: 'Montserrat', sans-serif;
    }
`;
document.head.appendChild(style);
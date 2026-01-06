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

    // Añadir símbolo de sol a los campos de salario
    $('input[name="job_salary_min"]').wrap('<div class="salary-input-wrapper"></div>');
    $('input[name="job_salary_min"]').before('<span class="currency-symbol">S/</span>');

    $('input[name="job_salary_max"]').wrap('<div class="salary-input-wrapper"></div>');
    $('input[name="job_salary_max"]').before('<span class="currency-symbol">S/</span>');

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
                preview = 'S/ ' + parseInt(min).toLocaleString('es-PE') + ' - S/ ' + parseInt(max).toLocaleString('es-PE');
            } else if (min) {
                preview = 'Desde S/ ' + parseInt(min).toLocaleString('es-PE');
            } else if (max) {
                preview = 'Hasta S/ ' + parseInt(max).toLocaleString('es-PE');
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

    // ============================================
    // FILTROS PERSONALIZADOS [inspjob_filters]
    // ============================================
    initInspjobFilters();

});

/**
 * Inicializar filtros personalizados de salario y tipo de trabajo
 */
function initInspjobFilters() {
    var $ = jQuery;
    var $filtersWrapper = $('.inspjob-filters-wrapper');

    if (!$filtersWrapper.length) {
        return;
    }

    // Actualizar clase active en chips cuando se selecciona
    $filtersWrapper.on('change', '.inspjob-filter-chip input', function() {
        var $chip = jQuery(this).closest('.inspjob-filter-chip');
        var $group = $chip.closest('.inspjob-filter-options');
        var isRadio = jQuery(this).attr('type') === 'radio';

        if (isRadio) {
            // Para radios, quitar active de todos y agregar al seleccionado
            $group.find('.inspjob-filter-chip').removeClass('active');
            $chip.addClass('active');
        } else {
            // Para checkboxes, toggle la clase active
            $chip.toggleClass('active', jQuery(this).is(':checked'));
        }

        // Actualizar contador
        updateFilterCount();
    });

    // Efecto de click en chips
    $filtersWrapper.on('click', '.inspjob-filter-chip', function() {
        var $span = jQuery(this).find('span');
        $span.css('transform', 'scale(0.95)');
        setTimeout(function() {
            $span.css('transform', 'scale(1)');
        }, 100);
    });

    // Inicializar contador
    updateFilterCount();
}

/**
 * Actualizar contador de filtros activos
 */
function updateFilterCount() {
    var $ = jQuery;
    var $wrapper = $('.inspjob-filters-wrapper');

    if (!$wrapper.length) {
        return;
    }

    var count = 0;

    // Contar radios seleccionados (excepto "Todos")
    $wrapper.find('input[type="radio"]:checked').each(function() {
        if ($(this).val() !== '') {
            count++;
        }
    });

    // Contar checkboxes seleccionados
    count += $wrapper.find('input[type="checkbox"]:checked').length;

    // Actualizar texto del botón si hay filtros activos
    var $applyBtn = $wrapper.find('.inspjob-filter-apply');
    if (count > 0) {
        $applyBtn.html(
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>' +
            '</svg> Aplicar (' + count + ')'
        );
        $wrapper.find('.inspjob-filter-clear').show();
    } else {
        $applyBtn.html(
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>' +
            '</svg> Aplicar Filtros'
        );
    }
}

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
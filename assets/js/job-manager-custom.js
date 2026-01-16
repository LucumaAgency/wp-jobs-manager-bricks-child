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

// ============================================
// INSPJOB PORTAL - EXTENDED FUNCTIONALITY
// ============================================

/**
 * Toast Notification System
 */
var InspJobToast = {
    container: null,

    init: function() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'inspjob-toast-container';
            document.body.appendChild(this.container);
        }
    },

    show: function(message, type, duration) {
        type = type || 'info';
        duration = duration || 4000;

        this.init();

        var toast = document.createElement('div');
        toast.className = 'inspjob-toast inspjob-toast-' + type;

        var icons = {
            success: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };

        toast.innerHTML = '<span class="toast-icon">' + icons[type] + '</span>' +
                          '<span class="toast-message">' + message + '</span>' +
                          '<button class="toast-close">&times;</button>';

        this.container.appendChild(toast);

        // Trigger animation
        setTimeout(function() {
            toast.classList.add('show');
        }, 10);

        // Auto-dismiss
        var self = this;
        var timeout = setTimeout(function() {
            self.dismiss(toast);
        }, duration);

        // Close button
        toast.querySelector('.toast-close').addEventListener('click', function() {
            clearTimeout(timeout);
            self.dismiss(toast);
        });
    },

    dismiss: function(toast) {
        toast.classList.remove('show');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },

    success: function(message, duration) {
        this.show(message, 'success', duration);
    },

    error: function(message, duration) {
        this.show(message, 'error', duration);
    },

    warning: function(message, duration) {
        this.show(message, 'warning', duration);
    },

    info: function(message, duration) {
        this.show(message, 'info', duration);
    }
};

/**
 * Modal Management
 */
var InspJobModal = {
    open: function(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Focus first input
            setTimeout(function() {
                var firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
    },

    close: function(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    closeAll: function() {
        document.querySelectorAll('.inspjob-modal').forEach(function(modal) {
            modal.style.display = 'none';
        });
        document.body.style.overflow = '';
    },

    init: function() {
        // Close on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                InspJobModal.closeAll();
            }
        });

        // Close on X button click
        document.querySelectorAll('.modal-close, .modal-cancel').forEach(function(el) {
            el.addEventListener('click', function() {
                InspJobModal.closeAll();
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                InspJobModal.closeAll();
            }
        });
    }
};

/**
 * Skills Input Component
 */
var InspJobSkillsInput = {
    init: function() {
        var containers = document.querySelectorAll('.skills-input-container');

        containers.forEach(function(container) {
            var input = container.querySelector('.skill-input');
            var hiddenInput = container.querySelector('input[type="hidden"]');
            var tagsContainer = container.querySelector('.skills-tags');
            var suggestions = container.querySelector('.skills-suggestions');

            if (!input || !hiddenInput) return;

            // Load existing skills
            var existingSkills = [];
            if (hiddenInput.value) {
                try {
                    existingSkills = JSON.parse(hiddenInput.value);
                } catch (e) {
                    existingSkills = [];
                }
            }

            // Render existing skills
            existingSkills.forEach(function(skill) {
                InspJobSkillsInput.addSkillTag(tagsContainer, skill, hiddenInput);
            });

            // Add skill on Enter
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var skill = this.value.trim();
                    if (skill && !InspJobSkillsInput.skillExists(hiddenInput, skill)) {
                        InspJobSkillsInput.addSkillTag(tagsContainer, skill, hiddenInput);
                        this.value = '';
                    }
                }
            });

            // Add skill on comma
            input.addEventListener('input', function() {
                if (this.value.includes(',')) {
                    var parts = this.value.split(',');
                    var self = this;
                    parts.forEach(function(part) {
                        var skill = part.trim();
                        if (skill && !InspJobSkillsInput.skillExists(hiddenInput, skill)) {
                            InspJobSkillsInput.addSkillTag(tagsContainer, skill, hiddenInput);
                        }
                    });
                    this.value = '';
                }
            });

            // Suggestions click
            if (suggestions) {
                suggestions.querySelectorAll('.suggestion-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        var skill = this.dataset.skill;
                        if (skill && !InspJobSkillsInput.skillExists(hiddenInput, skill)) {
                            InspJobSkillsInput.addSkillTag(tagsContainer, skill, hiddenInput);
                        }
                    });
                });
            }
        });
    },

    addSkillTag: function(container, skill, hiddenInput) {
        var tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerHTML = skill + '<button type="button" class="remove-skill">&times;</button>';
        container.appendChild(tag);

        // Update hidden input
        var skills = this.getSkills(hiddenInput);
        skills.push(skill);
        hiddenInput.value = JSON.stringify(skills);

        // Remove handler
        tag.querySelector('.remove-skill').addEventListener('click', function() {
            container.removeChild(tag);
            var skills = InspJobSkillsInput.getSkills(hiddenInput);
            var index = skills.indexOf(skill);
            if (index > -1) {
                skills.splice(index, 1);
                hiddenInput.value = JSON.stringify(skills);
            }
        });
    },

    getSkills: function(hiddenInput) {
        try {
            return JSON.parse(hiddenInput.value) || [];
        } catch (e) {
            return [];
        }
    },

    skillExists: function(hiddenInput, skill) {
        var skills = this.getSkills(hiddenInput);
        return skills.indexOf(skill) > -1;
    }
};

/**
 * Profile Completion Progress
 */
var InspJobProfileProgress = {
    update: function() {
        var progressBars = document.querySelectorAll('.profile-completion-progress');

        progressBars.forEach(function(bar) {
            var percentage = parseInt(bar.dataset.percentage) || 0;
            var fill = bar.querySelector('.progress-fill');
            var text = bar.querySelector('.progress-text');

            if (fill) {
                setTimeout(function() {
                    fill.style.width = percentage + '%';
                }, 100);
            }

            if (text) {
                text.textContent = percentage + '%';
            }
        });
    }
};

/**
 * Application Actions
 */
var InspJobApplications = {
    updateStatus: function(applicationId, newStatus, note, reasonId) {
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('action', 'inspjob_update_application_status');
            formData.append('nonce', inspjob_ajax.manage_applications_nonce || inspjob_ajax.nonce);
            formData.append('application_id', applicationId);
            formData.append('status', newStatus);

            if (note) {
                formData.append('note', note);
            }

            if (reasonId) {
                formData.append('rejection_reason_id', reasonId);
            }

            fetch(inspjob_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    InspJobToast.success(data.data.message || 'Estado actualizado');
                    resolve(data);
                } else {
                    InspJobToast.error(data.data.message || 'Error al actualizar');
                    reject(data);
                }
            })
            .catch(function(error) {
                InspJobToast.error('Error de conexion');
                reject(error);
            });
        });
    },

    initStatusDropdowns: function() {
        document.querySelectorAll('.status-dropdown').forEach(function(dropdown) {
            var toggle = dropdown.querySelector('.status-dropdown-toggle');
            var menu = dropdown.querySelector('.status-dropdown-menu');

            if (!toggle || !menu) return;

            toggle.addEventListener('click', function(e) {
                e.stopPropagation();

                // Close other dropdowns
                document.querySelectorAll('.status-dropdown-menu.show').forEach(function(m) {
                    if (m !== menu) m.classList.remove('show');
                });

                menu.classList.toggle('show');
            });

            // Status option click
            menu.querySelectorAll('.status-option').forEach(function(option) {
                option.addEventListener('click', function() {
                    var applicationId = dropdown.dataset.applicationId;
                    var newStatus = this.dataset.status;
                    var applicationCard = dropdown.closest('.application-card, .applicant-card, tr');

                    menu.classList.remove('show');

                    if (newStatus === 'rejected') {
                        InspJobApplications.showRejectModal(applicationId, applicationCard);
                    } else {
                        InspJobApplications.updateStatus(applicationId, newStatus)
                            .then(function() {
                                // Update UI
                                toggle.querySelector('.status-text').textContent =
                                    InspJobApplications.getStatusLabel(newStatus);
                                toggle.className = 'status-dropdown-toggle status-' + newStatus;

                                if (applicationCard) {
                                    applicationCard.dataset.status = newStatus;
                                }
                            });
                    }
                });
            });
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function() {
            document.querySelectorAll('.status-dropdown-menu.show').forEach(function(m) {
                m.classList.remove('show');
            });
        });
    },

    showRejectModal: function(applicationId, applicationCard) {
        var modal = document.getElementById('reject-modal');
        if (!modal) {
            // Create modal dynamically
            modal = document.createElement('div');
            modal.id = 'reject-modal';
            modal.className = 'inspjob-modal';
            modal.innerHTML =
                '<div class="modal-overlay"></div>' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<h3>Rechazar aplicacion</h3>' +
                        '<button type="button" class="modal-close">&times;</button>' +
                    '</div>' +
                    '<form id="reject-form">' +
                        '<div class="modal-body">' +
                            '<p>Selecciona una razon para el rechazo:</p>' +
                            '<div class="rejection-reasons">' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="1"><span>Nivel de experiencia no coincide</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="2"><span>Habilidades no coinciden</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="3"><span>Expectativas salariales no alineadas</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="4"><span>Ubicacion no compatible</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="5"><span>Puesto ya cubierto</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="6"><span>Otro candidato seleccionado</span></label>' +
                                '<label class="rejection-option"><input type="radio" name="rejection_reason" value="8"><span>Otra razon</span></label>' +
                            '</div>' +
                            '<div class="inspjob-form-group">' +
                                '<label>Nota adicional (opcional)</label>' +
                                '<textarea name="rejection_note" rows="3" placeholder="Agregar comentario privado..."></textarea>' +
                            '</div>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                            '<button type="button" class="inspjob-btn inspjob-btn-outline modal-cancel">Cancelar</button>' +
                            '<button type="submit" class="inspjob-btn inspjob-btn-danger">Rechazar</button>' +
                        '</div>' +
                    '</form>' +
                '</div>';
            document.body.appendChild(modal);
            InspJobModal.init();
        }

        // Reset form
        var form = modal.querySelector('#reject-form');
        form.reset();

        // Store application ID
        form.dataset.applicationId = applicationId;
        form.dataset.applicationCard = applicationCard ? 'yes' : 'no';

        // Form submit handler
        form.onsubmit = function(e) {
            e.preventDefault();

            var reasonInput = form.querySelector('input[name="rejection_reason"]:checked');
            if (!reasonInput) {
                InspJobToast.warning('Selecciona una razon');
                return;
            }

            var reasonId = reasonInput.value;
            var note = form.querySelector('textarea[name="rejection_note"]').value;
            var appId = form.dataset.applicationId;

            InspJobApplications.updateStatus(appId, 'rejected', note, reasonId)
                .then(function() {
                    InspJobModal.close('reject-modal');

                    // Update UI
                    var dropdown = document.querySelector('.status-dropdown[data-application-id="' + appId + '"]');
                    if (dropdown) {
                        var toggle = dropdown.querySelector('.status-dropdown-toggle');
                        toggle.querySelector('.status-text').textContent = 'Rechazada';
                        toggle.className = 'status-dropdown-toggle status-rejected';
                    }

                    // Optionally reload
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                });
        };

        InspJobModal.open('reject-modal');
    },

    getStatusLabel: function(status) {
        var labels = {
            'pending': 'Pendiente',
            'viewed': 'Vista',
            'shortlisted': 'Preseleccionado',
            'interviewing': 'Entrevistando',
            'offered': 'Oferta enviada',
            'hired': 'Contratado',
            'rejected': 'Rechazada',
            'withdrawn': 'Retirada'
        };
        return labels[status] || status;
    },

    withdraw: function(applicationId) {
        if (!confirm('¿Estas seguro de que deseas retirar esta aplicacion?')) {
            return;
        }

        this.updateStatus(applicationId, 'withdrawn')
            .then(function() {
                setTimeout(function() {
                    location.reload();
                }, 1000);
            });
    }
};

/**
 * Availability Management
 */
var InspJobAvailability = {
    toggleStatus: function(availabilityId, newStatus) {
        var formData = new FormData();
        formData.append('action', 'inspjob_toggle_availability');
        formData.append('nonce', inspjob_ajax.availability_nonce || inspjob_ajax.nonce);
        formData.append('availability_id', availabilityId);
        formData.append('status', newStatus);

        return fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                InspJobToast.success(data.data.message || 'Estado actualizado');
                return data;
            } else {
                InspJobToast.error(data.data.message || 'Error');
                throw new Error(data.data.message);
            }
        });
    },

    delete: function(availabilityId) {
        if (!confirm('¿Estas seguro de que deseas eliminar esta disponibilidad?')) {
            return Promise.reject();
        }

        var formData = new FormData();
        formData.append('action', 'inspjob_delete_availability');
        formData.append('nonce', inspjob_ajax.availability_nonce || inspjob_ajax.nonce);
        formData.append('availability_id', availabilityId);

        return fetch(inspjob_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                InspJobToast.success('Disponibilidad eliminada');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                InspJobToast.error(data.data.message || 'Error');
            }
        });
    }
};

/**
 * Dashboard Tab Navigation
 */
var InspJobTabs = {
    init: function() {
        document.querySelectorAll('.inspjob-tabs').forEach(function(tabContainer) {
            var tabs = tabContainer.querySelectorAll('.tab-button');
            var panels = tabContainer.querySelectorAll('.tab-panel');

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var targetId = this.dataset.tab;

                    // Update tab buttons
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');

                    // Update panels
                    panels.forEach(function(panel) {
                        panel.classList.remove('active');
                        if (panel.id === targetId) {
                            panel.classList.add('active');
                        }
                    });

                    // Update URL hash
                    if (history.pushState) {
                        history.pushState(null, null, '#' + targetId);
                    }
                });
            });

            // Handle initial hash
            var hash = window.location.hash.substring(1);
            if (hash) {
                var targetTab = tabContainer.querySelector('[data-tab="' + hash + '"]');
                if (targetTab) {
                    targetTab.click();
                }
            }
        });
    }
};

/**
 * Form Validation Helpers
 */
var InspJobValidation = {
    validateEmail: function(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    validateRequired: function(form) {
        var valid = true;
        var requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(function(field) {
            var value = field.value.trim();
            var group = field.closest('.inspjob-form-group');

            if (!value) {
                valid = false;
                if (group) {
                    group.classList.add('has-error');
                    if (!group.querySelector('.error-message')) {
                        var error = document.createElement('span');
                        error.className = 'error-message';
                        error.textContent = 'Este campo es requerido';
                        group.appendChild(error);
                    }
                }
            } else {
                if (group) {
                    group.classList.remove('has-error');
                    var existingError = group.querySelector('.error-message');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            }
        });

        return valid;
    },

    validateSalaryRange: function(minInput, maxInput) {
        var min = parseInt(minInput.value) || 0;
        var max = parseInt(maxInput.value) || 0;

        if (min && max && min > max) {
            InspJobToast.warning('El salario minimo no puede ser mayor que el maximo');
            return false;
        }
        return true;
    }
};

/**
 * Loading State
 */
var InspJobLoading = {
    show: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        element.classList.add('is-loading');
        element.disabled = true;

        var originalText = element.textContent;
        element.dataset.originalText = originalText;
        element.innerHTML = '<span class="loading-spinner"></span> Cargando...';
    },

    hide: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        element.classList.remove('is-loading');
        element.disabled = false;

        if (element.dataset.originalText) {
            element.textContent = element.dataset.originalText;
        }
    }
};

/**
 * Match Score Animation
 */
var InspJobMatchScore = {
    animateScores: function() {
        document.querySelectorAll('.match-score-badge').forEach(function(badge) {
            var score = parseInt(badge.dataset.score) || 0;
            var circle = badge.querySelector('.score-circle');

            if (circle) {
                var circumference = 2 * Math.PI * 18; // radius 18
                var offset = circumference - (score / 100) * circumference;

                setTimeout(function() {
                    circle.style.strokeDashoffset = offset;
                }, 100);
            }
        });
    }
};

/**
 * Timeline Animations
 */
var InspJobTimeline = {
    animate: function() {
        var timeline = document.querySelector('.application-timeline');
        if (!timeline) return;

        var items = timeline.querySelectorAll('.timeline-item');

        items.forEach(function(item, index) {
            setTimeout(function() {
                item.classList.add('animate-in');
            }, index * 150);
        });
    }
};

/**
 * Initialize all components
 */
function initInspJobComponents() {
    InspJobModal.init();
    InspJobSkillsInput.init();
    InspJobProfileProgress.update();
    InspJobApplications.initStatusDropdowns();
    InspJobTabs.init();
    InspJobMatchScore.animateScores();
    InspJobTimeline.animate();

    // Initialize contact candidate buttons
    document.querySelectorAll('.contact-btn, #contact-candidate-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var modal = document.getElementById('contact-modal');
            if (modal) {
                var availabilityId = this.dataset.id;
                var candidateName = this.dataset.name;

                if (availabilityId) {
                    var idInput = modal.querySelector('#contact-availability-id, [name="availability_id"]');
                    if (idInput) idInput.value = availabilityId;
                }

                if (candidateName) {
                    var nameSpan = modal.querySelector('#contact-name');
                    if (nameSpan) nameSpan.textContent = candidateName;
                }

                InspJobModal.open('contact-modal');
            }
        });
    });

    // Withdraw application buttons
    document.querySelectorAll('.withdraw-application').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var applicationId = this.dataset.id;
            if (applicationId) {
                InspJobApplications.withdraw(applicationId);
            }
        });
    });

    // Toggle availability status
    document.querySelectorAll('.toggle-availability').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var availabilityId = this.dataset.id;
            var newStatus = this.dataset.status;

            if (availabilityId && newStatus) {
                InspJobAvailability.toggleStatus(availabilityId, newStatus)
                    .then(function() {
                        location.reload();
                    });
            }
        });
    });

    // Delete availability
    document.querySelectorAll('.delete-availability').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var availabilityId = this.dataset.id;
            if (availabilityId) {
                InspJobAvailability.delete(availabilityId);
            }
        });
    });

    // Form submissions with loading states
    document.querySelectorAll('.inspjob-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var submitBtn = form.querySelector('button[type="submit"]');

            if (!InspJobValidation.validateRequired(form)) {
                e.preventDefault();
                InspJobToast.warning('Por favor completa todos los campos requeridos');
                return;
            }

            // Check salary range if applicable
            var salaryMin = form.querySelector('[name*="salary_min"]');
            var salaryMax = form.querySelector('[name*="salary_max"]');
            if (salaryMin && salaryMax) {
                if (!InspJobValidation.validateSalaryRange(salaryMin, salaryMax)) {
                    e.preventDefault();
                    return;
                }
            }

            if (submitBtn) {
                InspJobLoading.show(submitBtn);
            }
        });
    });
}

// Run on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInspJobComponents);
} else {
    initInspJobComponents();
}

// CSS para animaciones y componentes adicionales
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

    /* Toast Notifications */
    .inspjob-toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 100000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    }

    .inspjob-toast {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        transform: translateX(120%);
        transition: transform 0.3s ease;
        font-family: 'Montserrat', sans-serif;
    }

    .inspjob-toast.show {
        transform: translateX(0);
    }

    .inspjob-toast-success {
        border-left: 4px solid #10b981;
    }

    .inspjob-toast-success .toast-icon {
        color: #10b981;
    }

    .inspjob-toast-error {
        border-left: 4px solid #ef4444;
    }

    .inspjob-toast-error .toast-icon {
        color: #ef4444;
    }

    .inspjob-toast-warning {
        border-left: 4px solid #f59e0b;
    }

    .inspjob-toast-warning .toast-icon {
        color: #f59e0b;
    }

    .inspjob-toast-info {
        border-left: 4px solid #164FC9;
    }

    .inspjob-toast-info .toast-icon {
        color: #164FC9;
    }

    .toast-icon {
        flex-shrink: 0;
    }

    .toast-message {
        flex: 1;
        font-size: 14px;
        color: #1e293b;
    }

    .toast-close {
        background: none;
        border: none;
        font-size: 20px;
        color: #94a3b8;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .toast-close:hover {
        color: #64748b;
    }

    /* Loading Spinner */
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid currentColor;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
        vertical-align: middle;
        margin-right: 8px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .is-loading {
        opacity: 0.7;
        pointer-events: none;
    }

    /* Rejection Modal */
    .rejection-reasons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 20px;
    }

    .rejection-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .rejection-option:hover {
        background: #EBF0FC;
        border-color: #164FC9;
    }

    .rejection-option input {
        accent-color: #164FC9;
    }

    .rejection-option span {
        font-size: 14px;
        color: #334155;
    }

    /* Timeline Animation */
    .timeline-item {
        opacity: 0;
        transform: translateX(-20px);
        transition: all 0.4s ease;
    }

    .timeline-item.animate-in {
        opacity: 1;
        transform: translateX(0);
    }

    /* Match Score Circle Animation */
    .match-score-badge .score-circle {
        transition: stroke-dashoffset 1s ease;
    }

    /* Error State */
    .inspjob-form-group.has-error input,
    .inspjob-form-group.has-error select,
    .inspjob-form-group.has-error textarea {
        border-color: #ef4444;
        background-color: #fef2f2;
    }

    .inspjob-form-group .error-message {
        display: block;
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }

    /* Status Dropdown Enhancements */
    .status-dropdown {
        position: relative;
    }

    .status-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        min-width: 180px;
        z-index: 1000;
        display: none;
        overflow: hidden;
    }

    .status-dropdown-menu.show {
        display: block;
    }

    .status-option {
        display: block;
        width: 100%;
        padding: 10px 14px;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #334155;
        transition: background 0.2s;
    }

    .status-option:hover {
        background: #f1f5f9;
    }

    .status-option.danger {
        color: #ef4444;
    }

    .status-option.danger:hover {
        background: #fef2f2;
    }

    /* Responsive Toast */
    @media (max-width: 480px) {
        .inspjob-toast-container {
            left: 10px;
            right: 10px;
            max-width: none;
        }
    }
`;
document.head.appendChild(style);
<?php
/**
 * Filtros de búsqueda de trabajos - Diseño Fullwidth
 *
 * Layout:
 * - Fila 1: search_keywords | search_location
 * - Fila 2: search_experience | search_salary
 * - Fila 3: job_types (checkboxes en línea)
 *
 * Color principal: #164FC9
 * Fuente: Montserrat
 *
 * @package InspJobPortal
 */

wp_enqueue_script( 'wp-job-manager-ajax-filters' );

do_action( 'job_manager_job_filters_before', $atts );
?>

<form class="job_filters modern-filters-fullwidth">

    <?php do_action( 'job_manager_job_filters_start', $atts ); ?>

    <div class="filters-container">

        <!-- Header de filtros -->
        <div class="filters-header">
            <h2 class="filters-title">Encuentra tu próximo trabajo</h2>
            <p class="filters-subtitle">Busca entre más de <?php echo wp_count_posts('job_listing')->publish; ?> ofertas de empleo</p>
        </div>

        <!-- Fila 1: Keywords y Location -->
        <div class="filters-row row-primary">
            <div class="filter-group filter-keywords">
                <div class="filter-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <input type="text"
                       name="search_keywords"
                       id="search_keywords"
                       placeholder="Cargo, empresa o palabras clave..."
                       value="<?php echo esc_attr( $keywords ); ?>" />
            </div>

            <div class="filter-group filter-location">
                <div class="filter-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                <input type="text"
                       name="search_location"
                       id="search_location"
                       placeholder="Ciudad o región..."
                       value="<?php echo esc_attr( $location ); ?>" />
            </div>
        </div>

        <!-- Fila 2: Experience y Salary -->
        <div class="filters-row row-secondary">
            <div class="filter-group filter-experience">
                <label for="search_experience" class="filter-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Experiencia
                </label>
                <select name="search_experience" id="search_experience" class="filter-select">
                    <option value="">Cualquier experiencia</option>
                    <option value="entry" <?php selected( isset($_GET['search_experience']) && $_GET['search_experience'] == 'entry' ); ?>>Sin experiencia</option>
                    <option value="junior" <?php selected( isset($_GET['search_experience']) && $_GET['search_experience'] == 'junior' ); ?>>1-2 años</option>
                    <option value="mid" <?php selected( isset($_GET['search_experience']) && $_GET['search_experience'] == 'mid' ); ?>>3-5 años</option>
                    <option value="senior" <?php selected( isset($_GET['search_experience']) && $_GET['search_experience'] == 'senior' ); ?>>5-10 años</option>
                    <option value="expert" <?php selected( isset($_GET['search_experience']) && $_GET['search_experience'] == 'expert' ); ?>>10+ años</option>
                </select>
            </div>

            <div class="filter-group filter-salary">
                <label for="search_salary" class="filter-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Salario anual
                </label>
                <select name="search_salary" id="search_salary" class="filter-select">
                    <option value="">Cualquier salario</option>
                    <option value="0-20000" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '0-20000' ); ?>>Hasta €20.000</option>
                    <option value="20000-30000" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '20000-30000' ); ?>>€20.000 - €30.000</option>
                    <option value="30000-40000" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '30000-40000' ); ?>>€30.000 - €40.000</option>
                    <option value="40000-60000" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '40000-60000' ); ?>>€40.000 - €60.000</option>
                    <option value="60000-80000" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '60000-80000' ); ?>>€60.000 - €80.000</option>
                    <option value="80000+" <?php selected( isset($_GET['search_salary']) && $_GET['search_salary'] == '80000+' ); ?>>Más de €80.000</option>
                </select>
            </div>
        </div>

        <!-- Fila 3: Job Types -->
        <div class="filters-row row-types">
            <div class="filter-group filter-job-types">
                <label class="filter-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    Tipo de empleo
                </label>
                <div class="job-types-wrapper">
                    <?php
                    $job_types = get_terms( array(
                        'taxonomy' => 'job_listing_type',
                        'hide_empty' => false,
                    ) );

                    if ( ! empty( $job_types ) && ! is_wp_error( $job_types ) ) :
                        foreach ( $job_types as $type ) :
                            $checked = isset($_GET['search_job_type']) && in_array($type->slug, (array)$_GET['search_job_type']);
                    ?>
                        <label class="job-type-checkbox">
                            <input type="checkbox"
                                   name="search_job_type[]"
                                   value="<?php echo esc_attr( $type->slug ); ?>"
                                   <?php checked( $checked ); ?> />
                            <span class="checkbox-label"><?php echo esc_html( $type->name ); ?></span>
                            <span class="job-count">(<?php echo $type->count; ?>)</span>
                        </label>
                    <?php
                        endforeach;
                    endif;
                    ?>

                    <!-- Opción para trabajo remoto -->
                    <label class="job-type-checkbox remote-option">
                        <input type="checkbox"
                               name="search_remote"
                               value="1"
                               <?php checked( isset($_GET['search_remote']) && $_GET['search_remote'] == '1' ); ?> />
                        <span class="checkbox-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Trabajo Remoto
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Categorías populares - Opcional -->
        <?php
        $categories = get_terms( array(
            'taxonomy' => 'job_listing_category',
            'hide_empty' => true,
            'number' => 8,
            'orderby' => 'count',
            'order' => 'DESC'
        ) );

        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
        ?>
        <div class="filters-row row-categories">
            <div class="filter-group">
                <label class="filter-label">Categorías populares</label>
                <div class="categories-pills">
                    <?php foreach ( $categories as $category ) : ?>
                        <button type="button"
                                class="category-pill"
                                data-category="<?php echo esc_attr( $category->slug ); ?>">
                            <?php echo esc_html( $category->name ); ?>
                            <span class="pill-count"><?php echo $category->count; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botones de acción -->
        <div class="filters-actions">
            <button type="submit" class="btn-search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Buscar trabajos
            </button>

            <button type="button" class="btn-reset" onclick="this.form.reset(); jQuery(this.form).trigger('submit');">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                Limpiar filtros
            </button>

            <div class="results-count">
                <span class="count-number">0</span> trabajos encontrados
            </div>
        </div>

    </div>

    <!-- Campos ocultos necesarios para WP Job Manager -->
    <input type="hidden" name="filter_job_type[]" value="<?php echo esc_attr( implode( ',', $job_types ) ); ?>" />

    <?php do_action( 'job_manager_job_filters_end', $atts ); ?>

</form>

<?php do_action( 'job_manager_job_filters_after', $atts ); ?>

<noscript>Tu navegador no soporta JavaScript!</noscript>
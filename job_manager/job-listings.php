<?php
/**
 * Job Listings Template
 *
 * Template que muestra los filtros de búsqueda y el listado de trabajos
 *
 * @package InspJobPortal
 */

// Obtener los parámetros
$per_page = get_option( 'job_manager_per_page' ) ?: 12;
$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'featured';
$order = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
$show_filters = true;
$show_categories = true;
$show_pagination = true;
$show_more = true;

// Keywords y location desde GET
$keywords = ! empty( $_GET['search_keywords'] ) ? sanitize_text_field( $_GET['search_keywords'] ) : '';
$location = ! empty( $_GET['search_location'] ) ? sanitize_text_field( $_GET['search_location'] ) : '';

// Atributos para el shortcode
$atts = array(
    'per_page' => $per_page,
    'orderby' => $orderby,
    'order' => $order,
    'show_filters' => $show_filters,
    'show_categories' => $show_categories,
    'show_pagination' => $show_pagination,
    'show_more' => $show_more
);

?>

<div class="job-listings-page">

    <?php
    // Incluir los filtros de búsqueda
    if ( $show_filters ) {
        get_job_manager_template( 'job-filters.php', array(
            'per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'show_categories' => $show_categories,
            'atts' => $atts,
            'keywords' => $keywords,
            'location' => $location
        ) );
    }
    ?>

    <!-- Contenedor de resultados -->
    <div class="job-listings-container">
        <div class="container">

            <!-- Loading indicator -->
            <div class="job-listings-loading" style="display: none;">
                <div class="spinner"></div>
                <p>Cargando trabajos...</p>
            </div>

            <!-- Job listings -->
            <ul class="job_listings">
                <?php
                // Query inicial de trabajos
                $args = array(
                    'post_type' => 'job_listing',
                    'post_status' => 'publish',
                    'posts_per_page' => $per_page,
                    'orderby' => $orderby,
                    'order' => $order
                );

                // Añadir búsqueda por keywords si existe
                if ( ! empty( $keywords ) ) {
                    $args['s'] = $keywords;
                }

                // Añadir meta query para location si existe
                if ( ! empty( $location ) ) {
                    $args['meta_query'][] = array(
                        'key' => '_job_location',
                        'value' => $location,
                        'compare' => 'LIKE'
                    );
                }

                // Aplicar filtros personalizados
                if ( ! empty( $_GET['search_experience'] ) ) {
                    $args['meta_query'][] = array(
                        'key' => '_job_experience',
                        'value' => sanitize_text_field( $_GET['search_experience'] ),
                        'compare' => '='
                    );
                }

                // Filtro de salario
                if ( ! empty( $_GET['search_salary'] ) ) {
                    $salary_range = sanitize_text_field( $_GET['search_salary'] );

                    // Parsear el rango salarial
                    $min = 0;
                    $max = 999999999;

                    if ( $salary_range === '0-20000' ) {
                        $max = 20000;
                    } elseif ( $salary_range === '20000-30000' ) {
                        $min = 20000;
                        $max = 30000;
                    } elseif ( $salary_range === '30000-40000' ) {
                        $min = 30000;
                        $max = 40000;
                    } elseif ( $salary_range === '40000-60000' ) {
                        $min = 40000;
                        $max = 60000;
                    } elseif ( $salary_range === '60000-80000' ) {
                        $min = 60000;
                        $max = 80000;
                    } elseif ( $salary_range === '80000+' ) {
                        $min = 80000;
                    }

                    $args['meta_query'][] = array(
                        'relation' => 'OR',
                        array(
                            'key' => '_job_salary_min',
                            'value' => array( $min, $max ),
                            'type' => 'NUMERIC',
                            'compare' => 'BETWEEN'
                        ),
                        array(
                            'key' => '_job_salary_max',
                            'value' => array( $min, $max ),
                            'type' => 'NUMERIC',
                            'compare' => 'BETWEEN'
                        )
                    );
                }

                // Filtro de trabajo remoto
                if ( ! empty( $_GET['search_remote'] ) && $_GET['search_remote'] == '1' ) {
                    $args['meta_query'][] = array(
                        'key' => '_remote_work',
                        'value' => '1',
                        'compare' => '='
                    );
                }

                // Filtro de tipos de trabajo
                if ( ! empty( $_GET['search_job_type'] ) && is_array( $_GET['search_job_type'] ) ) {
                    $args['tax_query'][] = array(
                        'taxonomy' => 'job_listing_type',
                        'field' => 'slug',
                        'terms' => array_map( 'sanitize_text_field', $_GET['search_job_type'] ),
                        'operator' => 'IN'
                    );
                }

                $jobs = new WP_Query( $args );

                if ( $jobs->have_posts() ) :
                    while ( $jobs->have_posts() ) : $jobs->the_post();
                        // Usar el template de card personalizado
                        get_job_manager_template_part( 'content', 'job_listing' );
                    endwhile;
                else :
                    ?>
                    <li class="no-jobs-found">
                        <div class="no-jobs-message">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#164FC9" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                                <path d="M8 11h6"></path>
                            </svg>
                            <h3>No se encontraron trabajos</h3>
                            <p>Intenta ajustar los filtros de búsqueda para encontrar más resultados.</p>
                        </div>
                    </li>
                    <?php
                endif;
                wp_reset_postdata();
                ?>
            </ul>

            <?php if ( $show_pagination && $jobs->max_num_pages > 1 ) : ?>
                <!-- Paginación -->
                <nav class="job-manager-pagination">
                    <?php
                    echo paginate_links( array(
                        'total' => $jobs->max_num_pages,
                        'current' => max( 1, get_query_var( 'paged' ) ),
                        'format' => '?paged=%#%',
                        'prev_text' => '← Anterior',
                        'next_text' => 'Siguiente →',
                        'type' => 'list'
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

            <?php if ( $show_more && $jobs->max_num_pages > 1 ) : ?>
                <!-- Botón cargar más -->
                <a class="load_more_jobs" href="#" style="display: none;">
                    <strong>Cargar más trabajos</strong>
                </a>
            <?php endif; ?>

        </div>
    </div>

</div>

<style>
/* Estilos para el mensaje de no encontrado */
.no-jobs-found {
    grid-column: 1 / -1;
    width: 100%;
    padding: 4rem 2rem;
    text-align: center;
}

.no-jobs-message {
    max-width: 400px;
    margin: 0 auto;
}

.no-jobs-message svg {
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.no-jobs-message h3 {
    color: #1f2937;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    font-family: 'Montserrat', sans-serif;
}

.no-jobs-message p {
    color: #6b7280;
    font-family: 'Montserrat', sans-serif;
}

.job-listings-container {
    padding: 2rem 0;
}

.job-listings-container .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Loading spinner */
.job-listings-loading {
    text-align: center;
    padding: 3rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #164FC9;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
# InspJobPortal - Documentación del Sistema

## Índice

1. [Resumen General](#resumen-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Base de Datos](#base-de-datos)
4. [Roles de Usuario](#roles-de-usuario)
5. [Funcionalidades Implementadas](#funcionalidades-implementadas)
   - [Sistema de Matching](#1-sistema-de-matching-nivel-intermedio)
   - [Transparencia Salarial](#2-transparencia-salarial)
   - [Gamificación del Perfil (Job Seeker)](#3-gamificación-del-perfil-job-seeker)
   - [Gamificación para Empleadores](#4-gamificación-para-empleadores)
   - [Aplicación Inversa](#5-aplicación-inversa)
   - [Sistema de Transparencia Forzada (SLA)](#6-sistema-de-transparencia-forzada-sla)
   - [Score de Empleador Visible](#7-score-de-empleador-visible)
   - [Estados de Aplicación Obligatorios](#8-estados-de-aplicación-obligatorios)
   - [Razones de Rechazo Predefinidas](#9-razones-de-rechazo-predefinidas)
   - [Timeline Visible para Candidatos](#10-timeline-visible-para-candidatos)
   - [Limitar Publicaciones si No Responden](#11-limitar-publicaciones-si-no-responden)
   - [Cierre Automático de Posiciones Fantasma](#12-cierre-automático-de-posiciones-fantasma)
6. [Shortcodes Disponibles](#shortcodes-disponibles)
7. [Páginas Requeridas](#páginas-requeridas)
8. [Notificaciones por Email](#notificaciones-por-email)
9. [Cron Jobs](#cron-jobs)
10. [Archivos del Sistema](#archivos-del-sistema)

---

## Resumen General

InspJobPortal es un sistema completo de portal de empleo construido sobre WordPress y WP Job Manager. Implementa funcionalidades avanzadas de matching, transparencia, gamificación y mecanismos anti-ghosting para mejorar la experiencia tanto de candidatos como de empleadores.

### Características Principales

- **Matching Inteligente**: Algoritmo ponderado que calcula compatibilidad entre candidatos y ofertas
- **Transparencia Total**: Salarios comparados con el mercado, scores de empleadores visibles
- **Anti-Ghosting**: SLAs obligatorios, cierre automático de posiciones fantasma
- **Gamificación**: Badges, puntos y niveles para motivar a usuarios
- **Aplicación Inversa**: Candidatos pueden publicar su disponibilidad

---

## Arquitectura del Sistema

### Estructura de Archivos

```
bricks-child/
├── functions.php                          # Carga principal
├── inc/
│   ├── job-manager-customizations.php     # Customizaciones base de WP Job Manager
│   ├── database-migration.php             # Creación de tablas
│   ├── class-job-seeker.php               # Sistema de candidatos
│   ├── class-application-tracker.php      # Tracking de aplicaciones
│   ├── class-matching-engine.php          # Algoritmo de matching
│   ├── class-salary-transparency.php      # Comparación salarial
│   ├── class-employer-score.php           # Score de empleadores
│   ├── class-sla-commitment.php           # Sistema SLA
│   ├── class-application-timeline.php     # Timeline de aplicaciones
│   ├── class-ghost-cleanup.php            # Limpieza de ghost positions
│   ├── class-gamification.php             # Sistema de badges y puntos
│   ├── class-reverse-application.php      # Aplicación inversa
│   └── email-notifications.php            # Notificaciones por email
├── job_manager/
│   ├── job-seeker-register.php            # Template registro candidato
│   ├── job-seeker-profile.php             # Template perfil candidato
│   ├── job-seeker-dashboard.php           # Template dashboard candidato
│   ├── application-form.php               # Formulario de aplicación
│   ├── my-applications.php                # Mis aplicaciones (candidato)
│   ├── employer-applications.php          # Aplicaciones recibidas (empleador)
│   ├── application-timeline.php           # Timeline visual
│   ├── post-availability.php              # Publicar disponibilidad
│   ├── available-candidates.php           # Lista de candidatos
│   └── content-candidate-availability.php # Single de candidato
└── assets/
    ├── css/job-manager-custom.css         # Estilos
    └── js/job-manager-custom.js           # JavaScript
```

---

## Base de Datos

### Tablas Creadas

El sistema crea 7 tablas personalizadas con el prefijo de WordPress:

#### 1. `{prefix}inspjob_applications`
Almacena todas las aplicaciones a ofertas de trabajo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID único |
| job_id | BIGINT | ID de la oferta |
| applicant_id | BIGINT | ID del candidato |
| employer_id | BIGINT | ID del empleador |
| status | VARCHAR(50) | Estado actual |
| cover_letter | TEXT | Carta de presentación |
| resume_url | VARCHAR(255) | URL del CV |
| match_score | INT | Score de compatibilidad (0-100) |
| created_at | DATETIME | Fecha de creación |
| updated_at | DATETIME | Última actualización |
| viewed_at | DATETIME | Fecha de visualización |
| responded_at | DATETIME | Fecha de respuesta |
| rejection_reason_id | INT | ID de razón de rechazo |
| candidate_feedback | TEXT | Feedback del candidato |
| candidate_feedback_rating | INT | Rating del feedback |

#### 2. `{prefix}inspjob_application_history`
Historial de cambios de estado de aplicaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID único |
| application_id | BIGINT | ID de la aplicación |
| from_status | VARCHAR(50) | Estado anterior |
| to_status | VARCHAR(50) | Estado nuevo |
| changed_by | BIGINT | ID del usuario que cambió |
| note | TEXT | Nota del cambio |
| created_at | DATETIME | Fecha del cambio |

#### 3. `{prefix}inspjob_rejection_reasons`
Razones predefinidas de rechazo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único |
| reason_key | VARCHAR(50) | Clave única |
| reason_text | VARCHAR(255) | Texto visible |
| display_order | INT | Orden de visualización |

**Razones incluidas:**
1. El nivel de experiencia no coincide con los requisitos del puesto
2. Las habilidades no coinciden con el perfil buscado
3. Las expectativas salariales no se alinean con el presupuesto
4. La ubicación no es compatible con los requisitos del puesto
5. El puesto ya ha sido cubierto
6. Se ha seleccionado a otro candidato que se ajusta mejor al perfil
7. El proceso de selección ha sido cancelado
8. Otra razón

#### 4. `{prefix}inspjob_salary_benchmarks`
Datos de mercado para comparación salarial.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID único |
| category_id | BIGINT | ID de categoría |
| experience_level | VARCHAR(50) | Nivel de experiencia |
| location | VARCHAR(255) | Ubicación |
| percentile_25 | INT | Percentil 25 |
| percentile_50 | INT | Mediana |
| percentile_75 | INT | Percentil 75 |
| sample_size | INT | Tamaño de muestra |
| updated_at | DATETIME | Última actualización |

#### 5. `{prefix}inspjob_badges`
Definición de badges disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único |
| badge_key | VARCHAR(50) | Clave única |
| badge_name | VARCHAR(100) | Nombre del badge |
| badge_description | VARCHAR(255) | Descripción |
| badge_icon | VARCHAR(50) | Icono |
| badge_type | VARCHAR(20) | job_seeker o employer |
| points | INT | Puntos que otorga |
| requirements | TEXT | Requisitos (JSON) |

#### 6. `{prefix}inspjob_user_badges`
Badges ganados por usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID único |
| user_id | BIGINT | ID del usuario |
| badge_id | INT | ID del badge |
| earned_at | DATETIME | Fecha de obtención |

#### 7. `{prefix}inspjob_employer_contacts`
Contactos de empleadores a candidatos (aplicación inversa).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID único |
| availability_id | BIGINT | ID de disponibilidad |
| employer_id | BIGINT | ID del empleador |
| candidate_id | BIGINT | ID del candidato |
| job_id | BIGINT | ID del trabajo relacionado (opcional) |
| message | TEXT | Mensaje del empleador |
| created_at | DATETIME | Fecha de contacto |

---

## Roles de Usuario

### Job Seeker (Candidato)
Rol: `job_seeker`

**Capacidades:**
- Ver y buscar ofertas de trabajo
- Aplicar a ofertas
- Gestionar su perfil
- Publicar disponibilidad
- Ver timeline de sus aplicaciones

**Meta datos del usuario:**
```
_job_seeker_headline              # Título profesional
_job_seeker_bio                   # Biografía
_job_seeker_skills                # Skills (JSON array)
_job_seeker_experience_level      # entry/junior/mid/senior/expert
_job_seeker_salary_min            # Expectativa salarial mínima
_job_seeker_salary_max            # Expectativa salarial máxima
_job_seeker_location              # Ubicación
_job_seeker_remote_preference     # yes/no/hybrid
_job_seeker_availability          # immediate/2weeks/1month/other
_job_seeker_categories            # Categorías de interés
_job_seeker_job_types             # Tipos de trabajo preferidos
_job_seeker_profile_completion    # % de completitud (0-100)
_job_seeker_level                 # bronze/silver/gold/platinum
_job_seeker_points                # Puntos acumulados
_job_seeker_resume_url            # URL del CV
_job_seeker_linkedin              # URL de LinkedIn
_job_seeker_portfolio             # URL de portfolio
```

### Employer (Empleador)
Rol: `employer`

**Capacidades:**
- Publicar ofertas de trabajo
- Gestionar aplicaciones recibidas
- Contactar candidatos disponibles
- Ver métricas de su perfil

**Meta datos del usuario:**
```
_employer_response_rate           # % de aplicaciones respondidas
_employer_feedback_rate           # % de rechazos con feedback
_employer_avg_response_hours      # Tiempo promedio de respuesta
_employer_score                   # Score compuesto (0-100)
_employer_badge                   # responsive/highly_responsive/top_employer
_employer_blocked_until           # Fecha de bloqueo (si aplica)
_employer_total_applications      # Total de aplicaciones recibidas
_employer_total_responses         # Total de respuestas dadas
```

---

## Funcionalidades Implementadas

### 1. Sistema de Matching (Nivel Intermedio)

**Clase:** `InspJob_Matching_Engine`
**Archivo:** `inc/class-matching-engine.php`

El algoritmo de matching calcula un score de compatibilidad (0-100) entre un candidato y una oferta de trabajo.

#### Pesos del Algoritmo

| Factor | Peso | Descripción |
|--------|------|-------------|
| Salario | 25% | Coincidencia de rangos salariales |
| Experiencia | 20% | Nivel de experiencia requerido vs disponible |
| Categoría | 15% | Coincidencia de industria/categoría |
| Skills | 15% | Habilidades en común |
| Ubicación | 10% | Coincidencia geográfica |
| Remoto | 10% | Preferencia de trabajo remoto |
| Tipo de trabajo | 5% | Full-time, part-time, etc. |

#### Funciones de Scoring

```php
// Salario: Calcula overlap entre rangos
score_salary($job_min, $job_max, $seeker_min, $seeker_max)
// Retorna 1.0 si hay overlap total, proporcional si parcial, 0 si no hay

// Experiencia: Compara niveles
score_experience($job_level, $seeker_level)
// 1.0 = exacto, 0.7 = ±1 nivel, 0.3 = ±2 niveles, 0 = más

// Categoría: Intersección de categorías
score_category($job_categories, $seeker_categories)
// Porcentaje de categorías que coinciden

// Skills: Coeficiente de Jaccard
score_skills($job_skills, $seeker_skills)
// matches / (job_skills + seeker_skills - matches)

// Ubicación: Coincidencia geográfica
score_location($job_location, $seeker_location)
// 1.0 = exacto, 0.5 = misma región, 0 = diferente

// Remoto: Compatibilidad de preferencias
score_remote($job_remote, $seeker_remote)
// 1.0 = compatible, 0.5 = parcial, 0 = incompatible

// Tipo de trabajo: Match de tipo
score_job_type($job_type, $seeker_types)
// 1.0 si coincide, 0 si no
```

#### Uso

```php
// Calcular match para un candidato y un trabajo
$score = InspJob_Matching_Engine::calculate_match($job_id, $user_id);

// Obtener trabajos recomendados para un candidato
$jobs = InspJob_Matching_Engine::get_recommended_jobs($user_id, $limit);
```

---

### 2. Transparencia Salarial

**Clase:** `InspJob_Salary_Transparency`
**Archivo:** `inc/class-salary-transparency.php`

Compara el salario ofrecido con datos del mercado y muestra etiquetas informativas.

#### Etiquetas de Salario

| Etiqueta | Condición |
|----------|-----------|
| `above_market` | Salario > percentil 75 |
| `competitive` | Salario entre percentil 25 y 75 |
| `below_market` | Salario < percentil 25 |

#### Funciones

```php
// Obtener comparación salarial para un trabajo
$comparison = InspJob_Salary_Transparency::get_salary_comparison($job_id);
// Retorna: ['label' => 'competitive', 'percentile' => 65, 'market_data' => [...]]

// Actualizar benchmarks (ejecutado por cron semanal)
InspJob_Salary_Transparency::update_benchmarks();
```

#### Visualización

El shortcode `[inspjob_salary_info job_id="123"]` muestra:
- Rango salarial de la oferta
- Comparación con el mercado (badge)
- Percentiles del mercado para la categoría

---

### 3. Gamificación del Perfil (Job Seeker)

**Clase:** `InspJob_Gamification`
**Archivo:** `inc/class-gamification.php`

Sistema de puntos, badges y niveles para candidatos.

#### Niveles

| Nivel | Puntos Mínimos | Color |
|-------|----------------|-------|
| Bronze | 0 | #CD7F32 |
| Silver | 100 | #C0C0C0 |
| Gold | 300 | #FFD700 |
| Platinum | 600 | #E5E4E2 |

#### Badges para Job Seekers

| Badge | Requisito | Puntos |
|-------|-----------|--------|
| Perfil Completo | 100% de perfil completado | 50 |
| Primera Aplicación | Enviar 1 aplicación | 10 |
| Buscador Activo | Enviar 10+ aplicaciones | 30 |
| Habilidoso | Agregar 5+ skills | 20 |
| Contratado | Ser contratado 1 vez | 100 |

#### Cálculo de Completitud del Perfil

```php
$weights = [
    'headline'         => 15,  // Título profesional
    'bio'              => 15,  // Biografía
    'skills'           => 20,  // Habilidades (mínimo 3)
    'experience_level' => 10,  // Nivel de experiencia
    'salary'           => 10,  // Expectativa salarial
    'location'         => 10,  // Ubicación
    'categories'       => 10,  // Categorías de interés
    'resume'           => 10,  // CV subido
];
```

---

### 4. Gamificación para Empleadores

**Clase:** `InspJob_Gamification`
**Archivo:** `inc/class-gamification.php`

#### Badges para Empleadores

| Badge | Requisito | Puntos |
|-------|-----------|--------|
| Empleador Responsivo | Response rate ≥80% | 50 |
| Campeón del Feedback | Feedback rate ≥90% | 75 |
| Respuesta Rápida | Tiempo promedio <48 horas | 60 |
| Top Empleador | Score ≥90 | 100 |

---

### 5. Aplicación Inversa

**Clase:** `InspJob_Reverse_Application`
**Archivo:** `inc/class-reverse-application.php`

Permite a los candidatos publicar su disponibilidad para que los empleadores los contacten.

#### Custom Post Type

- **Nombre:** `candidate_avail`
- **Slug:** `/candidatos-disponibles/`

#### Meta datos de disponibilidad

```
_availability_status        # active/paused
_availability_title         # Título/posición buscada
_availability_description   # Descripción
_availability_job_types     # Tipos de trabajo (JSON)
_availability_categories    # Categorías (JSON)
_availability_experience    # Nivel de experiencia
_availability_salary_min    # Salario mínimo esperado
_availability_salary_max    # Salario máximo esperado
_availability_remote_pref   # Preferencia remoto
_availability_start_date    # Disponibilidad de inicio
_availability_location      # Ubicación
_availability_views         # Contador de vistas
_availability_contacts      # Contador de contactos
```

#### Flujo

1. Candidato publica disponibilidad con `[inspjob_post_availability]`
2. Empleadores ven listado con `[inspjob_available_candidates]`
3. Empleador contacta candidato (se registra en `inspjob_employer_contacts`)
4. Candidato recibe notificación por email
5. Se incrementa contador de contactos

---

### 6. Sistema de Transparencia Forzada (SLA)

**Clase:** `InspJob_SLA_Commitment`
**Archivo:** `inc/class-sla-commitment.php`

Obliga a empleadores a comprometerse con un tiempo de respuesta al publicar ofertas.

#### Opciones de SLA

| Opción | Días |
|--------|------|
| Rápido | 3 días |
| Estándar | 5 días |
| Extendido | 7 días |
| Máximo | 14 días |

#### Meta datos del trabajo

```
_job_sla_days        # Días de compromiso (3/5/7/14)
_job_sla_committed   # Boolean - si se comprometió
```

#### Integración

- Campo obligatorio en formulario de publicación de empleo
- Se muestra en la página del trabajo
- Se usa para cálculos de ghost positions y warnings

---

### 7. Score de Empleador Visible

**Clase:** `InspJob_Employer_Score`
**Archivo:** `inc/class-employer-score.php`

Calcula y muestra métricas de responsividad del empleador.

#### Métricas

| Métrica | Descripción |
|---------|-------------|
| Response Rate | % de aplicaciones que recibieron respuesta |
| Feedback Rate | % de rechazos con razón proporcionada |
| Avg Response Time | Tiempo promedio de respuesta en horas |
| Employer Score | Score compuesto (0-100) |

#### Cálculo del Score

```php
$score = ($response_rate * 0.5) +
         ($feedback_rate * 0.3) +
         (max(0, 100 - $avg_hours) * 0.2);
```

#### Badges de Empleador

| Badge | Requisito |
|-------|-----------|
| Top Employer | Score ≥90 y response_rate ≥95% |
| Highly Responsive | Score ≥75 y response_rate ≥80% |
| Responsive | Response rate ≥60% |

---

### 8. Estados de Aplicación Obligatorios

**Clase:** `InspJob_Application_Tracker`
**Archivo:** `inc/class-application-tracker.php`

Define un flujo obligatorio de estados para las aplicaciones.

#### Estados Disponibles

| Estado | Descripción | Color |
|--------|-------------|-------|
| `pending` | Aplicación enviada, sin revisar | Gris |
| `viewed` | Empleador vio la aplicación | Azul |
| `shortlisted` | Candidato preseleccionado | Amarillo |
| `interviewing` | En proceso de entrevistas | Naranja |
| `offered` | Oferta enviada | Púrpura |
| `hired` | Candidato contratado | Verde |
| `rejected` | Rechazado | Rojo |
| `withdrawn` | Retirado por candidato | Gris |

#### Transiciones Válidas

```php
const VALID_TRANSITIONS = [
    'pending'      => ['viewed', 'shortlisted', 'rejected'],
    'viewed'       => ['shortlisted', 'interviewing', 'rejected'],
    'shortlisted'  => ['interviewing', 'rejected'],
    'interviewing' => ['offered', 'rejected'],
    'offered'      => ['hired', 'rejected', 'withdrawn'],
    'hired'        => [],
    'rejected'     => [],
    'withdrawn'    => [],
];
```

---

### 9. Razones de Rechazo Predefinidas

Cuando un empleador rechaza una aplicación, debe seleccionar una razón predefinida.

#### Razones Disponibles

1. El nivel de experiencia no coincide con los requisitos
2. Las habilidades no coinciden con el perfil
3. Las expectativas salariales no se alinean
4. La ubicación no es compatible
5. El puesto ya ha sido cubierto
6. Se ha seleccionado a otro candidato
7. El proceso de selección ha sido cancelado
8. Otra razón

#### Beneficios

- Candidatos reciben feedback constructivo
- Empleadores tienen opciones rápidas
- Se pueden analizar tendencias de rechazo

---

### 10. Timeline Visible para Candidatos

**Clase:** `InspJob_Application_Timeline`
**Archivo:** `inc/class-application-timeline.php`

Muestra al candidato el progreso de su aplicación en formato visual.

#### Información Mostrada

- Cada cambio de estado con fecha y hora
- Tiempo transcurrido entre estados
- Estado actual destacado
- Tiempo promedio de respuesta del empleador
- Días restantes según SLA
- Próximo paso estimado

#### Ejemplo de Timeline

```
● Aplicación enviada - 15 Ene 2025, 10:30
● Vista por empleador - 16 Ene 2025, 14:15 (1 día después)
● Preseleccionado - 18 Ene 2025, 09:00 (2 días después)
◷ En evaluación - Próximo paso estimado: 3 días (según SLA)
```

---

### 11. Limitar Publicaciones si No Responden

**Clase:** `InspJob_Employer_Score`
**Archivo:** `inc/class-employer-score.php`

Bloquea a empleadores que no responden a aplicaciones.

#### Reglas de Bloqueo

```php
if ($pending_applications >= 10 && $response_rate < 50) {
    // Bloquear por 7 días
    block_employer($employer_id, '+7 days');
}
```

#### Proceso

1. Empleador acumula aplicaciones sin responder
2. Si tiene 10+ pendientes y <50% response rate → bloqueado
3. No puede publicar nuevos trabajos hasta responder
4. Se envía notificación por email explicando la situación
5. Bloqueo se levanta automáticamente después de 7 días o al responder

---

### 12. Cierre Automático de Posiciones Fantasma

**Clase:** `InspJob_Ghost_Cleanup`
**Archivo:** `inc/class-ghost-cleanup.php`

Detecta y cierra automáticamente ofertas que no reciben respuesta.

#### Definición de Ghost Position

Un trabajo se considera "fantasma" cuando:
- Tiene aplicaciones pendientes
- Han pasado más de SLA + 7 días sin respuesta

#### Proceso de Limpieza

| Día | Acción |
|-----|--------|
| SLA + 7 | Enviar warning al empleador |
| SLA + 14 | Auto-cerrar trabajo, notificar a candidatos |

#### Meta datos del trabajo

```
_ghost_warning_sent       # Fecha de warning enviado
_auto_closed_reason       # 'ghost_position'
_auto_closed_date         # Fecha de cierre automático
```

---

## Shortcodes Disponibles

### Autenticación

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_login_form]` | Formulario de login y registro unificado |
| `[inspjob_login_form default_tab="register"]` | Abre en tab de registro |

### Job Seeker

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_register_job_seeker]` | Formulario de registro (deprecado, usar login_form) |
| `[inspjob_job_seeker_profile]` | Editar perfil de candidato |
| `[inspjob_job_seeker_dashboard]` | Dashboard del candidato |

### Aplicaciones

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_apply_form job_id="123"]` | Formulario para aplicar a un trabajo |
| `[inspjob_my_applications]` | Lista de mis aplicaciones (candidato) |
| `[inspjob_job_applications]` | Aplicaciones recibidas (empleador) |
| `[inspjob_application_timeline application_id="123"]` | Timeline de una aplicación |

### Matching y Recomendaciones

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_recommended_jobs]` | Trabajos recomendados para el candidato |
| `[inspjob_recommended_jobs limit="10"]` | Con límite personalizado |

### Transparencia Salarial

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_salary_info job_id="123"]` | Comparación salarial de un trabajo |

### Score de Empleador

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_employer_score employer_id="123"]` | Score y badge del empleador |
| `[inspjob_employer_metrics]` | Métricas detalladas (para el empleador) |

### Gamificación

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_user_badges]` | Mostrar badges del usuario actual |
| `[inspjob_user_badges user_id="123"]` | Badges de un usuario específico |
| `[inspjob_user_level]` | Mostrar nivel actual |
| `[inspjob_leaderboard]` | Tabla de posiciones |

### Aplicación Inversa

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_post_availability]` | Publicar/editar disponibilidad |
| `[inspjob_available_candidates]` | Lista de candidatos disponibles |
| `[inspjob_my_availability]` | Gestionar mi disponibilidad |

### Filtros de Búsqueda

| Shortcode | Descripción |
|-----------|-------------|
| `[inspjob_filters]` | Filtros personalizados de salario y tipo |

---

## Páginas Requeridas

Para el funcionamiento completo del sistema, crear estas páginas:

| Página | Slug | Shortcode |
|--------|------|-----------|
| Iniciar Sesión | `/iniciar-sesion/` | `[inspjob_login_form]` |
| Mi Perfil | `/mi-perfil/` | `[inspjob_job_seeker_profile]` |
| Mi Dashboard | `/mi-dashboard/` | `[inspjob_job_seeker_dashboard]` |
| Mis Aplicaciones | `/mis-aplicaciones/` | `[inspjob_my_applications]` |
| Aplicaciones Recibidas | `/aplicaciones-recibidas/` | `[inspjob_job_applications]` |
| Candidatos Disponibles | `/candidatos-disponibles/` | `[inspjob_available_candidates]` |
| Publicar Disponibilidad | `/publicar-disponibilidad/` | `[inspjob_post_availability]` |
| Trabajos Recomendados | `/trabajos-recomendados/` | `[inspjob_recommended_jobs]` |
| Términos y Condiciones | `/terminos-y-condiciones/` | (contenido legal) |

---

## Notificaciones por Email

**Archivo:** `inc/email-notifications.php`

### Notificaciones Implementadas

| Evento | Destinatario | Descripción |
|--------|--------------|-------------|
| Nueva aplicación | Empleador | Candidato aplicó a su oferta |
| Cambio de estado | Candidato | Estado de aplicación cambió |
| Rechazo con razón | Candidato | Incluye la razón del rechazo |
| SLA próximo a vencer | Empleador | Reminder de responder |
| Violación de SLA | Empleador | Warning por no responder |
| Bloqueo de cuenta | Empleador | Notificación de restricción |
| Badge ganado | Usuario | Celebración de logro |
| Contacto de empleador | Candidato | Empleador quiere contactarlo |
| Ghost warning | Empleador | Aviso de cierre próximo |
| Cierre automático | Candidatos | Trabajo cerrado automáticamente |

### Personalización de Emails

Los emails usan templates HTML responsivos con:
- Logo de la empresa
- Colores corporativos (#164FC9)
- Botones de acción
- Footer con información de contacto

---

## Cron Jobs

El sistema registra los siguientes cron jobs:

| Hook | Frecuencia | Función |
|------|------------|---------|
| `inspjob_calculate_employer_scores` | Diario | Recalcular métricas de empleadores |
| `inspjob_check_sla_violations` | Cada hora | Verificar SLAs y enviar reminders |
| `inspjob_ghost_cleanup` | Diario | Cerrar ghost positions |
| `inspjob_update_salary_benchmarks` | Semanal | Actualizar datos de mercado |
| `inspjob_check_badges` | Cada hora | Verificar y otorgar badges |

### Verificar Cron Jobs

```php
// Ver próximas ejecuciones
$crons = _get_cron_array();
foreach ($crons as $timestamp => $cron) {
    foreach ($cron as $hook => $data) {
        if (strpos($hook, 'inspjob') !== false) {
            echo "$hook: " . date('Y-m-d H:i:s', $timestamp) . "\n";
        }
    }
}
```

---

## Archivos del Sistema

### Clases PHP

| Archivo | Clase | Propósito |
|---------|-------|-----------|
| `database-migration.php` | `InspJob_Database_Migration` | Crear/migrar tablas |
| `class-job-seeker.php` | `InspJob_Job_Seeker` | Gestión de candidatos |
| `class-application-tracker.php` | `InspJob_Application_Tracker` | Tracking de aplicaciones |
| `class-matching-engine.php` | `InspJob_Matching_Engine` | Algoritmo de matching |
| `class-salary-transparency.php` | `InspJob_Salary_Transparency` | Comparación salarial |
| `class-employer-score.php` | `InspJob_Employer_Score` | Score de empleadores |
| `class-sla-commitment.php` | `InspJob_SLA_Commitment` | Sistema SLA |
| `class-application-timeline.php` | `InspJob_Application_Timeline` | Timeline visual |
| `class-ghost-cleanup.php` | `InspJob_Ghost_Cleanup` | Limpieza automática |
| `class-gamification.php` | `InspJob_Gamification` | Badges y puntos |
| `class-reverse-application.php` | `InspJob_Reverse_Application` | Aplicación inversa |
| `email-notifications.php` | `InspJob_Email_Notifications` | Notificaciones |

### Templates

| Archivo | Propósito |
|---------|-----------|
| `job-seeker-register.php` | Registro de candidato |
| `job-seeker-profile.php` | Edición de perfil |
| `job-seeker-dashboard.php` | Dashboard de candidato |
| `application-form.php` | Formulario de aplicación |
| `my-applications.php` | Listado de aplicaciones |
| `employer-applications.php` | Gestión de aplicaciones |
| `application-timeline.php` | Timeline visual |
| `post-availability.php` | Publicar disponibilidad |
| `available-candidates.php` | Lista de candidatos |
| `content-candidate-availability.php` | Single de candidato |

---

## Notas de Desarrollo

### Constantes Importantes

```php
// Niveles de experiencia
InspJob_Job_Seeker::EXPERIENCE_LEVELS = [
    'entry'  => 'Sin experiencia',
    'junior' => 'Junior (1-2 años)',
    'mid'    => 'Mid-level (3-5 años)',
    'senior' => 'Senior (5-10 años)',
    'expert' => 'Expert (10+ años)',
];

// Preferencias de remoto
InspJob_Job_Seeker::REMOTE_PREFERENCES = [
    'yes'    => 'Solo remoto',
    'hybrid' => 'Híbrido',
    'no'     => 'Presencial',
];

// Disponibilidad
InspJob_Job_Seeker::AVAILABILITY_OPTIONS = [
    'immediate' => 'Inmediata',
    '2weeks'    => 'En 2 semanas',
    '1month'    => 'En 1 mes',
    'other'     => 'Otro',
];
```

### AJAX Endpoints

Todos los endpoints AJAX usan el prefijo `inspjob_`:

```php
// Registro
wp_ajax_nopriv_inspjob_register_job_seeker
wp_ajax_inspjob_register_user

// Perfil
wp_ajax_inspjob_update_job_seeker_profile
wp_ajax_inspjob_upload_resume

// Aplicaciones
wp_ajax_inspjob_submit_application
wp_ajax_inspjob_update_application_status
wp_ajax_inspjob_withdraw_application

// Disponibilidad
wp_ajax_inspjob_save_availability
wp_ajax_inspjob_toggle_availability
wp_ajax_inspjob_delete_availability
wp_ajax_inspjob_contact_candidate

// Tracking
wp_ajax_inspjob_track_view
wp_ajax_nopriv_inspjob_track_view
```

### Seguridad

- Todos los formularios usan nonces de WordPress
- Sanitización de inputs con `sanitize_text_field()`, `sanitize_email()`, etc.
- Escape de outputs con `esc_html()`, `esc_attr()`, `esc_url()`
- Verificación de capacidades con `current_user_can()`
- Prepared statements para queries SQL

---

## Changelog

### v1.0.0 (Enero 2025)

- Implementación inicial del sistema completo
- 12 clases PHP con funcionalidad completa
- 10 templates de frontend
- Sistema de matching con algoritmo ponderado
- Transparencia salarial con comparación de mercado
- Gamificación para candidatos y empleadores
- Sistema anti-ghosting con SLA y cierre automático
- Aplicación inversa para candidatos
- Notificaciones por email
- CSS responsivo y JavaScript modular

---

## Soporte

Para reportar bugs o solicitar funcionalidades:
- GitHub: [LucumaAgency/wp-jobs-manager-bricks-child](https://github.com/LucumaAgency/wp-jobs-manager-bricks-child)

---

*Documentación generada para InspJobPortal - Bricks Child Theme*
*Color principal: #164FC9 | Fuente: Montserrat*

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema de Cursos
    |--------------------------------------------------------------------------
    |
    | Configuraciones centralizadas para el sistema de cursos, certificados
    | y gestión de estudiantes. Todas configurables por variables de entorno.
    |
    */

    // =======================================================================
    // CERTIFICADOS Y APROBACIÓN
    // =======================================================================
    
    'pass_threshold' => (float) env('COURSE_PASS_THRESHOLD', 60),
    'virtual_certificate_threshold' => (float) env('VIRTUAL_CERTIFICATE_THRESHOLD', 80),
    'complete_certificate_threshold' => (float) env('COMPLETE_CERTIFICATE_THRESHOLD', 70),
    'auto_generate_certificates' => env('AUTO_GENERATE_CERTIFICATES', true),

    // =======================================================================
    // SISTEMA DE CALIFICACIONES
    // =======================================================================
    
    'grading' => [
        'quiz_weight' => (int) env('QUIZ_WEIGHT_PERCENTAGE', 50),
        'activity_weight' => (int) env('ACTIVITY_WEIGHT_PERCENTAGE', 50),
        'scale' => env('GRADING_SCALE', 'percentage'), // percentage, letter, points
        'decimal_places' => (int) env('GRADE_DECIMAL_PLACES', 2),
    ],

    // =======================================================================
    // GESTIÓN DE CURSOS Y ESTUDIANTES
    // =======================================================================
    
    'enrollment' => [
        'max_students_per_course' => env('MAX_STUDENTS_PER_COURSE', 'unlimited'), // unlimited o número
        'allow_self_unenroll' => env('ALLOW_SELF_UNENROLL', false),
        'completion_grace_days' => (int) env('COURSE_COMPLETION_GRACE_DAYS', 30),
        'auto_enrollment' => env('AUTO_ENROLLMENT', false), // Para futuro
        'require_approval' => env('REQUIRE_ENROLLMENT_APPROVAL', false), // Para futuro
    ],

    // =======================================================================
    // CERTIFICADOS - PERSONALIZACIÓN
    // =======================================================================
    
    'certificates' => [
        'issuer_name' => env('CERTIFICATE_ISSUER_NAME', 'VanguardIA'),
        'template' => env('CERTIFICATE_TEMPLATE', 'default'),
        'logo_url' => env('CERTIFICATE_LOGO_URL', ''),
        'favicon_url' => env('CERTIFICATE_FAVICON_URL', ''),
        'custom_message' => env('CERTIFICATE_CUSTOM_MESSAGE', 'Felicitaciones por completar exitosamente el curso'),
        'signature_text' => env('CERTIFICATE_SIGNATURE_TEXT', 'Certificado Digital'),
        'footer_text' => env('CERTIFICATE_FOOTER_TEXT', 'Este certificado verifica la finalización exitosa del curso'),
        
        // Colores personalizables
        'primary_color' => env('CERTIFICATE_PRIMARY_COLOR', '#3B82F6'),
        'secondary_color' => env('CERTIFICATE_SECONDARY_COLOR', '#10B981'),
        'text_color' => env('CERTIFICATE_TEXT_COLOR', '#1F2937'),
        
        // Configuraciones técnicas
        'format' => env('CERTIFICATE_FORMAT', 'pdf'), // pdf, png, jpg
        'orientation' => env('CERTIFICATE_ORIENTATION', 'landscape'), // landscape, portrait
        'quality' => env('CERTIFICATE_QUALITY', 'high'), // high, medium, low
    ],

    // =======================================================================
    // ANALYTICS Y REPORTES
    // =======================================================================
    
    'analytics' => [
        'default_metrics' => explode(',', env('DEFAULT_METRICS', 'progress,completion,engagement,time_spent')),
        'auto_report_frequency' => env('AUTO_REPORT_FREQUENCY', 'weekly'), // daily, weekly, monthly
        'export_format' => env('EXPORT_FORMAT', 'pdf'), // pdf, excel, csv
        'track_detailed_progress' => env('TRACK_DETAILED_PROGRESS', true),
        'track_time_spent' => env('TRACK_TIME_SPENT', true),
    ],

    // =======================================================================
    // CONFIGURACIONES DE PROGRESO
    // =======================================================================
    
    'progress' => [
        'calculation_method' => env('PROGRESS_CALCULATION', 'weighted'), // simple, weighted, custom
        'component_weights' => [
            'lessons' => (int) env('LESSON_WEIGHT', 40),
            'quizzes' => (int) env('QUIZ_WEIGHT', 30),
            'activities' => (int) env('ACTIVITY_WEIGHT', 30),
        ],
        'minimum_time_per_component' => (int) env('MIN_TIME_PER_COMPONENT', 60), // segundos
        'auto_mark_complete' => env('AUTO_MARK_COMPLETE', true),
    ],

    // =======================================================================
    // NOTIFICACIONES
    // =======================================================================
    
    'notifications' => [
        'certificate_generated' => env('NOTIFY_CERTIFICATE_GENERATED', true),
        'course_completed' => env('NOTIFY_COURSE_COMPLETED', true),
        'enrollment_confirmed' => env('NOTIFY_ENROLLMENT_CONFIRMED', true),
        'progress_milestones' => explode(',', env('PROGRESS_MILESTONES', '25,50,75,90')),
    ],

    // =======================================================================
    // LÍMITES Y RESTRICCIONES
    // =======================================================================
    
    'limits' => [
        'max_file_upload_size' => env('MAX_UPLOAD_SIZE_MB', 50), // MB
        'max_course_duration' => env('MAX_COURSE_DURATION_HOURS', 200), // horas
        'max_modules_per_course' => env('MAX_MODULES_PER_COURSE', 50),
        'max_lessons_per_module' => env('MAX_LESSONS_PER_MODULE', 20),
        'session_timeout' => env('SESSION_TIMEOUT_MINUTES', 480), // 8 horas
    ],

    // =======================================================================
    // CARACTERÍSTICAS FUTURAS (Para expansión SaaS)
    // =======================================================================
    
    'features' => [
        'virtual_classrooms' => env('ENABLE_VIRTUAL_CLASSROOMS', false),
        'live_sessions' => env('ENABLE_LIVE_SESSIONS', false),
        'assignments' => env('ENABLE_ASSIGNMENTS', false),
        'forums' => env('ENABLE_FORUMS', false),
        'gamification' => env('ENABLE_GAMIFICATION', false),
        'ai_recommendations' => env('ENABLE_AI_RECOMMENDATIONS', false),
    ],
];
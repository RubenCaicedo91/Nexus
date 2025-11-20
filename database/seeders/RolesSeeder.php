<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RolesModel;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Rector',
                'descripcion' => 'Máxima autoridad del colegio, responsable de la dirección y administración general',
                'permisos' => [
                    'gestionar_coordinadores',
                    'ver_reportes_generales',
                    'aprobar_desiciones_académicas',
                    "consultar_info_institucional",
                    // Permisos para gestionar usuarios, asignar roles y gestionar académica
                    'gestionar_usuarios',
                    'asignar_roles',
                    'gestionar_academica',
                    // Permisos académicos (docentes, materias y asignaciones)
                    'asignar_docentes',
                    'crear_cursos',
                    'editar_cursos',
                    'crear_horarios',
                    'editar_horarios',
                    'crear_materias',
                    'editar_materias',
                    'gestionar_asignaciones',
                    'asignar_estudiantes',
                ]
            ],
            [
                'nombre' => 'Coordinador Académico',
                'descripcion' => 'Encargado de coordinar actividades académicas y disciplinarias',
                'permisos' => [
                    // Permisos académicos explícitos
                    'gestionar_academica',
                    'asignar_docentes',
                    'consultar_reportes_academicos',
                    'crear_horarios',
                    'editar_horarios',
                    'crear_cursos',
                    'editar_cursos',
                    'crear_materias',
                    'editar_materias',
                    'gestionar_asignaciones',
                ]
            ],
            [
                'nombre' => 'Coordinador de Disciplina',
                'descripcion' => 'Encargado de supervisar el comportamiento estudiantil y gestionar reportes disciplinarios',
                'permisos' => [
                    'registrar_reporte_disciplinario',
                    'asignar_sanciones',
                    'consultar_informes_disciplinarios',
                    'generar_estadisticas_disciplina',
                    'ver_estudiantes_asignados'
                ]
            ],
            [
                'nombre' => 'Docente',
                'descripcion' => 'Profesor encargado de impartir clases y evaluar estudiantes',
                'permisos' => [
                    'ver_estudiantes_asignados',
                    'registrar_notas',
                    'ver_horarios',
                    'generar_reportes_materia'
                ]
            ],
            [
                'nombre' => 'Estudiante',
                'descripcion' => 'Estudiante del colegio',
                'permisos' => [
                    'ver_notas',
                    'ver_horarios',
                    'ver_actividades',
                    'consultar_calificaciones',
                    'consultar_reporte_disciplinarios',
                    'consultar_reporte_academicos',
                    'consultar_pensiones_deudas',
                    'exportar_reportes_financieros',
                    'solicitar_cita_orientacion'

                ]
            ],
            [
                'nombre' => 'Acudiente',
                'descripcion' => 'Padre, madre o acudiente responsable del estudiante',
                'permisos' => [
                   
                    'consultar_reporte_disciplinarios',
                    'consultar_reporte_academicos',
                    'ver_horarios_estudiante',
                    'actualizar_datos_contacto',
                    'justificar_inasistencias',
                    'solicitar_cita_orientacion'
                ]

                ],
            [
                'nombre' => 'orientador',
                'descripcion' => 'Profesional encargado de brindar apoyo emocional y orientación a los estudiantes',
                'permisos' => [
                    'ver_estudiantes_asignados',
                    'registrar_sesiones_orientacion',
                    'ver_horarios',
                    'generar_reportes_orientacion',
                    'consultar_circulares_institucionales'
                ]



                ],
                [
                    'nombre' => 'tesorero',
                    'descripcion' => 'Profesional encargado de la gestión financiera y contable del colegio',
                    'permisos' => [
                        'generar_estados_financieros', // Balance, estado de resultados, etc.
                        'registrar_pagos',
                        'generar_reportes_financieros', // Reportes de ingresos, egresos, deudas, etc.
                         'consultar_circulares_institucionales'
                ]

             ],
             [
                'nombre' => 'Administrador_sistema',
                'descripcion' => 'Responsable de la gestión técnica y mantenimiento del sistema',
                'permisos' => [
                    'gestionar_usuarios', // Crear, editar, eliminar usuarios del sistema
                    'asignar_roles', // Asignar y modificar roles y permisos
                    'realizar_backup', // Realizar copias de seguridad de la base de datos
                    'restaurar_backup', // Restaurar la base de datos desde una copia de seguridad
                    'monitorizar_sistema', // Supervisar el rendimiento y estado del sistema
                    'actualizar_sistema', // Aplicar actualizaciones y parches al sistema
                    'configurar_parametros', // Configurar parámetros generales del sistema
                    'actualizar_datos_institucionales' // Actualizar información institucional como nombre, dirección, contacto, etc.

                ]

                ]
        ];


        foreach ($roles as $rol) {
            RolesModel::updateOrCreate(
                ['nombre' => $rol['nombre']],
                $rol
            );
        }
    }
}

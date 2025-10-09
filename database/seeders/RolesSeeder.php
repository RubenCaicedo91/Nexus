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
                    'gestionar_usuarios',
                    'gestionar_docentes',
                    'gestionar_estudiantes',
                    'gestionar_coordinadores',
                    'gestionar_acudientes',
                    'ver_reportes_generales',
                    'configurar_sistema',
                    'gestionar_roles',
                    'aprobar_matrículas'
                ]
            ],
            [
                'nombre' => 'Coordinador',
                'descripcion' => 'Encargado de coordinar actividades académicas y disciplinarias',
                'permisos' => [
                    'gestionar_estudiantes',
                    'gestionar_docentes',
                    'ver_reportes_académicos',
                    'gestionar_horarios',
                    'gestionar_disciplina',
                    'aprobar_permisos'
                ]
            ],
            [
                'nombre' => 'Docente',
                'descripcion' => 'Profesor encargado de impartir clases y evaluar estudiantes',
                'permisos' => [
                    'ver_estudiantes_asignados',
                    'registrar_notas',
                    'crear_actividades',
                    'ver_horarios',
                    'comunicarse_acudientes',
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
                    'ver_comunicados',
                    'actualizar_perfil_básico'
                ]
            ],
            [
                'nombre' => 'Acudiente',
                'descripcion' => 'Padre, madre o acudiente responsable del estudiante',
                'permisos' => [
                    'ver_notas_estudiante',
                    'ver_horarios_estudiante',
                    'comunicarse_docentes',
                    'ver_reportes_estudiante',
                    'actualizar_datos_contacto',
                    'justificar_inasistencias'
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
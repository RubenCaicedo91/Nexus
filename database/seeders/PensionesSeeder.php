<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pension;
use App\Models\User;
use App\Models\Matricula;
use Carbon\Carbon;

class PensionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Usar matrículas activas para tomar estudiantes confiables
        $matriculas = Matricula::where('estado', 'activa')->with(['user', 'curso'])->take(12)->get();
        if ($matriculas->isEmpty()) {
            $this->command->info('No hay matrículas activas para crear pensiones.');
            return;
        }

        $mesActual = (int) date('n');
        $añoActual = (int) date('Y');
        $created = 0;

        foreach ($matriculas as $matricula) {
            $estudiante = $matricula->user;
            if (! $estudiante) continue;

            // Determinar acudiente: preferir el campo en el usuario si existe, si no usar un usuario cualquiera para
            // cumplir la restricción NOT NULL en la tabla pensiones.
            $acudienteId = $estudiante->acudiente_id ?? null;
            if (empty($acudienteId)) {
                $fallbackUser = User::first();
                $acudienteId = $fallbackUser ? $fallbackUser->id : null;
            }
            // Si aún es null, usar el propio estudiante como acudiente (solo para datos de prueba)
            if (empty($acudienteId)) {
                $acudienteId = $estudiante->id;
            }

            // Pensión del mes actual
            Pension::create([
                'estudiante_id' => $estudiante->id,
                'acudiente_id' => $acudienteId,
                'curso_id' => $matricula->curso_id,
                'grado' => $matricula->curso->grado ?? 'N/A',
                'concepto' => 'Pensión Escolar',
                // Periodo (columna canonica)
                'mes_correspondiente' => $mesActual,
                'año_correspondiente' => $añoActual,
                'fecha_generacion' => Carbon::now()->format('Y-m-d'),
                'valor_base' => 150000,
                'descuentos' => 0,
                'recargos' => 0,
                'fecha_vencimiento' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'estado' => Pension::ESTADO_PENDIENTE,
            ]);
            $created++;

            // Pensión anterior (algunos pagados y algunos vencidos)
            $mesPrev = $mesActual - 1 > 0 ? $mesActual - 1 : 12;
            $añoPrev = $mesActual - 1 > 0 ? $añoActual : $añoActual - 1;

            $pensionPrev = Pension::create([
                'estudiante_id' => $estudiante->id,
                'acudiente_id' => $acudienteId,
                'curso_id' => $matricula->curso_id,
                'grado' => $matricula->curso->grado ?? 'N/A',
                'concepto' => 'Pensión Escolar',
                // Periodo (columna canonica)
                'mes_correspondiente' => $mesPrev,
                'año_correspondiente' => $añoPrev,
                'fecha_generacion' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'valor_base' => 150000,
                'descuentos' => 0,
                'recargos' => 0,
                'fecha_vencimiento' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'estado' => Pension::ESTADO_VENCIDA,
            ]);
            $created++;

            // Convertir algunos a pagada
            if ($created % 3 === 0) {
                $p = $pensionPrev;
                $p->estado = Pension::ESTADO_PAGADA;
                $p->fecha_pago = Carbon::now()->subDays(3)->toDateTimeString();
                $p->metodo_pago = Pension::METODO_TRANSFERENCIA;
                // La tabla tiene `numero_factura` mientras el modelo usa `numero_recibo` en algunos lugares;
                // guardar en `numero_factura` para cumplir con el esquema
                $p->numero_factura = 'REC-' . Carbon::now()->format('Ymd') . '-' . $p->id;
                $p->save();
            }
        }

        $this->command->info("Pensiones de prueba creadas: {$created}");
    }
}

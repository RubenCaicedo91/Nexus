<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use App\Models\Curso;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materias = Materia::with(['cursos', 'docente'])->orderBy('nombre')->paginate(10);
        return view('materias.index', compact('materias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cursos = Curso::orderBy('nombre')->get();
        
        // Obtener solo usuarios con rol de docente
        $rolDocente = RolesModel::where('nombre', 'Docente')->first();
        $docentes = User::where('roles_id', $rolDocente->id ?? 0)->orderBy('name')->get();
        
        $materias = Materia::orderBy('nombre')->get();
        return view('materias.create', compact('cursos', 'docentes', 'materias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
            'nuevo_curso' => 'nullable|string|max:255',
            'docente_id' => 'nullable|exists:users,id',
        ], [
            'nombre.required' => 'El nombre de la materia es obligatorio.',
            //'nombre.unique' => 'Ya existe una materia con este nombre.',
            'cursos.*.exists' => 'Alguno de los cursos seleccionados no existe.',
            'docente_id.exists' => 'El docente seleccionado no existe.',
        ]);
        // Aceptamos dos vías: seleccionar cursos existentes (cursos[]) o crear uno nuevo (nuevo_curso).
        $selected = $request->input('cursos', []) ?: [];
        if (! is_array($selected)) $selected = [];

        $nuevoCursoName = trim($request->input('nuevo_curso', ''));

        if (empty($selected) && $nuevoCursoName === '') {
            return back()->withErrors(['cursos' => 'Debe seleccionar al menos un curso o crear uno nuevo.'])->withInput();
        }

        // Si viene nuevo_curso, crear (o recuperar) y añadir su id al array
        if ($nuevoCursoName !== '') {
            $cursoModel = Curso::firstOrCreate(['nombre' => $nuevoCursoName], ['descripcion' => null]);
            if ($cursoModel && $cursoModel->id) {
                $selected[] = $cursoModel->id;
            }
        }

        // Eliminar valores marcador como '__other__' si quedaron
        $selected = array_values(array_filter($selected, function($v){ return $v !== '__other__' && $v !== null && $v !== ''; }));

        try {
            // Si ya existe una materia con ese nombre, NO crear duplicado.
            // En su lugar usamos la materia existente y asociamos (sin desasociar) los cursos seleccionados.
            $nombre = trim($request->input('nombre'));
            $existing = Materia::where('nombre', $nombre)->first();
            if ($existing) {
                if (!empty($selected)) {
                    $existing->cursos()->syncWithoutDetaching($selected);
                }
                return redirect()->route('materias.index')
                               ->with('info', "Existe una materia con el nombre \"{$nombre}\". Se usó la materia existente y se asignaron los cursos seleccionados.");
            }

            $materia = Materia::create($request->only(['nombre','descripcion','docente_id']));
            // sincronizar cursos
            $materia->cursos()->sync($selected);
            return redirect()->route('materias.index')
                           ->with('success', 'Materia creada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear la materia: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $materia = Materia::with(['cursos', 'docente'])->findOrFail($id);
        return view('materias.show', compact('materia'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $materia = Materia::findOrFail($id);
        $cursos = Curso::orderBy('nombre')->get();
        
        // Obtener solo usuarios con rol de docente
        $rolDocente = RolesModel::where('nombre', 'Docente')->first();
        $docentes = User::where('roles_id', $rolDocente->id ?? 0)->orderBy('name')->get();
        
        $materias = Materia::orderBy('nombre')->get();
        return view('materias.edit', compact('materia', 'cursos', 'docentes', 'materias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $materia = Materia::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
            'nuevo_curso' => 'nullable|string|max:255',
            'docente_id' => 'nullable|exists:users,id',
        ], [
            'nombre.required' => 'El nombre de la materia es obligatorio.',
            //'nombre.unique' => 'Ya existe una materia con este nombre.',
            'cursos.*.exists' => 'Alguno de los cursos seleccionados no existe.',
            'docente_id.exists' => 'El docente seleccionado no existe.',
        ]);
        // Manejar cursos y posible nuevo curso (misma lógica que en store)
        $selected = $request->input('cursos', []) ?: [];
        if (! is_array($selected)) $selected = [];
        $nuevoCursoName = trim($request->input('nuevo_curso', ''));
        if (empty($selected) && $nuevoCursoName === '') {
            return back()->withErrors(['cursos' => 'Debe seleccionar al menos un curso o crear uno nuevo.'])->withInput();
        }
        if ($nuevoCursoName !== '') {
            $cursoModel = Curso::firstOrCreate(['nombre' => $nuevoCursoName], ['descripcion' => null]);
            if ($cursoModel && $cursoModel->id) {
                $selected[] = $cursoModel->id;
            }
        }
        $selected = array_values(array_filter($selected, function($v){ return $v !== '__other__' && $v !== null && $v !== ''; }));

        try {
            $nombre = trim($request->input('nombre'));
            $existing = Materia::where('nombre', $nombre)->first();

            // Si existe otra materia con el mismo nombre y no es la misma que estamos editando,
            // realizamos una fusión: reasignamos referencias y asociamos cursos al registro existente,
            // luego eliminamos la materia antigua.
            if ($existing && $existing->id != $materia->id) {
                // Reasignar referencias en tablas que usan materia_id
                try {
                    DB::table('notas')->where('materia_id', $materia->id)->update(['materia_id' => $existing->id]);
                } catch (\Throwable $e) {
                    // ignorar si la tabla no existe o falla
                }
                try {
                    DB::table('horarios')->where('materia_id', $materia->id)->update(['materia_id' => $existing->id]);
                } catch (\Throwable $e) {
                }
                try {
                    DB::table('asistencias')->where('materia_id', $materia->id)->update(['materia_id' => $existing->id]);
                } catch (\Throwable $e) {
                }

                // Asociar cursos seleccionados al existente sin quitar las previas
                if (!empty($selected)) {
                    $existing->cursos()->syncWithoutDetaching($selected);
                }

                // Transferir campos opcionales si vienen y el existente no los tiene
                $updateData = [];
                if ($request->filled('descripcion') && empty($existing->descripcion)) $updateData['descripcion'] = $request->input('descripcion');
                if ($request->filled('docente_id') && empty($existing->docente_id)) $updateData['docente_id'] = $request->input('docente_id');
                if (!empty($updateData)) {
                    $existing->update($updateData);
                }

                // Finalmente eliminar la materia antigua
                $materia->delete();

                return redirect()->route('materias.index')
                               ->with('info', "Se detectó una materia existente con el nombre '{$nombre}'. Se fusionaron los cursos y referencias con la materia existente.");
            }

            // Si no hay conflicto, actualizar la materia normalmente
            $materia->update($request->only(['nombre','descripcion','docente_id']));
            $materia->cursos()->sync($selected);
            return redirect()->route('materias.index')
                           ->with('success', 'Materia actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar la materia: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $materia = Materia::findOrFail($id);
            $nombre = $materia->nombre;
            
            $materia->delete();
            
            return redirect()->route('materias.index')
                           ->with('success', "Materia '{$nombre}' eliminada exitosamente.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la materia: ' . $e->getMessage()]);
        }
    }
}

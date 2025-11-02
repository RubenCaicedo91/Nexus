<!DOCTYPE html>
<html>
<head>
    <title>Test Select de Estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Prueba Simple - Select de Estudiantes</h2>
        
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            Total estudiantes: {{ count($estudiantes) }}<br>
            Datos recibidos: {{ $estudiantes ? 'SÍ' : 'NO' }}
        </div>

        <div class="mb-3">
            <label for="user_id" class="form-label">Estudiantes Disponibles:</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">-- Seleccionar estudiante --</option>
                @if(isset($estudiantes) && count($estudiantes) > 0)
                    @foreach($estudiantes as $estudiante)
                        <option value="{{ $estudiante->id }}">
                            {{ $estudiante->name }} - {{ $estudiante->email }}
                        </option>
                    @endforeach
                @else
                    <option value="" disabled>❌ No hay estudiantes disponibles</option>
                @endif
            </select>
        </div>

        <button onclick="debugSelect()" class="btn btn-primary">🔍 Debug Select</button>
        
        <div class="mt-4">
            <h4>Raw Data:</h4>
            <pre>{{ print_r($estudiantes->toArray() ?? [], true) }}</pre>
        </div>
    </div>

    <script>
        function debugSelect() {
            const select = document.getElementById('user_id');
            console.log('Select:', select);
            console.log('Options count:', select.options.length);
            
            for (let i = 0; i < select.options.length; i++) {
                console.log(`Option ${i}:`, select.options[i].value, select.options[i].text);
            }
            
            alert(`Opciones encontradas: ${select.options.length}`);
        }
        
        // Auto debug
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== AUTO DEBUG TEST PAGE ===');
            debugSelect();
        });
    </script>
</body>
</html>
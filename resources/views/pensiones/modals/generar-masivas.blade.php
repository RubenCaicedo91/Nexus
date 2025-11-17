<!-- Modal para generar pensiones masivas -->
<div class="modal fade" id="generarMasivasModal" tabindex="-1" aria-labelledby="generarMasivasLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="generarMasivasLabel">
					<i class="fas fa-layer-group me-2"></i>
					Generar Pensiones Masivas
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

			<form action="{{ route('pensiones.generar-masivas') }}" method="POST">
				@csrf
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">Curso (opcional)</label>
							<select name="curso_id" class="form-select">
								<option value="">Todos</option>
								@foreach($cursos as $curso)
									<option value="{{ $curso->id }}">{{ $curso->nombre }} - {{ $curso->grado }}</option>
								@endforeach
							</select>
						</div>

						<div class="col-md-6">
							<label class="form-label">Grado (opcional)</label>
							<input type="text" name="grado" class="form-control" placeholder="Ej: 3°" />
						</div>

						<div class="col-md-4">
							<label class="form-label">Mes</label>
							<select name="mes" class="form-select" required>
								@for($i = 1; $i <= 12; $i++)
									<option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
								@endfor
							</select>
						</div>

						<div class="col-md-4">
							<label class="form-label">Año</label>
							<select name="año" class="form-select" required>
								@for($y = date('Y'); $y <= date('Y') + 1; $y++)
									<option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
								@endfor
							</select>
						</div>

						<div class="col-md-4">
							<label class="form-label">Concepto</label>
							<input type="text" name="concepto" class="form-control" value="Pensión Escolar" required />
						</div>

						<div class="col-md-6">
							<label class="form-label">Valor Base</label>
							<div class="input-group">
								<span class="input-group-text">$</span>
								<input type="number" name="valor_base" class="form-control" min="0" step="1000" value="0" required />
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label">Fecha de Vencimiento</label>
							<input type="date" name="fecha_vencimiento" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required />
						</div>

						<div class="col-md-6">
							<label class="form-label">Descuentos</label>
							<div class="input-group">
								<span class="input-group-text">$</span>
								<input type="number" name="descuentos" class="form-control" min="0" step="1000" value="0" />
							</div>
						</div>

						<div class="col-md-6">
							<label class="form-label">Recargos</label>
							<div class="input-group">
								<span class="input-group-text">$</span>
								<input type="number" name="recargos" class="form-control" min="0" step="1000" value="0" />
							</div>
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary">Generar Pensiones</button>
				</div>
			</form>
		</div>
	</div>
</div>

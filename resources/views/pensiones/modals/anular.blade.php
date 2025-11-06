<!-- Modal para anular pensión -->
<div class="modal fade" id="anularModal{{ $pension->id }}" tabindex="-1" aria-labelledby="anularModalLabel{{ $pension->id }}" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="anularModalLabel{{ $pension->id }}">
					<i class="fas fa-ban me-2 text-danger"></i>
					Anular Pensión - {{ $pension->estudiante->name }}
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

			<form action="{{ route('pensiones.anular', $pension) }}" method="POST">
				@csrf
				<div class="modal-body">
					<p>Por favor indique el motivo de anulación de la pensión. Esta acción no se puede deshacer.</p>

					<div class="mb-3">
						<label for="motivo_anulacion_{{ $pension->id }}" class="form-label">Motivo de Anulación <span class="text-danger">*</span></label>
						<textarea id="motivo_anulacion_{{ $pension->id }}" name="motivo_anulacion" class="form-control" rows="4" required maxlength="500" placeholder="Describa brevemente por qué se anula esta pensión..."></textarea>
					</div>

					<div class="alert alert-warning small">
						<i class="fas fa-exclamation-triangle me-1"></i>
						La pensión quedará marcada como anulada y no podrá ser cobrada desde el sistema.
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-danger">Anular Pensión</button>
				</div>
			</form>
		</div>
	</div>
</div>

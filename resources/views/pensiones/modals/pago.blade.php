<!-- Modal para procesar pago -->
<div class="modal fade" id="pagoModal{{ $pension->id }}" tabindex="-1" aria-labelledby="pagoModalLabel{{ $pension->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pagoModalLabel{{ $pension->id }}">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Procesar Pago - {{ $pension->estudiante->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('pensiones.procesar-pago', $pension) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Información de la pensión -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Concepto:</strong> {{ $pension->concepto }}<br>
                                            <strong>Período:</strong> {{ DateTime::createFromFormat('!m', $pension->mes)->format('F') }} {{ $pension->año }}<br>
                                            <strong>Vencimiento:</strong> {{ \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('d/m/Y') }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Valor Base:</strong> ${{ number_format($pension->valor_base, 0) }}<br>
                                            @if($pension->descuentos > 0)
                                                <strong>Descuentos:</strong> -${{ number_format($pension->descuentos, 0) }}<br>
                                            @endif
                                            @if($pension->recargos > 0)
                                                <strong>Recargos:</strong> +${{ number_format($pension->recargos, 0) }}<br>
                                            @endif
                                            @if($pension->isVencida() && $pension->calcularRecargo() > 0)
                                                <strong class="text-danger">Mora:</strong> +${{ number_format($pension->calcularRecargo(), 0) }}<br>
                                            @endif
                                            <hr>
                                            <strong class="text-primary">Total a Pagar:</strong> 
                                            <span class="h5 text-primary">
                                                ${{ number_format($pension->valor_base + $pension->recargos - $pension->descuentos + ($pension->isVencida() ? $pension->calcularRecargo() : 0), 0) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del pago -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="metodo_pago{{ $pension->id }}" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select" id="metodo_pago{{ $pension->id }}" name="metodo_pago" required>
                                    <option value="">Seleccionar método</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia Bancaria</option>
                                    <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                                    <option value="consignacion">Consignación</option>
                                    <option value="pse">PSE</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="numero_recibo{{ $pension->id }}" class="form-label">Número de Recibo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="numero_recibo{{ $pension->id }}" name="numero_recibo" 
                                       placeholder="Ej: REC-001234" required maxlength="50">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="comprobante_pago{{ $pension->id }}" class="form-label">Comprobante de Pago</label>
                                <input type="file" class="form-control" id="comprobante_pago{{ $pension->id }}" name="comprobante_pago" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">Formatos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="observaciones_pago{{ $pension->id }}" class="form-label">Observaciones del Pago</label>
                                <textarea class="form-control" id="observaciones_pago{{ $pension->id }}" name="observaciones_pago" 
                                          rows="3" maxlength="500" placeholder="Observaciones adicionales sobre el pago..."></textarea>
                            </div>
                        </div>
                    </div>

                    @if($pension->isVencida())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Esta pensión está vencida desde hace {{ $pension->diasVencida() }} días. 
                            Se aplicará un recargo por mora de ${{ number_format($pension->calcularRecargo(), 0) }}.
                        </div>
                    @endif
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Procesar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
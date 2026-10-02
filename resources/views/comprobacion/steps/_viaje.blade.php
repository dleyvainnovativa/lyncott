{{-- STEP 2 · Datos del Viaje (Phase 3) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-calendar-days"></i></span>
    <div>
        <h2>Datos del Viaje</h2>
        <p>Registra el periodo, origen y destino de tu viaje.</p>
    </div>
</div>

<form id="formViaje" autocomplete="off" novalidate>
    <div class="lx-card p-4 mt-3">
        <div class="row g-3">
            {{-- Period --}}
            <div class="col-md-6">
                <label class="form-label" for="fecha_salida">Fecha de salida *</label>
                <input type="date" class="form-control lx-mono" id="fecha_salida" name="fecha_salida">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="fecha_regreso">Fecha de regreso *</label>
                <input type="date" class="form-control lx-mono" id="fecha_regreso" name="fecha_regreso">
            </div>
            <div class="col-12">
                <div class="lx-note mt-0" id="totalDias" aria-live="polite">
                    <i class="fa-regular fa-clock"></i>
                    <span>Selecciona las fechas para calcular la duración.</span>
                </div>
            </div>

            {{-- Route --}}
            <div class="col-md-6">
                <label class="form-label" for="origen">Origen *</label>
                <input type="text" class="form-control" id="origen" name="origen" placeholder="Escribe el lugar donde parte">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="destino">Destino *</label>
                <input type="text" class="form-control" id="destino" name="destino" placeholder="Escribe el lugar destino">
            </div>

            {{-- Advance --}}
            <div class="col-md-6">
                <label class="form-label" for="folio_anticipo">Folio de anticipo <span class="text-secondary" style="font-weight:400;color:var(--lx-faint)">(opcional)</span></label>
                <input type="text" class="form-control" id="folio_anticipo" name="folio_anticipo" placeholder="#A12419222">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="monto_anticipo">Monto del anticipo</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control lx-mono" id="monto_anticipo" name="monto_anticipo" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>

            {{-- Cost center + transport --}}
            <div class="col-md-6">
                <label class="form-label" for="centro_costos">Centro de costos *</label>
                <select class="form-select" id="centro_costos" name="centro_costos">
                    <option value="">Seleccione centro de costos</option>
                    @foreach ($centros as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="medio_transporte">Medio de transporte *</label>
                <select class="form-select" id="medio_transporte" name="medio_transporte">
                    <option value="">Seleccione el medio de transporte</option>
                    @foreach ($medios as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Notes --}}
            <div class="col-12">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Escriba sus observaciones"></textarea>
            </div>
        </div>
    </div>

    <div class="lx-foot">
        <button type="button" class="btn btn-lx-ghost" data-wizard-prev>
            <i class="fa-solid fa-arrow-left me-1"></i> Anterior
        </button>
        <button type="button" class="btn btn-lx" data-wizard-next>
            Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>
        </button>
    </div>
</form>

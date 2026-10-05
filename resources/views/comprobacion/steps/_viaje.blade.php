{{-- STEP 2 · Datos del Viaje (per-flow) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-calendar-days"></i></span>
    <div>
        <h2>Datos del Viaje</h2>
        <p>Registra el periodo del gasto y los datos de pago.</p>
    </div>
</div>

<form id="formViaje" autocomplete="off" novalidate>
    <div class="lx-card p-4 mt-3">
        <div class="row g-3">

            {{-- Date range (single flatpickr field -> fecha_gasto_1 / fecha_gasto_2) --}}
            <div class="col-12">
                <label class="form-label" for="fecha_rango">Fecha del Gasto <span class="lx-req">*</span></label>
                <input type="text" class="form-control lx-mono" id="fecha_rango"
                       placeholder="Selecciona el rango de fechas" autocomplete="off">
                <input type="hidden" name="fecha_gasto_1" id="fecha_gasto_1">
                <input type="hidden" name="fecha_gasto_2" id="fecha_gasto_2">
                <div class="lx-note mt-2" id="totalDias" aria-live="polite">
                    <i class="fa-regular fa-clock"></i>
                    <span>Selecciona el rango para calcular la duración.</span>
                </div>
            </div>

            {{-- Monto del anticipo (anticipo) / Folio de anticipo (comprobación) --}}
            <div class="col-md-6" data-field="monto_anticipo">
                <label class="form-label" for="monto_anticipo">Monto del anticipo <span class="lx-req" data-req="monto_anticipo"></span></label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control lx-mono" id="monto_anticipo" name="monto_anticipo" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-6" data-field="folio_anticipo">
                <label class="form-label" for="folio_anticipo">Folio de anticipo <span class="lx-req" data-req="folio_anticipo"></span></label>
                <input type="text" class="form-control" id="folio_anticipo" name="folio_anticipo" placeholder="#A12419222">
            </div>

            {{-- Tipo de Gasto + Centro de costos --}}
            <div class="col-md-6" data-field="tipo_gasto">
                <label class="form-label" for="tipo_gasto">Tipo de Gasto <span class="lx-req" data-req="tipo_gasto"></span></label>
                <select class="form-select" id="tipo_gasto" name="tipo_gasto">
                    <option value="">Seleccione el tipo de gasto</option>
                    @foreach ($tiposGasto as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6" data-field="centro_costos">
                <label class="form-label" for="centro_costos">Centro de costos <span class="lx-req" data-req="centro_costos"></span></label>
                <select class="form-select" id="centro_costos" name="centro_costos">
                    <option value="">Seleccione centro de costos</option>
                    @foreach ($centros as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Banco + CLABE --}}
            <div class="col-md-6" data-field="banco">
                <label class="form-label" for="banco">Banco <span class="lx-req" data-req="banco"></span></label>
                <select class="form-select" id="banco" name="banco">
                    <option value="">Seleccione el banco</option>
                    @foreach ($bancos as $b)
                        <option value="{{ $b->nombre }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6" data-field="clabe">
                <label class="form-label" for="clabe">CLABE <span class="lx-req" data-req="clabe"></span></label>
                <input type="text" class="form-control lx-mono" id="clabe" name="clabe"
                       maxlength="18" placeholder="18 dígitos" inputmode="numeric">
                <div class="lx-note mt-1" data-hint="clabe" hidden>
                    <i class="fa-solid fa-circle-info"></i><span>La CLABE debe tener 18 dígitos.</span>
                </div>
            </div>

            {{-- Sucursal --}}
            <div class="col-md-6" data-field="sucursal">
                <label class="form-label" for="sucursal">Sucursal <span class="lx-req" data-req="sucursal"></span></label>
                <input type="text" class="form-control" id="sucursal" name="sucursal" placeholder="Escribe la sucursal">
            </div>

            {{-- Observaciones --}}
            <div class="col-12" data-field="observaciones">
                <label class="form-label" for="observaciones">Observaciones <span class="lx-req" data-req="observaciones"></span></label>
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

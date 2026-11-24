{{-- STEP · Desglose del anticipo (solo flujo anticipo) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-coins"></i></span>
    <div>
        <h2>Desglose del anticipo</h2>
        <p>Captura el monto por concepto. La suma de todos los conceptos será el
            <b>monto del anticipo</b>.</p>
    </div>
</div>

<form id="formDesglose" autocomplete="off" novalidate>
    <div class="lx-card p-4 mt-3">
        <div class="row g-3">
            <div class="col-md-4" data-field="alimentos">
                <label class="form-label" for="dg_alimentos">Alimentos</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control lx-mono dg-amt" id="dg_alimentos" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-4" data-field="pasajes">
                <label class="form-label" for="dg_pasajes">Pasajes</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control lx-mono dg-amt" id="dg_pasajes" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-4" data-field="peajes">
                <label class="form-label" for="dg_peajes">Peajes</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control lx-mono dg-amt" id="dg_peajes" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
        </div>

        <hr class="my-4" style="border-color:var(--lx-line)">

        {{-- Otros: repetidor (monto + descripción) --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Otros</label>
            <button type="button" class="btn btn-lx-sub btn-sm" id="dgAddOtro">
                <i class="fa-solid fa-plus me-1"></i> Agregar otro
            </button>
        </div>
        <div id="dgOtros" class="d-flex flex-column gap-2"></div>
        <div class="lx-note mt-2" id="dgOtrosEmpty">
            <i class="fa-solid fa-circle-info"></i>
            <span>Agrega conceptos adicionales con su descripción (opcional).</span>
        </div>
    </div>

    {{-- Total en vivo --}}
    <div class="lx-card p-4 mt-3 d-flex align-items-center justify-content-between">
        <div>
            <div style="font-size:.78rem;color:var(--lx-muted);text-transform:uppercase;letter-spacing:.6px">Monto del anticipo</div>
            <div style="font-size:.8rem;color:var(--lx-faint)" id="dgResumen">Suma de todos los conceptos</div>
        </div>
        <div class="lx-tnum" style="font-size:1.6rem;font-weight:700" id="dgTotal">$0.00</div>
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

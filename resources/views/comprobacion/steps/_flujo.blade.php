{{-- STEP 0 · Tipo de solicitud --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-code-branch"></i></span>
    <div>
        <h2>Tipo de solicitud</h2>
        <p>Elige qué deseas tramitar. El flujo se ajusta según tu elección.</p>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <button type="button" class="lx-flow-card" data-flujo="anticipo" aria-pressed="false">
            <span class="lx-flow-ico"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span>
                <span class="lx-flow-t">Solicitud de anticipo</span>
                <span class="lx-flow-d d-block">Solicita un monto por adelantado. Sin facturas: solo el importe del anticipo.</span>
            </span>
            <i class="fa-solid fa-circle-check lx-flow-check"></i>
        </button>
    </div>
    <div class="col-md-6">
        <button type="button" class="lx-flow-card" data-flujo="comprobacion" aria-pressed="false">
            <span class="lx-flow-ico"><i class="fa-solid fa-file-invoice-dollar"></i></span>
            <span>
                <span class="lx-flow-t">Comprobación de gastos</span>
                <span class="lx-flow-d d-block">Sube tus facturas (XML/PDF), el sistema lee el CFDI y arma la relación de gastos.</span>
            </span>
            <i class="fa-solid fa-circle-check lx-flow-check"></i>
        </button>
    </div>
</div>

<div class="lx-foot">
    <span></span>
    <button type="button" class="btn btn-lx" data-wizard-next>
        Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>
    </button>
</div>

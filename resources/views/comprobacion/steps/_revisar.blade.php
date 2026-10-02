{{-- STEP 4 · Revisar y Enviar (Phase 5) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-circle-check"></i></span>
    <div>
        <h2>Revisar y Enviar</h2>
        <p>Por favor, revisa tus datos antes de enviar.</p>
    </div>
</div>

{{-- ============ Review (shown before submit) ============ --}}
<div id="revisarContenido" class="lx-review mt-3">
    <div class="lx-review__card">
        <div class="lx-review__title"><i class="fa-solid fa-user"></i> Datos del Solicitante</div>
        <div class="lx-review__grid">
            <div><div class="lx-review__k">Nombre</div><div class="lx-review__v" data-rv="nombre">—</div></div>
            <div><div class="lx-review__k">Correo electrónico</div><div class="lx-review__v" data-rv="correo">—</div></div>
            <div><div class="lx-review__k">Número de empleado</div><div class="lx-review__v lx-mono" data-rv="numero">—</div></div>
            <div><div class="lx-review__k">Departamento</div><div class="lx-review__v" data-rv="departamento">—</div></div>
            <div><div class="lx-review__k">Puesto</div><div class="lx-review__v" data-rv="puesto">—</div></div>
            <div><div class="lx-review__k">Centro de costos</div><div class="lx-review__v" data-rv="centro">—</div></div>
        </div>
    </div>

    <div class="lx-review__card">
        <div class="lx-review__title"><i class="fa-solid fa-calendar-days"></i> Datos del Viaje</div>
        <div class="lx-review__grid">
            <div><div class="lx-review__k">Fecha de salida</div><div class="lx-review__v lx-mono" data-rv="salida">—</div></div>
            <div><div class="lx-review__k">Fecha de regreso</div><div class="lx-review__v lx-mono" data-rv="regreso">—</div></div>
            <div><div class="lx-review__k">Duración</div><div class="lx-review__v" data-rv="duracion">—</div></div>
            <div><div class="lx-review__k">Ruta</div><div class="lx-review__v" data-rv="ruta">—</div></div>
            <div><div class="lx-review__k">Medio de transporte</div><div class="lx-review__v" data-rv="medio">—</div></div>
            <div><div class="lx-review__k">Folio / Monto anticipo</div><div class="lx-review__v" data-rv="anticipo">—</div></div>
        </div>
    </div>

    {{-- Anticipo amount (anticipo flow only) --}}
    <div class="lx-tiles" id="rvAnticipo" style="margin:0" hidden>
        <div class="lx-tile is-total" style="grid-column:1/-1">
            <div class="lx-k">Monto del anticipo solicitado</div>
            <div class="lx-v lx-tnum" data-rv="tAnt">$0.00</div>
        </div>
    </div>

    {{-- Totals (comprobación flow only) --}}
    <div class="lx-tiles" id="rvTotales" style="margin:0">
        <div class="lx-tile">
            <div class="lx-k"><span class="lx-tag" style="background:var(--lx-ok)"></span>Con factura</div>
            <div class="lx-v lx-tnum" data-rv="tCf">$0.00</div>
        </div>
        <div class="lx-tile">
            <div class="lx-k"><span class="lx-tag" style="background:var(--lx-neutral)"></span>Sin factura</div>
            <div class="lx-v lx-tnum" data-rv="tSf">$0.00</div>
        </div>
        <div class="lx-tile is-total">
            <div class="lx-k">Total comprobación</div>
            <div class="lx-v lx-tnum" data-rv="tTot">$0.00</div>
        </div>
    </div>

    {{-- PDF preview (comprobación flow only) --}}
    <div class="lx-review__card" id="rvPdfCard">
        <div class="lx-review__title"><i class="fa-regular fa-file-pdf"></i> Vista previa — DETALLE DE GASTOS</div>
        <div id="pdfWrap" style="position:relative;min-height:220px">
            <div id="pdfLoading" class="lx-empty"><i class="fa-solid fa-spinner fa-spin me-2"></i>Generando vista previa…</div>
            <iframe id="pdfPreview" class="lx-pdf-frame" title="Vista previa del PDF" hidden></iframe>
        </div>
    </div>

    {{-- Terms --}}
    <label class="d-flex align-items-center gap-2" style="cursor:pointer;color:var(--lx-red-ink);font-weight:600">
        <input type="checkbox" id="terminos" class="form-check-input mt-0">
        Acepto que la información es correcta (términos y condiciones).
    </label>
</div>

{{-- ============ Success (shown after submit) ============ --}}
<div id="revisarExito" class="lx-review mt-3" hidden>
    <div class="lx-review__card" style="text-align:center;border-color:var(--lx-ok)">
        <div style="font-size:2.4rem;color:var(--lx-ok)"><i class="fa-solid fa-circle-check"></i></div>
        <h3 style="margin:8px 0 2px">Comprobación registrada</h3>
        <p style="color:var(--lx-muted);margin:0" data-ex="message"></p>
        <div class="lx-review__grid" style="margin-top:18px;text-align:left">
            <div><div class="lx-review__k">Folio</div><div class="lx-review__v lx-mono" data-ex="folio">—</div></div>
            <div><div class="lx-review__k" data-ex-label="total">Total comprobación</div><div class="lx-review__v lx-mono" data-ex="total">—</div></div>
            <div><div class="lx-review__k">Payload Smartker</div><div class="lx-review__v" data-ex="payload">—</div></div>
            <div><div class="lx-review__k">Archivos adjuntos</div><div class="lx-review__v" data-ex="files">—</div></div>
        </div>
    </div>
</div>

<div class="lx-foot" id="revisarFoot">
    <button type="button" class="btn btn-lx-ghost" data-wizard-prev>
        <i class="fa-solid fa-arrow-left me-1"></i> Anterior
    </button>
    <button type="button" class="btn btn-lx" data-wizard-next>
        <i class="fa-solid fa-paper-plane me-1"></i> Enviar Solicitud
    </button>
</div>

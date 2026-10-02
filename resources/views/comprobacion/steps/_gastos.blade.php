{{-- STEP 3 · Gestión de gastos (Phase 4) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-file-invoice-dollar"></i></span>
    <div>
        <h2>Gestión de gastos</h2>
        <p>Arrastra los XML de tus facturas o captura gastos sin comprobante.
            El sistema lee el CFDI y clasifica cada línea.</p>
    </div>
</div>

{{-- Summary tiles --}}
<div class="lx-tiles">
    <div class="lx-tile">
        <div class="lx-k"><span class="lx-tag" style="background:var(--lx-ok)"></span>Con factura</div>
        <div class="lx-v lx-tnum" data-tile="cf">$0.00</div>
        <div class="lx-sub" data-tile="cf-sub">0 gastos · IVA $0.00</div>
    </div>
    <div class="lx-tile">
        <div class="lx-k"><span class="lx-tag" style="background:var(--lx-neutral)"></span>Sin factura</div>
        <div class="lx-v lx-tnum" data-tile="sf">$0.00</div>
        <div class="lx-sub" data-tile="sf-sub">0 gastos</div>
    </div>
    <div class="lx-tile is-total">
        <div class="lx-k">Total comprobación</div>
        <div class="lx-v lx-tnum" data-tile="tot">$0.00</div>
        <div class="lx-sub" data-tile="tot-sub">0 líneas en total</div>
    </div>
</div>

{{-- Intake: drop zone + add buttons --}}
<div class="row g-3 align-items-stretch mb-3">
    <div class="col-lg-8">
        <label class="lx-drop h-100 mb-0" id="lxDrop">
            <span class="lx-drop__big"><i class="fa-solid fa-cloud-arrow-down"></i></span>
            <span>
                <span class="lx-drop__t">Arrastra tus archivos XML aquí</span>
                <span class="lx-drop__d">o <b>haz clic para seleccionar</b> · CFDI 4.0 · se lee fecha, importe, emisor y UUID</span>
            </span>
            <input type="file" id="lxXmlInput" accept=".xml,text/xml" multiple hidden>
        </label>
    </div>
    <div class="col-lg-4 d-flex flex-column gap-2 justify-content-center">
        <button type="button" class="btn btn-lx" id="btnAddGasto">
            <i class="fa-solid fa-plus me-1"></i> Agregar gasto
        </button>
        <button type="button" class="btn btn-lx-sub" id="btnDemoXml">
            <i class="fa-regular fa-file-code me-1"></i> Cargar XML de ejemplo
        </button>
    </div>
</div>

{{-- Toolbar --}}
<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <div class="input-group" style="max-width:300px">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" class="form-control" id="lxSearch" placeholder="Buscar por emisor, RFC o categoría…">
    </div>
    <span class="ms-auto" style="font-size:.82rem;color:var(--lx-muted)" data-tile="count">0 gastos</span>
</div>

{{-- Table --}}
<div class="lx-card">
    <div class="lx-tbl-scroll">
        <table class="lx-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Emisor</th>
                    <th>Categoría</th>
                    <th style="text-align:right">Importe</th>
                    <th style="text-align:right">IVA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="lxRows"></tbody>
        </table>
    </div>
</div>

<div class="lx-note">
    <i class="fa-solid fa-lightbulb"></i>
    <span><b>Categoría automática:</b> al leer el XML se sugiere la categoría según el emisor
        (aerolíneas → Transportación Aérea, hoteles → Hotel). El campo queda editable en cada línea.</span>
</div>

<div class="lx-foot">
    <button type="button" class="btn btn-lx-ghost" data-wizard-prev>
        <i class="fa-solid fa-arrow-left me-1"></i> Anterior
    </button>
    <button type="button" class="btn btn-lx" data-wizard-next>
        Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>
    </button>
</div>

{{-- Add-gasto modal --}}
<div class="modal fade" id="modalGasto" tabindex="-1" aria-labelledby="modalGastoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header lx-modal-head">
                <h3 class="modal-title h5 mb-0" id="modalGastoTitle">Agregar gasto</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="lx-seg mb-3" id="segTipo" role="tablist">
                    <button type="button" class="is-on" data-m="sf">Sin factura</button>
                    <button type="button" data-m="cf">Con factura (XML)</button>
                </div>

                {{-- Sin factura (manual) --}}
                <div id="sfFields" class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="mCat">Categoría</label>
                        <select class="form-select" id="mCat"></select>
                    </div>
                    <div>
                        <label class="form-label" for="mFecha">Fecha</label>
                        <input type="date" class="form-control lx-mono" id="mFecha">
                    </div>
                    <div>
                        <label class="form-label" for="mCosto">Costo (MXN)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control lx-mono" id="mCosto" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="mCompania">Compañía / concepto</label>
                        <input type="text" class="form-control" id="mCompania" placeholder="Ej. Taxi aeropuerto">
                    </div>
                    <div>
                        <label class="form-label" for="mJustificacion">Justificación</label>
                        <textarea class="form-control" id="mJustificacion" rows="2" placeholder="Motivo del gasto"></textarea>
                    </div>
                </div>

                {{-- Con factura (XML + optional PDF) --}}
                <div id="cfFields" class="d-flex flex-column gap-3" hidden>
                    <div>
                        <label class="form-label" for="mXml">Archivo XML (CFDI 4.0) *</label>
                        <input type="file" class="form-control" id="mXml" accept=".xml,text/xml">
                    </div>
                    <div>
                        <label class="form-label" for="mPdf">Archivo PDF <span style="font-weight:400;color:var(--lx-faint)">(opcional)</span></label>
                        <input type="file" class="form-control" id="mPdf" accept="application/pdf,.pdf">
                    </div>
                    <p style="font-size:.78rem;color:var(--lx-faint);margin:0">
                        El importe, fecha, emisor y UUID se extraen del XML automáticamente;
                        la categoría se sugiere y queda editable en la tabla.
                    </p>
                </div>
            </div>
            <div class="modal-footer lx-modal-foot">
                <button type="button" class="btn btn-lx-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-lx" id="btnGuardarGasto">Guardar</button>
            </div>
        </div>
    </div>
</div>

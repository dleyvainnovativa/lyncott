{{-- STEP 1 · Datos del Solicitante (Phase 2) --}}
<div class="lx-panel-head">
    <span class="lx-ico"><i class="fa-solid fa-user"></i></span>
    <div>
        <h2>Datos del Solicitante</h2>
        <p>Ingresa tu número de empleado y RFC, luego da clic en
            <i class="fa-solid fa-magnifying-glass"></i> Buscar.</p>
    </div>
</div>

<form id="formSolicitante" autocomplete="off" novalidate>
    <div class="lx-card p-4 mt-3">
        {{-- Lookup keys --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="numero_empleado">Número de empleado *</label>
                <div class="lx-input-wrap">
                    <input type="text" class="form-control" id="numero_empleado"
                           name="numero_empleado" placeholder="Ej. 082907558" inputmode="numeric">
                    <i class="fa-solid fa-circle-check lx-ok-check" aria-hidden="true"></i>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="rfc">RFC *</label>
                <div class="lx-input-wrap">
                    <input type="text" class="form-control text-uppercase" id="rfc"
                           name="rfc" maxlength="13" placeholder="Ej. PASK820101XYZ">
                    <i class="fa-solid fa-circle-check lx-ok-check" aria-hidden="true"></i>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" id="btnBuscar" class="btn btn-lx">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                </button>
            </div>
        </div>

        <hr class="my-4" style="border-color:var(--lx-line)">

        {{-- Read-only result (filled by JS after a successful lookup) --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre completo</label>
                <input type="text" class="form-control" data-emp="nombre_completo" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo electrónico</label>
                <input type="text" class="form-control" data-emp="correo" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Puesto</label>
                <input type="text" class="form-control" data-emp="puesto" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gerencia</label>
                <input type="text" class="form-control" data-emp="gerencia" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Departamento</label>
                <input type="text" class="form-control" data-emp="departamento" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Días disponibles</label>
                <input type="text" class="form-control lx-mono" data-emp="dias_disponibles" readonly>
            </div>
            <div class="col-12">
                <label class="form-label">Dirección perteneciente</label>
                <input type="text" class="form-control" data-emp="direccion" readonly>
            </div>
        </div>
    </div>

    <div class="lx-foot">
        <span></span>
        <button type="button" class="btn btn-lx" data-wizard-next>
            Siguiente <i class="fa-solid fa-arrow-right ms-1"></i>
        </button>
    </div>
</form>

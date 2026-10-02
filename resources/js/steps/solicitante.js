/* ==========================================================================
   Step 1 · Datos del Solicitante
   Looks up an employee by número + RFC and fills the read-only fields.
   Registers the wizard hook so "Siguiente" is blocked until an employee
   is validated. Keeps the fetched record in central wizard state.
   ========================================================================== */

export function initSolicitante(Lx) {
  const panel = document.querySelector('[data-step="1"]');
  if (!panel) return;

  const numero = panel.querySelector('#numero_empleado');
  const rfc    = panel.querySelector('#rfc');
  const btn    = panel.querySelector('#btnBuscar');
  const wraps  = [numero.closest('.lx-input-wrap'), rfc.closest('.lx-input-wrap')];

  let empleado = null;

  const fill = (data) => {
    panel.querySelectorAll('[data-emp]').forEach((el) => {
      el.value = data?.[el.dataset.emp] ?? '';
    });
  };
  const setOk = (on) => wraps.forEach((w) => w.classList.toggle('is-ok', on));
  const clearResult = () => { empleado = null; setOk(false); fill({}); };

  // Typing again invalidates the previous match.
  numero.addEventListener('input', clearResult);
  rfc.addEventListener('input', clearResult);

  async function buscar() {
    const n = numero.value.trim();
    const r = rfc.value.trim().toUpperCase();
    rfc.value = r;
    if (!n || !r) { Lx.toast.error('Captura número de empleado y RFC.'); return; }

    try {
      const res = await Lx.loading.button(btn, () =>
        Lx.http.post(window.LX_ROUTES.empleadoBuscar, { numero_empleado: n, rfc: r }));
      empleado = res.empleado ?? res;
      fill(empleado);
      setOk(true);
      Lx.toast.ok('Empleado validado.');
    } catch (e) {
      clearResult();
      Lx.toast.error(e.message || 'No se pudo validar al empleado.');
    }
  }

  btn.addEventListener('click', buscar);
  rfc.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); buscar(); }
  });

  Lx.Wizard.registerStep(1, {
    validate() {
      if (!empleado) { Lx.toast.error('Primero valida al empleado con «Buscar».'); return false; }
      return true;
    },
    collect(state) { state.solicitante = empleado; },
    onEnter(state) {
      // Restore a prior match if the user navigates back.
      if (state.solicitante && !empleado) {
        empleado = state.solicitante;
        numero.value = empleado.numero_empleado ?? '';
        rfc.value = empleado.rfc ?? '';
        fill(empleado);
        setOk(true);
      }
    },
  });
}

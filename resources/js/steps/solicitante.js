/* ==========================================================================
   Step 1 · Datos del Solicitante
   Looks up an employee by número de empleado and fills the read-only fields.
   Registers the wizard hook so "Siguiente" is blocked until an employee
   is validated. Keeps the fetched record in central wizard state.
   ========================================================================== */

export function initSolicitante(Lx) {
  const panel = document.querySelector('[data-step="solicitante"]');
  if (!panel) return;

  const numero = panel.querySelector('#numero_empleado');
  const btn    = panel.querySelector('#btnBuscar');
  const wraps  = [numero.closest('.lx-input-wrap')];

  let empleado = null;
  let tarjetas = [];

  const fill = (data) => {
    panel.querySelectorAll('[data-emp]').forEach((el) => {
      el.value = data?.[el.dataset.emp] ?? '';
    });
  };
  const setOk = (on) => wraps.forEach((w) => w.classList.toggle('is-ok', on));
  const clearResult = () => { empleado = null; tarjetas = []; setOk(false); fill({}); };

  // Typing again invalidates the previous match.
  numero.addEventListener('input', clearResult);

  async function buscar() {
    const n = numero.value.trim();
    if (!n) { Lx.toast.error('Captura el número de empleado.'); return; }

    try {
      const res = await Lx.loading.button(btn, () =>
        Lx.http.post(window.LX_ROUTES.empleadoBuscar, { numero_empleado: n }));
      empleado = res.empleado ?? res;
      tarjetas = res.tarjetas ?? [];
      fill(empleado);
      setOk(true);
      Lx.toast.ok('Empleado validado.');
    } catch (e) {
      clearResult();
      Lx.toast.error(e.message || 'No se pudo validar al empleado.');
    }
  }

  btn.addEventListener('click', buscar);
  numero.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); buscar(); }
  });

  Lx.Wizard.registerStep('solicitante', {
    validate() {
      if (!empleado) { Lx.toast.error('Primero valida al empleado con «Buscar».'); return false; }
      return true;
    },
    collect(state) { state.solicitante = empleado; state.tarjetas = tarjetas; },
    onEnter(state) {
      // Restore a prior match if the user navigates back.
      if (state.solicitante && !empleado) {
        empleado = state.solicitante;
        tarjetas = state.tarjetas ?? [];
        numero.value = empleado.numero_empleado ?? '';
        fill(empleado);
        setOk(true);
      }
    },
  });
}

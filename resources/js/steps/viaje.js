/* ==========================================================================
   Step 2 · Datos del Viaje
   Captures the trip period, route, advance, cost center and transport.
   Computes "X días y Y noches" live, validates required fields, and pushes
   everything into central wizard state.
   ========================================================================== */

export function initViaje(Lx) {
  const panel = document.querySelector('[data-step="2"]');
  if (!panel) return;

  const el = (id) => panel.querySelector('#' + id);
  const salida  = el('fecha_salida');
  const regreso = el('fecha_regreso');
  const totalBox = el('totalDias');

  /* ---- live duration ---- */
  function computeDuration() {
    const a = salida.value ? new Date(salida.value + 'T00:00:00') : null;
    const b = regreso.value ? new Date(regreso.value + 'T00:00:00') : null;
    if (!a || !b || b < a) {
      return { noches: 0, dias: 0, text: b && b < a ? 'La fecha de regreso no puede ser anterior a la salida.' : null };
    }
    const noches = Math.round((b - a) / 86400000);
    const dias = noches + 1;
    const nochesTxt = noches === 1 ? 'noche' : 'noches';
    const diasTxt = dias === 1 ? 'día' : 'días';
    return { noches, dias, text: `Total: ${dias} ${diasTxt} y ${noches} ${nochesTxt}.` };
  }
  function renderDuration() {
    const d = computeDuration();
    const span = totalBox.querySelector('span');
    const bad = regreso.value && salida.value && new Date(regreso.value) < new Date(salida.value);
    span.textContent = d.text || 'Selecciona las fechas para calcular la duración.';
    totalBox.style.color = bad ? 'var(--lx-red)' : '';
    return d;
  }
  salida.addEventListener('change', () => { if (salida.value && !regreso.min) regreso.min = salida.value; renderDuration(); });
  regreso.addEventListener('change', renderDuration);

  /* ---- validation helpers ---- */
  const required = ['fecha_salida', 'fecha_regreso', 'origen', 'destino', 'centro_costos', 'medio_transporte'];
  function clearInvalid() { panel.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid')); }

  Lx.Wizard.registerStep(2, {
    onEnter(state) {
      // Prefill the cost center from the employee, if not already chosen.
      const cc = el('centro_costos');
      if (!cc.value && state.solicitante?.centro_costos) cc.value = state.solicitante.centro_costos;
      // For an anticipo, the amount is the key field — surface it.
      const montoWrap = el('monto_anticipo').closest('.col-md-6');
      if (montoWrap) {
        const lbl = montoWrap.querySelector('.form-label');
        if (lbl) lbl.innerHTML = state.flujo === 'anticipo'
          ? 'Monto del anticipo *'
          : 'Monto del anticipo';
      }
      // Restore prior values.
      if (state.viaje && Object.keys(state.viaje).length) {
        Lx.form.fill(panel, state.viaje);
        renderDuration();
      }
    },
    validate(state) {
      clearInvalid();
      for (const id of required) {
        const f = el(id);
        if (!f.value.trim()) {
          f.classList.add('is-invalid');
          f.focus();
          Lx.toast.error('Completa los campos obligatorios del viaje.');
          return false;
        }
      }
      if (new Date(regreso.value) < new Date(salida.value)) {
        regreso.classList.add('is-invalid'); regreso.focus();
        Lx.toast.error('La fecha de regreso no puede ser anterior a la salida.');
        return false;
      }
      // Anticipo: the requested amount is mandatory.
      if (state.flujo === 'anticipo' && !(parseFloat(el('monto_anticipo').value) > 0)) {
        el('monto_anticipo').classList.add('is-invalid'); el('monto_anticipo').focus();
        Lx.toast.error('Captura el monto del anticipo.');
        return false;
      }
      return true;
    },
    collect(state) {
      const d = computeDuration();
      state.viaje = {
        ...Lx.form.serialize(panel),
        dias: d.dias,
        noches: d.noches,
      };
    },
  });
}

/* ==========================================================================
   Step 2 · Datos del Viaje (per-flow)
   - Single flatpickr range field -> fecha_gasto_1 / fecha_gasto_2 (+ días/noches).
   - Fields and validations switch by flow (anticipo | comprobacion).
   - Banco from the seeded catalog; Tipo de Gasto from the static catalog.
   - Prefills CLABE/Banco/Sucursal/Centro from the looked-up employee.
   ========================================================================== */

import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

const FLOW = {
  anticipo: {
    hide:     ['folio_anticipo'],
    required: ['monto_anticipo', 'centro_costos', 'sucursal', 'clabe', 'banco', 'tipo_gasto'],
    optional: ['observaciones'],
    clabeDigits: true,
  },
  comprobacion: {
    hide:     ['monto_anticipo'],
    required: ['clabe', 'banco'],
    optional: ['folio_anticipo', 'tipo_gasto', 'centro_costos', 'sucursal', 'observaciones'],
    clabeDigits: false,
  },
};

export function initViaje(Lx) {
  const panel = document.querySelector('[data-step="2"]');
  if (!panel) return;

  const el = (id) => panel.querySelector('#' + id);
  const field = (name) => panel.querySelector(`[data-field="${name}"]`);
  const f1 = el('fecha_gasto_1');
  const f2 = el('fecha_gasto_2');
  const totalBox = el('totalDias');
  let flujo = 'comprobacion';

  /* ---- flatpickr range ---- */
  const fp = flatpickr(el('fecha_rango'), {
    mode: 'range',
    dateFormat: 'Y-m-d',
    locale: Spanish,
    onChange: (dates) => {
      f1.value = dates[0] ? fp.formatDate(dates[0], 'Y-m-d') : '';
      f2.value = dates[1] ? fp.formatDate(dates[1], 'Y-m-d') : '';
      renderDuration();
    },
  });

  function renderDuration() {
    const span = totalBox.querySelector('span');
    if (!f1.value || !f2.value) {
      span.textContent = 'Selecciona el rango para calcular la duración.';
      return { dias: 0, noches: 0 };
    }
    const a = new Date(f1.value + 'T00:00:00');
    const b = new Date(f2.value + 'T00:00:00');
    const noches = Math.max(0, Math.round((b - a) / 86400000));
    const dias = noches + 1;
    span.textContent = `Total: ${dias} ${dias === 1 ? 'día' : 'días'} y ${noches} ${noches === 1 ? 'noche' : 'noches'}.`;
    return { dias, noches };
  }

  /* ---- CLABE digits-only for anticipo ---- */
  el('clabe').addEventListener('input', (e) => {
    if (flujo === 'anticipo') e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
  });

  /* ---- per-flow field visibility + required labels ---- */
  function applyFlow(state) {
    flujo = state.flujo || 'comprobacion';
    const cfg = FLOW[flujo];
    const allFields = ['monto_anticipo', 'folio_anticipo', 'tipo_gasto', 'centro_costos', 'banco', 'clabe', 'sucursal', 'observaciones'];

    allFields.forEach((name) => {
      const wrap = field(name);
      if (!wrap) return;
      const hidden = cfg.hide.includes(name);
      wrap.hidden = hidden;
      const req = panel.querySelector(`[data-req="${name}"]`);
      if (req) {
        req.textContent = cfg.required.includes(name) ? '*'
          : (!hidden && cfg.optional.includes(name) ? '(opcional)' : '');
        req.classList.toggle('lx-opt', req.textContent === '(opcional)');
      }
    });
    // CLABE 18-digit hint only for anticipo.
    const hint = panel.querySelector('[data-hint="clabe"]');
    if (hint) hint.hidden = !cfg.clabeDigits;
  }

  function clearInvalid() { panel.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid')); }
  const fail = (node, msg) => { node.classList.add('is-invalid'); node.focus(); Lx.toast.error(msg); return false; };

  Lx.Wizard.registerStep(2, {
    onEnter(state) {
      applyFlow(state);
      // Prefill pago/centro from the employee if empty.
      const s = state.solicitante || {};
      if (!el('centro_costos').value && s.centro_costos) el('centro_costos').value = s.centro_costos;
      if (!el('banco').value && s.banco) el('banco').value = s.banco;
      if (!el('clabe').value && s.clabe) el('clabe').value = s.clabe;
      if (!el('sucursal').value && s.sucursal_cedis) el('sucursal').value = s.sucursal_cedis;
      // Restore prior values (incl. the date range).
      if (state.viaje && Object.keys(state.viaje).length) {
        Lx.form.fill(panel, state.viaje);
        if (state.viaje.fecha_gasto_1 && state.viaje.fecha_gasto_2) {
          fp.setDate([state.viaje.fecha_gasto_1, state.viaje.fecha_gasto_2], false);
        }
        renderDuration();
      }
    },
    validate(state) {
      clearInvalid();
      const cfg = FLOW[state.flujo] || FLOW.comprobacion;

      if (!f1.value || !f2.value) {
        return fail(el('fecha_rango'), 'Selecciona el rango de fechas del gasto.');
      }
      for (const name of cfg.required) {
        const node = el(name);
        if (node && !String(node.value).trim()) {
          return fail(node, 'Completa los campos obligatorios del viaje.');
        }
      }
      if (state.flujo === 'anticipo') {
        if (!(parseFloat(el('monto_anticipo').value) > 0)) return fail(el('monto_anticipo'), 'Captura el monto del anticipo.');
        if (!/^\d{18}$/.test(el('clabe').value)) return fail(el('clabe'), 'La CLABE debe tener 18 dígitos.');
      }
      return true;
    },
    collect(state) {
      const d = renderDuration();
      state.viaje = { ...Lx.form.serialize(panel), dias: d.dias, noches: d.noches };
    },
  });
}

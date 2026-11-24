/* ==========================================================================
   Step 2 · Datos del Viaje (per-flow)
   - Single flatpickr range field -> fecha_gasto_1 / fecha_gasto_2 (+ días/noches).
   - Fields and validations switch by flow (anticipo | comprobacion).
   - Banco from the seeded catalog; Tipo de Gasto from the static catalog.
   - Prefills CLABE/Banco/Sucursal/Centro from the looked-up employee.
   ========================================================================== */

import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
// flatpickr CSS is bundled in resources/css/app.css (linked, no FOUC).

const FLOW = {
  anticipo: {
    // Monto moves to the "Desglose" step; banco/referencia/moneda are hidden for anticipo.
    hide:     ['folio_anticipo', 'monto_anticipo', 'banco', 'referencia', 'moneda'],
    required: ['centro_costos', 'sucursal', 'clabe', 'tipo_gasto', 'fecha_dispersacion'],
    optional: ['observaciones'],
    clabeDigits: true,
  },
  comprobacion: {
    // Banco/referencia/moneda are hidden in both flows now (defaults sent server-side).
    hide:     ['monto_anticipo', 'fecha_dispersacion', 'banco', 'referencia', 'moneda'],
    required: ['clabe'],
    optional: ['folio_anticipo', 'tipo_gasto', 'centro_costos', 'sucursal', 'observaciones'],
    clabeDigits: false,
  },
};

export function initViaje(Lx) {
  const panel = document.querySelector('[data-step="viaje"]');
  if (!panel) return;

  const el = (id) => panel.querySelector('#' + id);
  const field = (name) => panel.querySelector(`[data-field="${name}"]`);
  const f1 = el('fecha_gasto_1');
  const f2 = el('fecha_gasto_2');
  const totalBox = el('totalDias');
  let flujo = 'comprobacion';
  const VIAJE_TIPO = 'Gastos de viaje (Comprobación)';
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

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

  /* ---- Folio de anticipo: shown + required only for "Gastos de viaje" (comprobación) ---- */
  function applyTipoGasto() {
    if (flujo !== 'comprobacion') return;
    const wrap = field('folio_anticipo');
    if (!wrap) return;
    const isViaje = el('tipo_gasto').value === VIAJE_TIPO;
    wrap.hidden = !isViaje;
    if (!isViaje) el('folio_anticipo').value = '';
    const req = panel.querySelector('[data-req="folio_anticipo"]');
    if (req) { req.textContent = isViaje ? '*' : ''; req.classList.remove('lx-opt'); }
  }
  el('tipo_gasto').addEventListener('change', applyTipoGasto);

  /* ---- Tarjeta (anticipo): 1 card auto-selected, many -> dropdown ---- */
  const cardLabel = (c) => `${c.nombre} · ${c.numero || '—'}${c.tipo ? ` (${c.tipo})` : ''}`;
  function setTarjetaHidden() {
    const sel = el('tarjeta');
    const c = (Lx.Wizard.state.tarjetas || []).find((k) => String(k.id) === sel.value);
    el('tarjeta_nombre').value = c ? c.nombre : '';
    el('tarjeta_numero').value = c ? (c.numero || '') : '';
  }
  function applyTarjetas(state) {
    const wrap = field('tarjeta');
    if (!wrap) return;
    const sel = el('tarjeta');
    const cards = (state.tarjetas || []).filter((t) => t && t.numero);
    if (flujo !== 'anticipo' || cards.length === 0) { wrap.hidden = true; return; }
    wrap.hidden = false;
    const cur = sel.value;
    if (cards.length === 1) {
      sel.innerHTML = `<option value="${cards[0].id}">${esc(cardLabel(cards[0]))}</option>`;
      sel.value = cards[0].id;
    } else {
      sel.innerHTML = '<option value="">— selecciona tarjeta —</option>' +
        cards.map((c) => `<option value="${c.id}">${esc(cardLabel(c))}</option>`).join('');
      sel.value = cur || '';
    }
    const req = panel.querySelector('[data-req="tarjeta"]');
    if (req) { req.textContent = '*'; req.classList.remove('lx-opt'); }
    setTarjetaHidden();
  }
  el('tarjeta').addEventListener('change', setTarjetaHidden);

  /* ---- per-flow field visibility + required labels ---- */
  function applyFlow(state) {
    flujo = state.flujo || 'comprobacion';
    const cfg = FLOW[flujo];
    const allFields = ['monto_anticipo', 'folio_anticipo', 'tipo_gasto', 'centro_costos', 'banco', 'clabe', 'sucursal', 'referencia', 'moneda', 'fecha_dispersacion', 'observaciones'];

    // "Sucursal" is "Destino / Concepto" in the anticipo flow. Rebuild the label
    // (keeping its required marker) before the loop fills the marker text.
    const sucLabel = panel.querySelector('[data-label="sucursal"]');
    if (sucLabel) {
      sucLabel.innerHTML = (flujo === 'anticipo' ? 'Destino / Concepto ' : 'Sucursal ')
        + '<span class="lx-req" data-req="sucursal"></span>';
    }

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

    applyTipoGasto();
    applyTarjetas(state);
  }

  function clearInvalid() { panel.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid')); }
  const fail = (node, msg) => { node.classList.add('is-invalid'); node.focus(); Lx.toast.error(msg); return false; };

  Lx.Wizard.registerStep('viaje', {
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
      if (state.flujo === 'comprobacion' && el('tipo_gasto').value === VIAJE_TIPO
          && !String(el('folio_anticipo').value).trim()) {
        return fail(el('folio_anticipo'), 'Captura el folio de anticipo para gastos de viaje.');
      }
      if (state.flujo === 'anticipo') {
        const cards = (state.tarjetas || []).filter((t) => t && t.numero);
        if (cards.length && !el('tarjeta').value) return fail(el('tarjeta'), 'Selecciona la tarjeta del anticipo.');
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

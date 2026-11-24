/* ==========================================================================
   Step · Desglose del anticipo (anticipo flow only)
   - Three fixed amount fields (alimentos, pasajes, peajes) + a repeater for
     "otros" (each line: amount + description).
   - Live total -> becomes state.viaje.monto_anticipo and state.desglose.
   - Blocks "Siguiente" until the total is > 0.
   ========================================================================== */

export function initDesglose(Lx) {
  const panel = document.querySelector('[data-step="desglose"]');
  if (!panel) return;

  const el = (id) => panel.querySelector('#' + id);
  const otrosWrap = el('dgOtros');
  const emptyNote = el('dgOtrosEmpty');
  const money = Lx.money;
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  const num = (v) => { const n = parseFloat(v); return Number.isFinite(n) && n > 0 ? n : 0; };

  function otroRow(monto = '', descripcion = '') {
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-center dg-otro';
    div.innerHTML = `
      <div class="col-5 col-md-4">
        <div class="input-group">
          <span class="input-group-text">$</span>
          <input type="number" class="form-control lx-mono dg-otro-amt" placeholder="0.00" step="0.01" min="0" value="${esc(monto)}">
        </div>
      </div>
      <div class="col">
        <input type="text" class="form-control dg-otro-desc" placeholder="Descripción del concepto" value="${esc(descripcion)}">
      </div>
      <div class="col-auto">
        <button type="button" class="lx-del dg-otro-del" aria-label="Quitar"><i class="fa-solid fa-trash-can"></i></button>
      </div>`;
    return div;
  }

  function syncEmpty() {
    emptyNote.hidden = otrosWrap.children.length > 0;
  }

  function total() {
    let t = num(el('dg_alimentos').value) + num(el('dg_pasajes').value) + num(el('dg_peajes').value);
    otrosWrap.querySelectorAll('.dg-otro-amt').forEach((i) => { t += num(i.value); });
    return +t.toFixed(2);
  }

  function renderTotal() {
    const t = total();
    el('dgTotal').textContent = money(t);
    const n = otrosWrap.children.length;
    el('dgResumen').textContent = n
      ? `Alimentos + pasajes + peajes + ${n} ${n === 1 ? 'otro' : 'otros'}`
      : 'Suma de todos los conceptos';
    return t;
  }

  function addOtro(monto, desc) {
    otrosWrap.appendChild(otroRow(monto, desc));
    syncEmpty();
    renderTotal();
  }

  el('dgAddOtro').addEventListener('click', () => addOtro());
  panel.addEventListener('input', (e) => { if (e.target.matches('.dg-amt, .dg-otro-amt')) renderTotal(); });
  otrosWrap.addEventListener('click', (e) => {
    const del = e.target.closest('.dg-otro-del');
    if (del) { del.closest('.dg-otro').remove(); syncEmpty(); renderTotal(); }
  });

  function collectOtros() {
    return [...otrosWrap.querySelectorAll('.dg-otro')].map((row) => ({
      monto: num(row.querySelector('.dg-otro-amt').value),
      descripcion: row.querySelector('.dg-otro-desc').value.trim(),
    })).filter((o) => o.monto > 0);
  }

  Lx.Wizard.registerStep('desglose', {
    onEnter(state) {
      // Restore prior values if the user navigates back.
      const d = state.desglose;
      if (d && !panel.dataset.filled) {
        el('dg_alimentos').value = d.alimentos || '';
        el('dg_pasajes').value = d.pasajes || '';
        el('dg_peajes').value = d.peajes || '';
        otrosWrap.innerHTML = '';
        (d.otros || []).forEach((o) => addOtro(o.monto, o.descripcion));
        panel.dataset.filled = '1';
      }
      syncEmpty();
      renderTotal();
    },
    validate() {
      if (!(total() > 0)) { Lx.toast.error('Captura al menos un monto en el desglose.'); return false; }
      return true;
    },
    collect(state) {
      const t = total();
      state.desglose = {
        alimentos: num(el('dg_alimentos').value),
        pasajes: num(el('dg_pasajes').value),
        peajes: num(el('dg_peajes').value),
        otros: collectOtros(),
        total: t,
      };
      // The anticipo "monto" is the desglose total.
      state.viaje = { ...(state.viaje || {}), monto_anticipo: t };
    },
  });
}

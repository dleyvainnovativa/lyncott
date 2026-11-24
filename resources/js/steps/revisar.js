/* ==========================================================================
   Step 4 · Revisar y Enviar
   - Fills the summary cards + totals from wizard state.
   - Requests the DomPDF preview (POST state -> PDF blob -> iframe).
   - Gates submit on the términos checkbox.
   - On submit: POSTs the full comprobación; shows the success panel.
   ========================================================================== */

export function initRevisar(Lx) {
  const panel = document.querySelector('[data-step="revisar"]');
  if (!panel) return;

  const q = (s) => panel.querySelector(s);
  const money = Lx.money;
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  let pdfUrls = [];   // object URLs to revoke on reload

  const setRv = (k, v) => { const el = panel.querySelector(`[data-rv="${k}"]`); if (el) el.textContent = v; };
  const setEx = (k, v) => { const el = panel.querySelector(`[data-ex="${k}"]`); if (el) el.textContent = v; };

  function fillSummary(state) {
    const s = state.solicitante || {};
    const v = state.viaje || {};
    const g = state.gastos || [];

    setRv('nombre', s.nombre_completo || '—');
    setRv('correo', s.correo || '—');
    setRv('numero', s.numero_empleado || '—');
    setRv('departamento', s.departamento || '—');
    setRv('puesto', s.puesto || '—');
    setRv('centro', v.centro_costos || s.centro_costos || '—');

    setRv('fecha1', v.fecha_gasto_1 || '—');
    setRv('fecha2', v.fecha_gasto_2 || '—');
    setRv('duracion', v.dias ? `${v.dias} días y ${v.noches} noches` : '—');
    setRv('tipo', v.tipo_gasto || '—');
    setRv('banco', v.banco || s.banco || '—');
    setRv('clabe', v.clabe || '—');
    setRv('vcentro', v.centro_costos || '—');
    setRv('sucursal', v.sucursal || '—');
    setRv('folio', v.folio_anticipo || '—');

    const cf = g.filter((r) => r.tipo === 'cf').reduce((a, r) => a + (+r.total || 0), 0);
    const sf = g.filter((r) => r.tipo === 'sf').reduce((a, r) => a + (+r.total || 0), 0);
    setRv('tCf', money(cf));
    setRv('tSf', money(sf));
    setRv('tTot', money(cf + sf));
    setRv('tAnt', money(v.monto_anticipo || 0));
  }

  // Toggle sections by flow. Both flows now get a PDF preview.
  function applyFlow(state) {
    const anticipo = state.flujo === 'anticipo';
    panel.querySelector('#rvAnticipo').hidden = !anticipo;   // monto tile (anticipo)
    panel.querySelector('#rvTotales').hidden = anticipo;     // gastos totals (comprobación)
    panel.querySelector('[data-rv-wrap="folio"]').hidden = anticipo; // folio row only for comprobación
    // Anticipo review omits banco / departamento / puesto.
    ['banco', 'departamento', 'puesto'].forEach((k) => {
      const w = panel.querySelector(`[data-rv-wrap="${k}"]`);
      if (w) w.hidden = anticipo;
    });
  }

  // Strip base64 before asking for the preview (keeps the request light).
  const lightGastos = (gastos) => (gastos || []).map(({ xmlB64, pdfB64, ...rest }) => rest);

  // Group gastos by card using the SAME key the server uses in enviar():
  // tarjeta_numero (trimmed) or 'sin-tarjeta'. Each group becomes one PDF tab,
  // mirroring the one-comprobación-per-card submission.
  function groupByCard(gastos) {
    const groups = new Map();
    for (const g of (gastos || [])) {
      const num = String(g.tarjeta_numero || '').trim();
      const key = num || 'sin-tarjeta';
      if (!groups.has(key)) {
        const nombre = g.tarjeta_nombre || '';
        groups.set(key, { key, nombre, num, label: num ? (nombre || 'Tarjeta') : 'Sin tarjeta', gastos: [] });
      }
      groups.get(key).gastos.push(g);
    }
    return [...groups.values()];
  }

  // Fetch one preview PDF for a given set of gastos; returns an object URL.
  async function fetchPdf(state, gastos) {
    const res = await fetch(window.LX_ROUTES.comprobacionPdf, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/pdf',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ flujo: state.flujo, solicitante: state.solicitante, viaje: state.viaje, gastos: lightGastos(gastos), desglose: state.desglose || {} }),
    });
    if (!res.ok) throw new Error('pdf');
    const url = URL.createObjectURL(await res.blob());
    pdfUrls.push(url);
    return url;
  }

  // Render a single preview into a wrapper (spinner -> iframe).
  async function loadInto(state, gastos, wrap) {
    wrap.innerHTML = '<div class="lx-empty"><i class="fa-solid fa-spinner fa-spin me-2"></i>Generando vista previa…</div>'
      + '<iframe class="lx-pdf-frame" title="Vista previa del PDF" hidden></iframe>';
    const loading = wrap.querySelector('.lx-empty');
    const frame = wrap.querySelector('iframe');
    try {
      frame.src = await fetchPdf(state, gastos);
      frame.hidden = false;
      loading.hidden = true;
    } catch {
      loading.innerHTML = '<span style="color:var(--lx-red)">No se pudo generar la vista previa del PDF.</span>';
    }
  }

  async function loadPdf(state) {
    const wrap = q('#pdfWrap');
    pdfUrls.forEach(URL.revokeObjectURL);
    pdfUrls = [];

    // Anticipo (single summary PDF) and single-card comprobación: no tabs.
    const groups = state.flujo === 'anticipo' ? [] : groupByCard(state.gastos);
    if (groups.length <= 1) {
      await loadInto(state, groups[0] ? groups[0].gastos : (state.gastos || []), wrap);
      return;
    }

    // Multiple cards: one tab + one lazy-loaded pane per card.
    const tabs = groups.map((g, i) =>
      `<button type="button" class="lx-pdftab ${i === 0 ? 'is-on' : ''}" data-tab="${i}">
         <i class="fa-regular fa-credit-card"></i> ${esc(g.label)}
         ${g.num ? `<span class="lx-pdftab-num">${esc(g.num)}</span>` : ''}
       </button>`).join('');
    const panes = groups.map((g, i) =>
      `<div class="lx-pdfpane" data-pane="${i}" style="position:relative;min-height:220px" ${i === 0 ? '' : 'hidden'}></div>`).join('');
    wrap.innerHTML = `<div class="lx-pdftabs" role="tablist">${tabs}</div>${panes}`;

    const loaded = new Set();
    const activate = async (i) => {
      wrap.querySelectorAll('.lx-pdftab').forEach((t) => t.classList.toggle('is-on', String(t.dataset.tab) === String(i)));
      wrap.querySelectorAll('.lx-pdfpane').forEach((p) => { p.hidden = String(p.dataset.pane) !== String(i); });
      if (!loaded.has(i)) {
        loaded.add(i);
        await loadInto(state, groups[i].gastos, wrap.querySelector(`.lx-pdfpane[data-pane="${i}"]`));
      }
    };
    wrap.querySelector('.lx-pdftabs').addEventListener('click', (e) => {
      const b = e.target.closest('.lx-pdftab');
      if (b) activate(+b.dataset.tab);
    });
    await activate(0);   // first tab eager; the rest load on click
  }

  Lx.Wizard.registerStep('revisar', {
    onEnter(state) {
      fillSummary(state);
      applyFlow(state);
      loadPdf(state);   // both flows now preview a PDF (detalle / anticipo)
    },
    validate() {
      if (!q('#terminos').checked) {
        Lx.toast.error('Debes aceptar que la información es correcta.');
        return false;
      }
      return true;
    },
  });

  // Final submit (wizard calls this after the last step validates).
  Lx.Wizard.onSubmit = async (state) => {
    try {
      const res = await Lx.http.post(window.LX_ROUTES.comprobacionEnviar, {
        terminos: true,
        flujo: state.flujo,
        solicitante: state.solicitante,
        viaje: state.viaje,
        gastos: state.flujo === 'anticipo' ? [] : state.gastos,
        desglose: state.flujo === 'anticipo' ? (state.desglose || {}) : null,
      });

      const esAnticipo = state.flujo === 'anticipo';
      setEx('message', res.message || '');
      // For anticipo show the generated folio (ANT-…) the comprobación will reference later.
      setEx('folio', (esAnticipo && res.folio_anticipo) ? res.folio_anticipo : (res.folio || '—'));
      setEx('total', money(esAnticipo ? (res.resumen?.anticipo || 0) : (res.resumen?.total || 0)));
      panel.querySelector('[data-ex-label="total"]').textContent = esAnticipo ? 'Monto del anticipo' : 'Total comprobación';
      setEx('payload', `#${res.payload_id} · ${res.live ? (res.sent ? 'enviado' : 'no enviado') : 'modo demo (no enviado)'}`);
      setEx('files', `${res.file_count} adjunto(s)`);

      q('#revisarContenido').hidden = true;
      q('#revisarFoot').hidden = true;
      q('#revisarExito').hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
      Lx.toast.ok('Comprobación registrada.');
    } catch (e) {
      Lx.toast.error(e.message || 'No se pudo enviar la comprobación.');
      throw e; // keep the button state honest
    }
  };
}

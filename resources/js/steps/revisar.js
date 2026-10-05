/* ==========================================================================
   Step 4 · Revisar y Enviar
   - Fills the summary cards + totals from wizard state.
   - Requests the DomPDF preview (POST state -> PDF blob -> iframe).
   - Gates submit on the términos checkbox.
   - On submit: POSTs the full comprobación; shows the success panel.
   ========================================================================== */

export function initRevisar(Lx) {
  const panel = document.querySelector('[data-step="4"]');
  if (!panel) return;

  const q = (s) => panel.querySelector(s);
  const money = Lx.money;
  let pdfUrl = null;

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
    setRv('banco', v.banco || '—');
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
  }

  // Strip base64 before asking for the preview (keeps the request light).
  function lightGastos(state) {
    return (state.gastos || []).map(({ xmlB64, pdfB64, ...rest }) => rest);
  }

  async function loadPdf(state) {
    const loading = q('#pdfLoading');
    const frame = q('#pdfPreview');
    loading.hidden = false;
    frame.hidden = true;
    try {
      const res = await fetch(window.LX_ROUTES.comprobacionPdf, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/pdf',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ flujo: state.flujo, solicitante: state.solicitante, viaje: state.viaje, gastos: lightGastos(state) }),
      });
      if (!res.ok) throw new Error('pdf');
      const blob = await res.blob();
      if (pdfUrl) URL.revokeObjectURL(pdfUrl);
      pdfUrl = URL.createObjectURL(blob);
      frame.src = pdfUrl;
      frame.hidden = false;
      loading.hidden = true;
    } catch {
      loading.innerHTML = '<span style="color:var(--lx-red)">No se pudo generar la vista previa del PDF.</span>';
    }
  }

  Lx.Wizard.registerStep(4, {
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
      });

      const esAnticipo = state.flujo === 'anticipo';
      setEx('message', res.message || '');
      setEx('folio', res.folio || '—');
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

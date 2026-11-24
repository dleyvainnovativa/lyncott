/* ==========================================================================
   Step 3 · Gestión de gastos
   - Drop / pick CFDI 4.0 XML -> parse in-browser -> build a row
     (fecha, importe, emisor RFC+nombre, UUID), suggest an editable categoría.
   - Manual "sin factura" rows via the modal.
   - XML (and optional PDF) kept as base64 data URIs on each con-factura row,
     so Phase 5 can emit the per-gasto file attributes.
   - Live Con/Sin factura + Total tiles; search; delete.
   - Blocks "Siguiente" until there is at least one gasto.
   ========================================================================== */

export function initGastos(Lx) {
  const panel = document.querySelector('[data-step="gastos"]');
  if (!panel) return;

  // Category catalogs (client-defined):
  //  - con factura: keyed by the comprobación's Tipo de Gasto.
  //  - sin factura: one independent flat list (no tipo dependency).
  const RAW = window.LX_CATS || {};
  const CON = (RAW.con_factura && typeof RAW.con_factura === 'object') ? RAW.con_factura : {};
  const SIN = Array.isArray(RAW.sin_factura) ? RAW.sin_factura : [];
  const FALLBACK = ['HOTEL', 'TRANSPORTACION AEREA', 'ALIMENTOS', 'VARIOS'];

  const tipoGasto = () => (Lx.Wizard.state.viaje && Lx.Wizard.state.viaje.tipo_gasto) || '';
  const conList = () => CON[tipoGasto()] || Object.values(CON)[0] || FALLBACK;
  // Options for a given row: sin-factura -> flat list; con-factura -> tipo catalog.
  const catsFor = (r) => (r.tipo === 'sf' ? (SIN.length ? SIN : FALLBACK) : conList());

  let data = [];
  let ID = 0;
  let lastTipo = null;   // tracks the Tipo de Gasto the rows were categorized under

  const q  = (sel) => panel.querySelector(sel);
  const rowsEl   = q('#lxRows');
  const cardTh   = q('.js-card-col');
  const searchEl = q('#lxSearch');
  const dropEl   = q('#lxDrop');
  const xmlInput = q('#lxXmlInput');

  const money = Lx.money;
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const ivaOf = (r) => (r.tipo === 'cf' ? +(r.total - r.total / 1.16).toFixed(2) : 0);

  /* ---- tarjetas assigned to the logged-in employee (from the lookup) ---- */
  const cards = () => (Lx.Wizard.state.tarjetas || []).filter((t) => t && t.numero);
  // Full card label: name + number (+ type), so the whole card data is visible.
  const cardLabel = (c) => `${c.nombre} · ${c.numero || '—'}${c.tipo ? ` (${c.tipo})` : ''}`;
  function cardCell(r, cs) {
    if (cs.length === 1) {
      const c = cs[0];
      return `<td data-label="Tarjeta"><span class="lx-cardlbl" title="${esc(c.numero || '')}">${esc(cardLabel(c))}</span></td>`;
    }
    const opts = '<option value="">— selecciona tarjeta —</option>' +
      cs.map((c) => `<option value="${c.id}" ${String(r.tarjeta_id) === String(c.id) ? 'selected' : ''}>${esc(cardLabel(c))}</option>`).join('');
    return `<td data-label="Tarjeta"><select class="lx-tarjeta form-select form-select-sm lx-cardsel" data-id="${r.id}" aria-label="Tarjeta">${opts}</select></td>`;
  }

  /* ---- fiscal validation (Venvers, via the server-side proxy) ---- */
  const valCache = new Map(); // uuid -> normalized result (avoid re-registering a comprobante)

  function valCell(r) {
    const hasXml = r.tipo === 'cf' && !!r.xmlB64;
    if (!hasXml) return '<td data-label="Validación" class="lx-tnum"><span class="lx-val lx-val-na">N/A</span></td>';
    const v = r.val || { status: 'idle' };
    if (v.status === 'loading') {
      return '<td data-label="Validación"><span class="lx-val lx-val-loading"><i class="fa-solid fa-spinner fa-spin"></i> Validando…</span></td>';
    }
    if (v.status === 'valido' || v.status === 'invalido') {
      const dup = v.data && v.data.duplicado;
      const cls = v.status === 'valido' ? (dup ? 'lx-val-warn' : 'lx-val-ok') : 'lx-val-bad';
      const ico = v.status === 'valido' ? (dup ? 'fa-triangle-exclamation' : 'fa-circle-check') : 'fa-circle-xmark';
      const txt = v.status === 'valido' ? (dup ? 'Duplicado' : 'Válido') : 'Inválido';
      return `<td data-label="Validación"><button type="button" class="lx-val lx-valchip ${cls}" data-valshow="${r.id}" title="Ver detalle"><i class="fa-solid ${ico}"></i> ${txt}</button></td>`;
    }
    if (v.status === 'error') {
      // Passive chip — re-validation happens only through "Validar todas".
      return `<td data-label="Validación"><span class="lx-val lx-valchip lx-val-bad" title="${esc(v.msg || 'Error')}"><i class="fa-solid fa-circle-exclamation"></i> Error</span></td>`;
    }
    // Idle: passive "Pendiente" chip (no row-level trigger; use "Validar todas").
    return `<td data-label="Validación"><span class="lx-val lx-val-na"><i class="fa-regular fa-clock"></i> Pendiente</span></td>`;
  }

  // A cleanly-validated invoice (válido, not duplicado) is locked and cannot be removed.
  const isLocked = (r) => !!(r.val && r.val.status === 'valido' && !(r.val.data && r.val.data.duplicado));

  async function validarRow(id, { silent = false } = {}) {
    const r = data.find((x) => String(x.id) === String(id));
    if (!r || r.tipo !== 'cf' || !r.xmlB64) return null;

    if (r.uuid && valCache.has(r.uuid)) {
      const cached = valCache.get(r.uuid);
      r.val = { status: cached.status, data: cached };
      render(); return null;
    }

    r.val = { status: 'loading' }; render();
    try {
      const res = await Lx.http.post(window.LX_ROUTES.cfdiValidar, { xmlB64: r.xmlB64, uuid: r.uuid || '' });
      r.val = { status: res.status === 'valido' ? 'valido' : 'invalido', data: res };
      if (r.uuid) valCache.set(r.uuid, res);
      render(); return null;
    } catch (e) {
      r.val = { status: 'error', msg: e.message || 'No se pudo validar.' };
      if (!silent) Lx.toast.error(e.message || 'No se pudo validar el comprobante.');
      render(); return e;
    }
  }

  async function validarTodas() {
    const pend = data.filter((r) => r.tipo === 'cf' && r.xmlB64 &&
      (!r.val || r.val.status === 'idle' || r.val.status === 'error'));
    if (!pend.length) { Lx.toast.ok('No hay comprobantes pendientes por validar.'); return; }

    const btn = panel.querySelector('#btnValidarTodas');
    let firstErr = null;
    let i = 0;
    const worker = async () => {
      while (i < pend.length) {
        const r = pend[i++];
        const err = await validarRow(r.id, { silent: true });
        if (err && !firstErr) firstErr = err;
      }
    };
    await Lx.loading.button(btn, () => Promise.all([worker(), worker(), worker()]));
    if (firstErr) Lx.toast.error(firstErr.message || 'Algunos comprobantes no se pudieron validar.');
  }

  const VAL_IC = { ok: 'fa-circle-check ic-ok', fail: 'fa-circle-xmark ic-fail', na: 'fa-minus ic-na' };
  function showValidacion(id) {
    const r = data.find((x) => String(x.id) === String(id));
    if (!r || !r.val || !r.val.data) return;
    const d = r.val.data;
    const head = panel.querySelector('#valHead');
    const body = panel.querySelector('#valBody');
    const chipCls = d.status === 'valido' ? (d.duplicado ? 'lx-val-warn' : 'lx-val-ok') : 'lx-val-bad';
    const chipTxt = d.status === 'valido' ? (d.duplicado ? 'Válido (duplicado)' : 'Válido fiscal') : 'No válido';
    head.innerHTML = `<span class="lx-valchip ${chipCls}">${esc(chipTxt)}</span>
      ${d.mensaje ? `<div class="mt-2" style="font-size:.82rem;color:var(--lx-muted)">${esc(d.mensaje)}</div>` : ''}`;
    body.innerHTML = (d.checks || []).map((c) => `
      <div class="lx-valrow">
        <i class="fa-solid ${VAL_IC[c.estado] || VAL_IC.na}"></i>
        <div><div class="vn">${esc(c.validacion)} <small style="color:var(--lx-faint)">· ${esc(c.codigo)}</small></div>
          ${c.explicacion ? `<div class="vd">${esc(c.explicacion)}</div>` : ''}</div>
      </div>`).join('');
    Lx.modal.show('modalValidacion');
  }

  /* ---- categoría auto-suggest (demo heuristic; real map in config/smartker.php) ---- */
  function suggest(name, rfc) {
    const s = ((name || '') + ' ' + (rfc || '')).toUpperCase();
    if (/AERO|VUELA|VOLARIS|AEROVIAS|AEROENLACES|VIVA|INTERJET|ABC AEROLINEAS/.test(s)) return pick('AEREA');
    if (/HOTEL|HOTELERA|POSADAS|INN|FIESTA|CITY CENTER|GALERIAS/.test(s))              return pick('HOTEL');
    if (/TAXI|UBER|DIDI|CABIFY/.test(s))                                                return pick('TAXI');
    if (/CASETA|AUTOPISTA|CAPUFE|IAVE|PASE|PEAJE/.test(s))                              return pick('CASETA');
    if (/AUTOBUS|\bADO\b|ESTACIONAMIENTO|PARKING|PENSION|ESTRELLA/.test(s))             return pick('AUTOBUS');
    if (/RESTAUR|ALIMENT|CAFE|COMEDOR|TAQUE|COCINA/.test(s))                            return pick('ALIMENT');
    return '';
  }
  // Match a keyword to an entry in the active con-factura catalog (auto-suggest for XML rows).
  const pick = (kw) => conList().find((c) => c.toUpperCase().includes(kw)) || '';

  /* ---- file helpers ---- */
  const fileToDataUri = (file) => new Promise((res, rej) => {
    const r = new FileReader();
    r.onload = () => res(r.result);
    r.onerror = rej;
    r.readAsDataURL(file);
  });
  const dataUriToText = (uri) => {
    const b64 = (uri.split(',')[1]) || '';
    const bytes = Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
    return new TextDecoder('utf-8').decode(bytes);
  };

  /* ---- CFDI 4.0 parse ---- */
  const localEls = (doc, name) => [...doc.getElementsByTagName('*')].filter((e) => e.localName === name);
  function parseCFDI(text) {
    const doc = new DOMParser().parseFromString(text, 'application/xml');
    if (doc.querySelector('parsererror')) throw { bad: true };
    const comp = localEls(doc, 'Comprobante')[0];
    if (!comp) throw { bad: true };
    const ver = comp.getAttribute('Version') || comp.getAttribute('version');
    if (ver !== '4.0') throw { v: ver || '¿?' };
    const em  = localEls(doc, 'Emisor')[0];
    const tfd = localEls(doc, 'TimbreFiscalDigital')[0];
    return {
      total: parseFloat(comp.getAttribute('Total') || '0'),
      fecha: (comp.getAttribute('Fecha') || '').slice(0, 10),
      co:    em?.getAttribute('Nombre') || '(emisor s/n)',
      rfc:   em?.getAttribute('Rfc') || '',
      uuid:  tfd?.getAttribute('UUID') || '',
    };
  }

  async function addFromXmlFile(file, pdfFile = null) {
    let uri;
    try { uri = await fileToDataUri(file); }
    catch { Lx.toast.error(`No se pudo leer ${file.name}.`); return; }

    let x;
    try { x = parseCFDI(dataUriToText(uri)); }
    catch (err) {
      if (err && err.v) Lx.toast.error(`CFDI ${err.v} no soportado. Se requiere CFDI 4.0.`);
      else Lx.toast.error(`${file.name}: XML no válido o no es un CFDI.`);
      return;
    }

    let pdfB64 = null, pdfName = null;
    if (pdfFile) {
      try { pdfB64 = await fileToDataUri(pdfFile); pdfName = pdfFile.name; } catch {}
    }

    const cat = suggest(x.co, x.rfc);
    data.push({
      id: ++ID, tipo: 'cf', ...x, cat, auto: !!cat,
      justificacion: '', xmlName: file.name, xmlB64: uri, pdfName, pdfB64,
    });
    render();
    Lx.toast.ok(`${file.name}: ${money(x.total)} — ${x.co}`);
  }

  function readXmlFiles(fileList) {
    [...fileList]
      .filter((f) => /\.xml$/i.test(f.name) || f.type.includes('xml'))
      .forEach((f) => addFromXmlFile(f));
  }

  /* ---- render ---- */
  function render() {
    const needle = searchEl.value.trim().toLowerCase();
    const list = data.filter((r) => !needle ||
      ((r.co + r.rfc + r.cat).toLowerCase().includes(needle)));

    const cs = cards();
    // Show the Tarjeta column only when there's a real choice (more than one card).
    const showCard = cs.length > 1;
    if (cardTh) cardTh.hidden = !showCard;
    // One card -> auto-assign the same card to every row.
    if (cs.length === 1) data.forEach((r) => {
      if (!r.tarjeta_id) { r.tarjeta_id = cs[0].id; r.tarjeta_nombre = cs[0].nombre; r.tarjeta_numero = cs[0].numero; }
    });

    rowsEl.innerHTML = list.length ? list.map((r) => {
      const catList = catsFor(r);
      const offList = r.cat && !catList.includes(r.cat);
      const opts = (r.cat
          ? (offList ? `<option value="${esc(r.cat)}" selected>${esc(r.cat)}</option>` : '')
          : '<option value="" selected>— selecciona —</option>') +
        catList.map((c) => `<option ${c === r.cat ? 'selected' : ''}>${esc(c)}</option>`).join('');
      const chips = r.tipo === 'cf' ? `
        <div class="mt-1 d-flex gap-1">
          <span class="lx-pill lx-pill--cf" title="${esc(r.xmlName || '')}"><i class="fa-solid fa-code"></i> XML</span>
          <span class="lx-pill ${r.pdfB64 ? 'lx-pill--cf' : 'lx-pill--sf'}" title="${esc(r.pdfName || 'Sin PDF')}"><i class="fa-regular fa-file-pdf"></i> PDF</span>
        </div>` : '';
      return `<tr>
        <td data-label="Tipo"><span class="lx-pill ${r.tipo === 'cf' ? 'lx-pill--cf' : 'lx-pill--sf'}">
          <i class="fa-solid ${r.tipo === 'cf' ? 'fa-circle-check' : 'fa-circle'}"></i>
          ${r.tipo === 'cf' ? 'Con factura' : 'Sin factura'}</span></td>
        <td data-label="Fecha" class="lx-tnum">${esc(r.fecha)}</td>
        <td data-label="Emisor"><span class="lx-co">${esc(r.co)}${r.rfc ? `<small>${esc(r.rfc)}</small>` : ''}</span>
          ${r.uuid ? `<div class="lx-uuid">${esc(r.uuid)}</div>` : ''}${chips}</td>
        <td data-label="Categoría">
          <select class="lx-cat ${r.auto ? 'is-auto' : ''}" data-id="${r.id}" aria-label="Categoría">${opts}</select>
          ${r.auto ? '<div class="lx-autobadge"><i class="fa-solid fa-wand-magic-sparkles"></i> auto</div>' : ''}</td>
        ${showCard ? cardCell(r, cs) : ''}
        <td data-label="Importe" class="lx-num">${money(r.total)}</td>
        <td data-label="IVA" class="lx-num" style="color:var(--lx-muted)">${money(ivaOf(r))}</td>
        ${valCell(r)}
        <td class="lx-actcell">${isLocked(r)
          ? '<span class="lx-del is-locked" title="Comprobante validado: no se puede eliminar"><i class="fa-solid fa-lock"></i></span>'
          : `<button type="button" class="lx-del" data-del="${r.id}" aria-label="Eliminar"><i class="fa-solid fa-trash-can"></i></button>`}</td>
      </tr>`;
    }).join('') : `<tr><td colspan="${showCard ? 9 : 8}" class="lx-empty">Aún no hay gastos. Arrastra un XML o agrega un gasto sin factura.</td></tr>`;

    totals();
    panel.querySelector('[data-tile="count"]').textContent = `${data.length} ${data.length === 1 ? 'gasto' : 'gastos'}`;
  }

  function totals() {
    const cf = data.filter((r) => r.tipo === 'cf');
    const sf = data.filter((r) => r.tipo === 'sf');
    const cfSum = cf.reduce((a, r) => a + r.total, 0);
    const sfSum = sf.reduce((a, r) => a + r.total, 0);
    const iva   = cf.reduce((a, r) => a + ivaOf(r), 0);
    const set = (k, v) => { const el = panel.querySelector(`[data-tile="${k}"]`); if (el) el.textContent = v; };
    set('cf', money(cfSum));   set('cf-sub', `${cf.length} gastos · IVA ${money(iva)}`);
    set('sf', money(sfSum));   set('sf-sub', `${sf.length} gastos`);
    set('tot', money(cfSum + sfSum)); set('tot-sub', `${data.length} líneas en total`);
  }

  /* ---- table interactions ---- */
  rowsEl.addEventListener('click', (e) => {
    const b = e.target.closest('[data-del]');
    if (b) {
      const r = data.find((x) => String(x.id) === b.dataset.del);
      if (r && isLocked(r)) { Lx.toast.error('El comprobante está validado y no se puede eliminar.'); return; }
      data = data.filter((x) => String(x.id) !== b.dataset.del); render(); return;
    }
    const vs = e.target.closest('[data-valshow]');
    if (vs) { showValidacion(vs.dataset.valshow); return; }
  });

  panel.querySelector('#btnValidarTodas')?.addEventListener('click', validarTodas);
  rowsEl.addEventListener('change', (e) => {
    const s = e.target.closest('.lx-cat');
    if (s) { const r = data.find((x) => String(x.id) === s.dataset.id); if (r) { r.cat = s.value; r.auto = false; } render(); return; }
    const tc = e.target.closest('.lx-tarjeta');
    if (tc) {
      const r = data.find((x) => String(x.id) === tc.dataset.id);
      if (r) {
        const c = cards().find((k) => String(k.id) === tc.value);
        r.tarjeta_id = c ? c.id : null;
        r.tarjeta_nombre = c ? c.nombre : null;
        r.tarjeta_numero = c ? c.numero : null;
      }
    }
  });
  searchEl.addEventListener('input', render);

  /* ---- drop zone ---- */
  xmlInput.addEventListener('change', (e) => { readXmlFiles(e.target.files); xmlInput.value = ''; });
  ['dragenter', 'dragover'].forEach((ev) => dropEl.addEventListener(ev, (e) => {
    e.preventDefault(); dropEl.classList.add('is-over');
  }));
  ['dragleave', 'drop'].forEach((ev) => dropEl.addEventListener(ev, (e) => {
    e.preventDefault();
    if (ev === 'dragleave' && dropEl.contains(e.relatedTarget)) return;
    dropEl.classList.remove('is-over');
  }));
  dropEl.addEventListener('drop', (e) => { if (e.dataTransfer.files.length) readXmlFiles(e.dataTransfer.files); });

  /* ---- modal ---- */
  let mode = 'sf';
  const seg = q('#segTipo');
  const sfFields = q('#sfFields');
  const cfFields = q('#cfFields');
  const mCat = q('#mCat');
  // The add-gasto modal captures "sin factura" rows -> independent flat list.
  mCat.innerHTML = (SIN.length ? SIN : FALLBACK).map((c) => `<option>${esc(c)}</option>`).join('');

  q('#btnAddGasto').addEventListener('click', () => {
    q('#mFecha').value = new Date().toISOString().slice(0, 10);
    Lx.modal.show('modalGasto');
  });
  seg.addEventListener('click', (e) => {
    const b = e.target.closest('button'); if (!b) return;
    mode = b.dataset.m;
    seg.querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b));
    sfFields.hidden = mode !== 'sf';
    cfFields.hidden = mode !== 'cf';
  });

  const btnGuardar = q('#btnGuardarGasto');
  btnGuardar.addEventListener('click', async () => {
    if (mode === 'cf') {
      const xf = q('#mXml').files[0];
      if (!xf) { Lx.toast.error('Selecciona un archivo XML.'); return; }
      await Lx.loading.button(btnGuardar, () => addFromXmlFile(xf, q('#mPdf').files[0] || null));
      Lx.modal.hide('modalGasto');
      q('#mXml').value = ''; q('#mPdf').value = '';
      return;
    }
    const costo = parseFloat(q('#mCosto').value || '0');
    if (!costo) { Lx.toast.error('Captura el costo del gasto.'); return; }
    data.push({
      id: ++ID, tipo: 'sf', fecha: q('#mFecha').value,
      co: q('#mCompania').value || 'Gasto sin factura', rfc: '', uuid: '',
      total: costo, cat: mCat.value, auto: false,
      justificacion: q('#mJustificacion').value || '', xmlName: null, xmlB64: null, pdfName: null, pdfB64: null,
    });
    render();
    Lx.modal.hide('modalGasto');
    q('#mCosto').value = ''; q('#mCompania').value = ''; q('#mJustificacion').value = '';
  });

  /* ---- wizard hook ---- */
  Lx.Wizard.registerStep('gastos', {
    onEnter(state) {
      if (Array.isArray(state.gastos) && state.gastos.length && !data.length) {
        data = state.gastos;
        ID = data.reduce((m, r) => Math.max(m, r.id || 0), 0);
      }
      // If the Tipo de Gasto changed, the con-factura catalog changed too:
      // clear each invoice (con factura) row's category so its select refreshes.
      const t = tipoGasto();
      if (lastTipo !== null && t !== lastTipo) {
        data.forEach((r) => { if (r.tipo === 'cf') { r.cat = ''; r.auto = false; } });
      }
      lastTipo = t;
      render();
    },
    validate() {
      if (!data.length) { Lx.toast.error('Agrega al menos un gasto para continuar.'); return false; }
      if (cards().length > 1 && data.some((r) => !r.tarjeta_id)) {
        Lx.toast.error('Selecciona la tarjeta para cada gasto.'); return false;
      }
      return true;
    },
    collect(state) { state.gastos = data; },
  });

  render();
}

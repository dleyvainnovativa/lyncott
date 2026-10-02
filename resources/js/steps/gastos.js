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
  const panel = document.querySelector('[data-step="2"]');
  if (!panel) return;

  const CATS = Array.isArray(window.LX_CATS) && window.LX_CATS.length
    ? window.LX_CATS
    : ['HOTEL', 'TRANSPORTACION AEREA', 'ALIMENTOS', 'VARIOS (Herramienta o material)'];

  let data = [];
  let ID = 0;

  const q  = (sel) => panel.querySelector(sel);
  const rowsEl   = q('#lxRows');
  const searchEl = q('#lxSearch');
  const dropEl   = q('#lxDrop');
  const xmlInput = q('#lxXmlInput');

  const money = Lx.money;
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const ivaOf = (r) => (r.tipo === 'cf' ? +(r.total - r.total / 1.16).toFixed(2) : 0);

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
  // Match a keyword to whichever catalog entry contains it (keeps us in sync with config).
  const pick = (kw) => CATS.find((c) => c.toUpperCase().includes(kw)) || '';

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

    rowsEl.innerHTML = list.length ? list.map((r) => {
      const opts = (r.cat ? '' : '<option value="" selected>— selecciona —</option>') +
        CATS.map((c) => `<option ${c === r.cat ? 'selected' : ''}>${esc(c)}</option>`).join('');
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
        <td data-label="Importe" class="lx-num">${money(r.total)}</td>
        <td data-label="IVA" class="lx-num" style="color:var(--lx-muted)">${money(ivaOf(r))}</td>
        <td class="lx-actcell"><button type="button" class="lx-del" data-del="${r.id}" aria-label="Eliminar"><i class="fa-solid fa-trash-can"></i></button></td>
      </tr>`;
    }).join('') : `<tr><td colspan="7" class="lx-empty">Aún no hay gastos. Arrastra un XML o agrega un gasto sin factura.</td></tr>`;

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
    if (b) { data = data.filter((r) => String(r.id) !== b.dataset.del); render(); }
  });
  rowsEl.addEventListener('change', (e) => {
    const s = e.target.closest('.lx-cat');
    if (s) { const r = data.find((x) => String(x.id) === s.dataset.id); if (r) { r.cat = s.value; r.auto = false; } render(); }
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

  /* ---- sample XML (valid CFDI 4.0) ---- */
  q('#btnDemoXml').addEventListener('click', () => {
    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" Fecha="2026-05-08T11:14:00" SubTotal="6352.50" Total="7368.90" Moneda="MXN">
  <cfdi:Emisor Rfc="ITI171211PZ2" Nombre="INNOVA TRAVEL INCOMING SERVICES" RegimenFiscal="601"/>
  <cfdi:Receptor Rfc="LAL1203015A1" Nombre="LYNCOTT ALIMENTARIA"/>
  <cfdi:Complemento>
    <tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="1.1" UUID="ED385009-F7FB-464D-AC84-3474EE33FF4A"/>
  </cfdi:Complemento>
</cfdi:Comprobante>`;
    addFromXmlFile(new File([xml], 'ejemplo_innova.xml', { type: 'text/xml' }));
  });

  /* ---- modal ---- */
  let mode = 'sf';
  const seg = q('#segTipo');
  const sfFields = q('#sfFields');
  const cfFields = q('#cfFields');
  const mCat = q('#mCat');
  mCat.innerHTML = CATS.map((c) => `<option>${esc(c)}</option>`).join('');

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
  Lx.Wizard.registerStep(2, {
    onEnter(state) {
      if (Array.isArray(state.gastos) && state.gastos.length && !data.length) {
        data = state.gastos;
        ID = data.reduce((m, r) => Math.max(m, r.id || 0), 0);
        render();
      }
    },
    validate() {
      if (!data.length) { Lx.toast.error('Agrega al menos un gasto para continuar.'); return false; }
      return true;
    },
    collect(state) { state.gastos = data; },
  });

  render();
}

/* ==========================================================================
   Step 0 · Tipo de solicitud
   Picks the flow: 'comprobacion' (XML/PDF) or 'anticipo' (amount only).
   The choice lives in wizard state and drives step-skipping downstream.
   ========================================================================== */

export function initFlujo(Lx) {
  const panel = document.querySelector('[data-step="0"]');
  if (!panel) return;

  const cards = [...panel.querySelectorAll('.lx-flow-card')];
  let selected = Lx.Wizard.state.flujo || 'comprobacion';

  function paint() {
    cards.forEach((c) => {
      const on = c.dataset.flujo === selected;
      c.classList.toggle('is-sel', on);
      c.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  cards.forEach((c) => c.addEventListener('click', () => {
    selected = c.dataset.flujo;
    paint();
  }));

  paint();

  Lx.Wizard.registerStep(0, {
    onEnter() { selected = Lx.Wizard.state.flujo || selected; paint(); },
    validate() {
      if (!selected) { Lx.toast.error('Elige un tipo de solicitud.'); return false; }
      return true;
    },
    collect(state) { state.flujo = selected; },
  });
}

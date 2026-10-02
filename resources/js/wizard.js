/* ==========================================================================
   Lyncott — wizard.js
   The 4-step comprobación wizard state machine.

   Responsibilities (Phase 1):
     - show/hide step panels
     - drive the stepper UI (done / active states)
     - Anterior / Siguiente navigation
     - hold a single central state object that survives step changes
     - per-step validation hook (steps register their own validator)

   Step bodies (Datos del Solicitante, Viaje, Gastos, Revisar) are filled in
   their own phases. Each step may call:
       Wizard.registerStep(index, { validate, onEnter, collect })
   to plug behavior in without touching this file.
   ========================================================================== */

export const Wizard = {
  shell: null,
  index: 0,
  steps: [],          // DOM panels
  hooks: {},          // { [index]: { validate, onEnter, collect } }
  state: {            // central, survives navigation
    solicitante: {},
    viaje: {},
    gastos: [],
    revisar: {},
  },

  init(shell) {
    this.shell = shell;
    this.steps = [...shell.querySelectorAll('[data-step]')];
    this.stepperItems = [...document.querySelectorAll('.lx-steps li')];

    shell.querySelectorAll('[data-wizard-next]').forEach((b) =>
      b.addEventListener('click', () => this.next(b)));
    shell.querySelectorAll('[data-wizard-prev]').forEach((b) =>
      b.addEventListener('click', () => this.prev()));
    // Allow clicking a completed step in the rail to jump back.
    this.stepperItems.forEach((li, i) =>
      li.addEventListener('click', () => { if (i < this.index) this.go(i); }));

    this.go(0);
  },

  registerStep(index, hook) {
    this.hooks[index] = { ...(this.hooks[index] || {}), ...hook };
  },

  go(i) {
    if (i < 0 || i >= this.steps.length) return;
    this.index = i;
    this.steps.forEach((p, idx) => { p.hidden = idx !== i; });
    this.stepperItems.forEach((li, idx) => {
      li.classList.toggle('is-active', idx === i);
      li.classList.toggle('is-done', idx < i);
      li.style.cursor = idx < i ? 'pointer' : 'default';
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
    this.hooks[i]?.onEnter?.(this.state);
  },

  async next(btn) {
    const hook = this.hooks[this.index];
    // Validate current step (sync or async). Falsy/throw blocks navigation.
    if (hook?.validate) {
      try {
        const ok = await (btn && window.Lx
          ? window.Lx.loading.button(btn, () => hook.validate(this.state))
          : hook.validate(this.state));
        if (!ok) return;
      } catch {
        return;
      }
    }
    // Let the step push its data into central state.
    hook?.collect?.(this.state);

    if (this.index < this.steps.length - 1) this.go(this.index + 1);
    else this.submit(btn);
  },

  prev() { if (this.index > 0) this.go(this.index - 1); },

  /** Final submit — delegates to the handler a step registers via onSubmit. */
  async submit(btn) {
    if (typeof this.onSubmit !== 'function') {
      window.Lx?.toast?.('No hay un manejador de envío registrado.', 'error');
      return;
    }
    const run = () => this.onSubmit(this.state);
    try {
      await (btn && window.Lx ? window.Lx.loading.button(btn, run) : run());
    } catch {
      /* handler already surfaced the error */
    }
  },

  onSubmit: null,
};

/* ==========================================================================
   Lyncott — wizard.js
   Multi-step wizard state machine with conditional (skippable) steps.

   Steps register behavior without touching this file:
       Wizard.registerStep(index, {
         onEnter(state) {},        // when the step becomes visible
         validate(state) {},       // falsy/throw blocks "Siguiente"
         collect(state) {},        // push this step's data into state
         skip(state) { return bool }, // when true, the step is skipped + dimmed
       });
   And the final handler:
       Wizard.onSubmit = async (state) => { ... };
   ========================================================================== */

export const Wizard = {
  shell: null,
  index: 0,
  steps: [],
  stepperItems: [],
  hooks: {},
  state: {
    flujo: 'anticipo',   // default selection; 'anticipo' | 'comprobacion'
    solicitante: {},
    viaje: {},
    gastos: [],
    revisar: {},
  },
  onSubmit: null,

  init(shell) {
    this.shell = shell;
    this.steps = [...shell.querySelectorAll('[data-step]')];
    this.stepperItems = [...document.querySelectorAll('.lx-steps li')];

    shell.querySelectorAll('[data-wizard-next]').forEach((b) =>
      b.addEventListener('click', () => this.next(b)));
    shell.querySelectorAll('[data-wizard-prev]').forEach((b) =>
      b.addEventListener('click', () => this.prev()));
    this.stepperItems.forEach((li, i) =>
      li.addEventListener('click', () => { if (i < this.index && this.enabled(i)) this.go(i); }));

    this.go(0);
  },

  registerStep(index, hook) {
    this.hooks[index] = { ...(this.hooks[index] || {}), ...hook };
  },

  /** A step is enabled unless its hook's skip(state) says otherwise. */
  enabled(i) {
    const h = this.hooks[i];
    return !(h && typeof h.skip === 'function' && h.skip(this.state));
  },

  go(i) {
    if (i < 0 || i >= this.steps.length) return;
    this.index = i;
    this.steps.forEach((p, idx) => { p.hidden = idx !== i; });
    this.stepperItems.forEach((li, idx) => {
      const enabled = this.enabled(idx);
      // Skipped steps are hidden from the stepper entirely (not just dimmed).
      li.hidden = !enabled;
      li.classList.toggle('is-active', idx === i);
      li.classList.toggle('is-done', idx < i && enabled);
      li.style.cursor = (idx < i && enabled) ? 'pointer' : 'default';
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
    this.hooks[i]?.onEnter?.(this.state);
  },

  async next(btn) {
    const hook = this.hooks[this.index];
    if (hook?.validate) {
      try {
        const ok = await (btn && window.Lx
          ? window.Lx.loading.button(btn, () => hook.validate(this.state))
          : hook.validate(this.state));
        if (!ok) return;
      } catch { return; }
    }
    hook?.collect?.(this.state);

    let n = this.index + 1;
    while (n < this.steps.length && !this.enabled(n)) n++;
    if (n < this.steps.length) this.go(n);
    else this.submit(btn);
  },

  prev() {
    let p = this.index - 1;
    while (p >= 0 && !this.enabled(p)) p--;
    if (p >= 0) this.go(p);
  },

  async submit(btn) {
    if (typeof this.onSubmit !== 'function') {
      window.Lx?.toast?.('No hay un manejador de envío registrado.', 'error');
      return;
    }
    const run = () => this.onSubmit(this.state);
    try {
      await (btn && window.Lx ? window.Lx.loading.button(btn, run) : run());
    } catch { /* handler surfaced the error */ }
  },
};

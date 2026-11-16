/* ==========================================================================
   Lyncott — wizard.js
   Multi-step wizard state machine, paginated (one step visible at a time).

   The flow (anticipo | comprobacion) is fixed by the page and read from
   window.LX_FLUJO — there is no in-wizard flow picker anymore. The step
   sequence is built from the [data-step="<name>"] panels present in the DOM,
   so the anticipo page simply omits the "gastos" panel.

   Steps register behavior by NAME, without touching this file:
       Wizard.registerStep('solicitante', {
         onEnter(state) {},        // when the step becomes visible
         validate(state) {},       // falsy/throw blocks "Siguiente"
         collect(state) {},        // push this step's data into state
       });
   And the final handler:
       Wizard.onSubmit = async (state) => { ... };
   ========================================================================== */

export const Wizard = {
  shell: null,
  index: 0,
  order: [],          // step names, in DOM order
  panels: {},         // name -> panel element
  stepperItems: {},   // name -> stepper <li>
  hooks: {},          // name -> hook object
  state: {
    flujo: 'comprobacion',   // overridden from window.LX_FLUJO in init()
    solicitante: {},
    viaje: {},
    gastos: [],
    revisar: {},
  },
  onSubmit: null,

  init(shell) {
    this.shell = shell;
    this.state.flujo = window.LX_FLUJO || this.state.flujo;

    const panels = [...shell.querySelectorAll('[data-step]')];
    this.order = panels.map((p) => p.dataset.step);
    panels.forEach((p) => { this.panels[p.dataset.step] = p; });

    // Stepper <li> use data-stepper (NOT data-step) so they don't collide
    // with the step panels when a module queries [data-step="<name>"].
    document.querySelectorAll('.lx-steps li[data-stepper]').forEach((li) => {
      this.stepperItems[li.dataset.stepper] = li;
    });

    shell.querySelectorAll('[data-wizard-next]').forEach((b) =>
      b.addEventListener('click', () => this.next(b)));
    shell.querySelectorAll('[data-wizard-prev]').forEach((b) =>
      b.addEventListener('click', () => this.prev()));

    // Stepper: click a completed step to jump back to it.
    this.order.forEach((name, i) => {
      const li = this.stepperItems[name];
      if (li) li.addEventListener('click', () => { if (i < this.index) this.go(i); });
    });

    this.go(0);
  },

  registerStep(name, hook) {
    this.hooks[name] = { ...(this.hooks[name] || {}), ...hook };
  },

  go(i) {
    if (i < 0 || i >= this.order.length) return;
    this.index = i;

    this.order.forEach((name, idx) => { this.panels[name].hidden = idx !== i; });
    this.order.forEach((name, idx) => {
      const li = this.stepperItems[name];
      if (!li) return;
      li.classList.toggle('is-active', idx === i);
      li.classList.toggle('is-done', idx < i);
      li.style.cursor = idx < i ? 'pointer' : 'default';
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
    this.hooks[this.order[i]]?.onEnter?.(this.state);
  },

  async next(btn) {
    const name = this.order[this.index];
    const hook = this.hooks[name];
    if (hook?.validate) {
      try {
        const ok = await (btn && window.Lx
          ? window.Lx.loading.button(btn, () => hook.validate(this.state))
          : hook.validate(this.state));
        if (!ok) return;
      } catch { return; }
    }
    hook?.collect?.(this.state);

    const n = this.index + 1;
    if (n < this.order.length) this.go(n);
    else this.submit(btn);
  },

  prev() {
    if (this.index > 0) this.go(this.index - 1);
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

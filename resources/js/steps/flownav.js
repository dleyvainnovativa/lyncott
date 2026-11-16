/* ==========================================================================
   Flow navigation — sidebar / offcanvas
   The "other process" card warns before leaving, because switching flows is
   a full page navigation and discards whatever was captured in this form.
   Buttons carry data-switch-url; the modal + confirm button live in the shell.
   ========================================================================== */

export function initFlowNav(Lx) {
  const switches = document.querySelectorAll('[data-switch-url]');
  if (!switches.length) return;

  let targetUrl = null;

  switches.forEach((btn) => btn.addEventListener('click', (e) => {
    e.preventDefault();
    targetUrl = btn.dataset.switchUrl;
    Lx.modal.show('modalSwitchFlujo');
  }));

  document.getElementById('btnSwitchConfirm')?.addEventListener('click', () => {
    if (targetUrl) window.location.href = targetUrl;
  });
}

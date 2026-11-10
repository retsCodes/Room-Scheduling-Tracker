document.addEventListener('DOMContentLoaded', () => {
  const recurrence = document.getElementById('recurrence_type');
  const weekdays = document.getElementById('weekdays');
  if (recurrence && weekdays) {
    const toggle = () => {
      weekdays.style.display = recurrence.value === 'weekly' ? '' : 'none';
    };
    recurrence.addEventListener('change', toggle);
    toggle();
  }

  const modalEl = document.getElementById('confirmModal');
  const modalText = document.getElementById('confirmModalText');
  const modalYes = document.getElementById('confirmModalYes');
  let pendingYes = null;
  window.showConfirm = (message, onYes) => {
    if (!modalEl || !window.bootstrap) { if (onYes) onYes(); return; }
    modalText.textContent = message;
    pendingYes = onYes || null;
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  };
  if (modalYes) {
    modalYes.addEventListener('click', () => {
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      const fn = pendingYes;
      pendingYes = null;
      if (fn) fn();
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (form.dataset.confirmed === '1') return;
      e.preventDefault();
      window.showConfirm(form.dataset.confirm, () => {
        form.dataset.confirmed = '1';
        form.requestSubmit();
      });
    });
  });
});

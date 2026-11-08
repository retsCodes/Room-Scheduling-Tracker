// Room Scheduling Tracker — small progressive enhancements only.
// The app works fully without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
  // Show/hide the weekday picker based on the recurrence type.
  const recurrence = document.getElementById('recurrence_type');
  const weekdays = document.getElementById('weekdays');
  if (recurrence && weekdays) {
    const toggle = () => {
      weekdays.style.display = recurrence.value === 'weekly' ? '' : 'none';
    };
    recurrence.addEventListener('change', toggle);
    toggle();
  }

  // Confirm before any delete form submits (in addition to inline onsubmit).
  document.querySelectorAll('form[action*="/delete"]').forEach((form) => {
    if (!form.getAttribute('onsubmit')) {
      form.addEventListener('submit', (e) => {
        if (!window.confirm('Delete this? This cannot be undone.')) e.preventDefault();
      });
    }
  });
});

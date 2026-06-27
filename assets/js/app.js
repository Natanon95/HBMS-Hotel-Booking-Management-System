// Hotel PMS — App JS

// Toggle mobile sidebar
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
      sidebar.classList.remove('open');
    }
  });
}

// Auto-dismiss flash alerts
document.querySelectorAll('.alert[data-autohide]').forEach(el => {
  setTimeout(() => el.remove(), 4000);
});

// Confirm dialogs
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});

// Modal helpers
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('hidden');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('hidden');
}
document.querySelectorAll('[data-modal-open]').forEach(btn =>
  btn.addEventListener('click', () => openModal(btn.dataset.modalOpen))
);
document.querySelectorAll('[data-modal-close]').forEach(btn =>
  btn.addEventListener('click', () => closeModal(btn.dataset.modalClose))
);
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.add('hidden');
  });
});

// Generic fetch helper
async function apiFetch(url, options = {}) {
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
  if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;
  const res = await fetch(url, { ...options, headers });
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

// Date range: ensure check-out > check-in
const checkIn  = document.getElementById('check_in');
const checkOut = document.getElementById('check_out');
if (checkIn && checkOut) {
  checkIn.addEventListener('change', () => {
    if (checkOut.value && checkOut.value <= checkIn.value) {
      const d = new Date(checkIn.value);
      d.setDate(d.getDate() + 1);
      checkOut.value = d.toISOString().split('T')[0];
    }
    checkOut.min = checkIn.value;
  });
}

// Expose globals
window.openModal  = openModal;
window.closeModal = closeModal;
window.apiFetch   = apiFetch;

// ============================================================
// GuestList Manager Italia – Admin JS
// ============================================================

// ── Modal helpers ──────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Chiudi modal cliccando sul backdrop
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
  }
});

// Chiudi modal con Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop.open')
      .forEach(m => m.classList.remove('open'));
  }
});

// ── Sidebar toggle mobile ──────────────────────────────────
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar       = document.getElementById('sidebar');
if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
}

// ── Alert auto-dismiss ─────────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  if (!el.closest('.modal')) {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  }
});

// ── Tabelle: ordinamento colonne ──────────────────────────
document.querySelectorAll('table thead th[data-sort]').forEach(th => {
  th.style.cursor = 'pointer';
  th.addEventListener('click', () => {
    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    const col   = Array.from(th.parentElement.children).indexOf(th);
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const asc   = th.dataset.dir !== 'asc';
    th.dataset.dir = asc ? 'asc' : 'desc';

    rows.sort((a, b) => {
      const av = a.cells[col]?.textContent.trim() ?? '';
      const bv = b.cells[col]?.textContent.trim() ?? '';
      return asc ? av.localeCompare(bv, 'it') : bv.localeCompare(av, 'it');
    });
    rows.forEach(r => tbody.appendChild(r));
  });
});

// ── Conferma eliminazione ─────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});

// ── Formato valuta ─────────────────────────────────────────
function formatEur(val) {
  return '€ ' + parseFloat(val).toFixed(2).replace('.', ',');
}

// ── Toast notifica ─────────────────────────────────────────
function showToast(msg, ok = true) {
  const toast = document.createElement('div');
  toast.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
  toast.style.cssText = `
    position:fixed;bottom:1.2rem;right:1.2rem;z-index:999;
    min-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.4);
  `;
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .3s';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('addUserForm');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());

    try {
      const res = await fetch('/nsikacart/api/admin/add-users.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(data)
      });
      if (!res.ok) throw new Error(res.statusText);

      const json = await res.json();
      showToast(json.message, json.success ? 'success' : 'error');
      if (json.success) form.reset();

    } catch (err) {
      console.error(err);
      showToast('An unexpected error occurred.', 'error');
    }
  });
});

function showToast(msg, type = 'success') {
  const toast = document.getElementById('toast');
  toast.innerHTML = `
    <span class="toast-icon ${type}"></span>
    <span class="toast-text">${msg}</span>
  `;
  toast.className = `toast show ${type}`;
  setTimeout(() => toast.className = 'toast', 3000);
}
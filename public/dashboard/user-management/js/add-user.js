document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addUserForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());

        const response =  await fetch('/nsikacart/api/admin/add-users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const json = await response.json();
        const feedback = document.getElementById('message');

        if (json.success) {
            feedback.textContent = 'User add seccessfully';
            form.reset();
        } else {
            feedback.textContent = `${json.message || 'Failed to add user'}`;
        }
    });
});
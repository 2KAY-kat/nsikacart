const form = document.querySelector('#login-form');
const toast = document.getElementById('toast');

function showToast(message, type = "success") {
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = "toast";
    }, 3000);
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        email: form.email.value,
        password: form.password.value,
        remember: form.remember.checked ? 1 : 0
    };

    try {
        const response = await fetch('/nsikacart/api/auth/login.php', {
            method: "POST",
            headers: {
                "Content-type": "application/json"
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, "success");
            setTimeout(() => window.location.href = "/nsikacart/public/dashboard/index.html", 1500);
        } else {
            showToast(result.message, "error");
        }
    } catch (err) {
        showToast("An error occurred: " + err.message, "error");
    }
});
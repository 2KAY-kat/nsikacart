const form = document.querySelector('#signup-form');
const toast = document.getElementById('toast');

function showToast(message, type = "success") {
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = "toast";
    }, 35000);
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        name: form.name.value,
        email: form.email.value,
        phone: form.phone.value,
        password: form.password.value,
        confirm_password: form.confirm_password.value
    };

    try {
        const response = await fetch('/nsikacart/api/auth/register.php', {
            method: "POST",
            headers: {
                "content-type": "application/json"
            },
            body: JSON.stringify(formData)
        });

        if (!response.ok) {
            throw new Error("Network response was not ok");
        }

        const result = await response.json();
        if (result.success) {
            showToast(result.message, "success");
            setTimeout(() => window.location.href = "./login.html", 1500);
        } else {
            showToast(result.message, "error");
        }
    } catch (err) {
        showToast("An error occurred: " + err.message, "error");
    }
});
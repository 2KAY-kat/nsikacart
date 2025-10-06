const logoutBtn = document.getElementById("logout-btn");
const toast = document.getElementById("toast");

function showToast(message, type = "success") {
    if (!toast) return alert(message); 
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = "toast";
    }, 3000);
}

logoutBtn.addEventListener("click", async () => {
    try {
        const response = await fetch("../../api/auth/logout.php", {
            method: "POST"
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, "success");
            setTimeout(() => {
                window.location.href = "../../auth/login.html";
            }, 1200);
        } else {
            showToast(result.message || "Logout failed", "error");
        }
    } catch (err) {
        showToast("An error occurred during logout", "error");
    }
});
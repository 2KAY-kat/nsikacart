import { validateSession } from './session-manager.js';

// Wait for DOM to be ready before running session check
document.addEventListener('DOMContentLoaded', async function() {
    await validateSession();
});

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../../api/auth/check-session.php');
        const result = await response.json();

        if (result.success && result.user && result.user.name) {
            document.getElementById('username').textContent = result.user.name;
            // store user role for dashboard.js
            window.currentUserRole = result.user.role;
        } else {
            window.location.href = '../../auth/login.html';
        }
    } catch (err) {
        window.location.href = '../../auth/login.html';
    }
});

/*

window.addEventListener("DOMContentLoaded", async () => {
  try {
    const response = await fetch("/nsikacart/api/auth/check-session.php");
    const result = await response.json();

    if (!result.success) {
      window.location.href = "/nsikacart/auth/login.html";
      return;
    }

    document.querySelector("#user-name").textContent = result.user.name;

    if (result.user.role === "admin") {
      document.querySelectorAll(".admin-only").forEach(el => el.style.display = "block");
    } else {
      document.querySelectorAll(".admin-only").forEach(el => el.remove());
    }

  } catch (err) {
    window.location.href = "/nsikacart/auth/login.html";
  }
});

**/
const authShell = document.querySelector("#auth-shell");
const signupForm = document.querySelector("#signup-form");
const loginForm = document.querySelector("#login-form");
const forgotPasswordLink = document.querySelector("#forgot-password-link");

function setAuthMode(mode) {
    const isLogin = mode === "login";
    authShell.classList.toggle("is-login", isLogin);
    authShell.classList.toggle("is-signup", !isLogin);
    document.querySelector(".login-view").setAttribute("aria-hidden", String(!isLogin));
    document.querySelector(".signup-view").setAttribute("aria-hidden", String(isLogin));
    document.querySelector(".story-copy-login").setAttribute("aria-hidden", String(!isLogin));
    document.querySelector(".story-copy-signup").setAttribute("aria-hidden", String(isLogin));
    window.history.replaceState(null, "", isLogin ? "#login" : "#signup");
}

document.querySelectorAll(".switch-to-login").forEach((button) => button.addEventListener("click", () => setAuthMode("login")));
document.querySelectorAll(".switch-to-signup").forEach((button) => button.addEventListener("click", () => setAuthMode("signup")));
forgotPasswordLink.addEventListener("click", (event) => {
    event.preventDefault();
    window.alert("Forgot password is still in development.");
});

function setAuthModeFromHash() {
    setAuthMode(window.location.hash === "#signup" ? "signup" : "login");
}

window.addEventListener("hashchange", setAuthModeFromHash);
setAuthModeFromHash();

// Show/hide password controls (registration and login).
document.querySelectorAll("[data-toggle-password]").forEach((toggle) => {
    toggle.addEventListener("change", () => {
        const ids = toggle.dataset.togglePassword.split(",");
        ids.forEach((id) => {
            const field = document.getElementById(id.trim());
            if (field) field.type = toggle.checked ? "text" : "password";
        });
    });
});

// Client-side password confirmation. The PHP handler repeats this validation.
const registerPassword = document.getElementById("register-password");
const confirmPassword = document.getElementById("confirm-password");
const signupMessage = document.getElementById("signup-message");

if (signupForm && registerPassword && confirmPassword) {
    const validatePasswordMatch = () => {
        if (confirmPassword.value && registerPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords do not match.");
            if (signupMessage) signupMessage.textContent = "Passwords do not match.";
        } else {
            confirmPassword.setCustomValidity("");
            if (signupMessage) signupMessage.textContent = "";
        }
    };
    registerPassword.addEventListener("input", validatePasswordMatch);
    confirmPassword.addEventListener("input", validatePasswordMatch);
    signupForm.addEventListener("submit", (event) => {
        validatePasswordMatch();
        if (!signupForm.checkValidity()) {
            event.preventDefault();
            signupForm.reportValidity();
        }
    });
}

// Keep server-provided login errors visible when the login view is active.
if (window.location.search.includes("error=") && window.location.hash === "#login") {
    setAuthMode("login");
}


// Show/hide passwords on registration and login.
document.addEventListener("change", function (event) {
    const toggle = event.target.closest("[data-toggle-password]");
    if (!toggle) return;

    const show = toggle.checked;
    const ids = toggle.dataset.togglePassword.split(",");

    ids.forEach(function (id) {
        const passwordInput = document.getElementById(id.trim());

        if (passwordInput) {
            passwordInput.type = show ? "text" : "password";
        }
    });
});
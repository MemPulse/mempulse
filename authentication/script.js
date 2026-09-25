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
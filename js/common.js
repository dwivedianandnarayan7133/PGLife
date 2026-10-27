window.addEventListener("load", function () {
    /*
     * ── Login & Signup forms ──────────────────────────────────────────────────
     * NOTE: The login and signup form submit handlers are defined directly in
     * login_modal.php (handleLoginSubmit) and signup_modal.php (handleSignupSubmit).
     * We do NOT attach duplicate XHR listeners here to prevent double-submission,
     * bypass of T&C Modals, and page reloading conflicts.
     *
     * All legacy XHR-based handlers below have been intentionally removed.
     */
});

/* ── Generic network error ────────────────────────────────────────────────── */
var on_error = function (event) {
    var loadingEl = document.getElementById("loading");
    if (loadingEl) loadingEl.style.display = 'none';
    alert('Oops! Something went wrong with the network connection.');
};
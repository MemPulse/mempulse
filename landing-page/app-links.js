const APP_BASE_URL = "";

if (APP_BASE_URL) {
    const appBaseUrl = new URL(APP_BASE_URL);
    if (
        appBaseUrl.protocol !== "https:" ||
        appBaseUrl.username ||
        appBaseUrl.password ||
        appBaseUrl.search ||
        appBaseUrl.hash
    ) {
        throw new Error("APP_BASE_URL must be an HTTPS URL without credentials, query, or hash.");
    }

    if (!appBaseUrl.pathname.endsWith("/")) {
        appBaseUrl.pathname += "/";
    }

    document.querySelectorAll('a[href^="../authentication/"]').forEach((link) => {
        const path = link.getAttribute("href").replace(/^\.\.\//, "");
        link.href = new URL(path, appBaseUrl).href;
    });
}

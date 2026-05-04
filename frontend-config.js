(function () {
    const explicitBase =
        window.IPMC_API_BASE_URL ||
        document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') ||
        localStorage.getItem('IPMC_API_BASE_URL');

    const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
    const configuredBase = explicitBase || (isLocalhost ? '/admin-dashboard/api/' : '');

    if (!configuredBase) {
        throw new Error('Production API base URL is not configured. Set window.IPMC_API_BASE_URL or <meta name="api-base-url"> to the Railway backend API URL.');
    }

    const normalizedBase = configuredBase.endsWith('/') ? configuredBase : configuredBase + '/';
    const absoluteBase = new URL(normalizedBase, window.location.origin).toString();

    window.IPMC_CONFIG = {
        apiBaseUrl: absoluteBase,
        apiUrl(path) {
            return new URL(path, absoluteBase).toString();
        }
    };
})();

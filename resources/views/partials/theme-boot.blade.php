{{--
    Theme + sidebar state, applied before first paint.

    This has to be inline. A bundled asset is fetched and executed *after* the
    stylesheet has already painted, so the user would see a flash of the wrong
    theme on every navigation — the exact problem this exists to prevent.

    It is deliberately identical in both layouts and contains no Blade output,
    so its content hash is stable. When the Content-Security-Policy work lands,
    add this one hash to `script-src` (`'sha256-…'`) rather than weakening the
    policy with `'unsafe-inline'`. Generate it with:

        openssl dgst -sha256 -binary <script-body> | openssl base64

    Referenced by: layouts/app.blade.php, layouts/auth.blade.php
--}}
<script>
(function () {
    try {
        var saved = localStorage.getItem('rams.theme');
        var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        if (localStorage.getItem('rams.sidebar.collapsed') === '1') {
            document.documentElement.classList.add('rams-preload-collapsed');
        }
    } catch (e) {
        /* storage unavailable (private mode) — keep the light default */
    }
})();
</script>

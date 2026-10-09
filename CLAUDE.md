# RestfulAPI Studio, conventions

- `fc-studio.html` holds markup, style and script; read its top comment first (modules, localStorage keys). `fc-studio-manual.html` is the user manual, `fc-studio-proxy.php` the CORS proxy.
- Keep the file name `fc-studio.html`: the version check (HEAD request), the proxy docs and the hosted URL use it.
- Release: bump `APP_VERSION` and the version in the top comment and in the manual footer, add a "Changes in vX.Y" section to the manual, then tag `vX.Y`.
- The proxy only forwards `v2/...` paths; extend its regex when the app uses a new API path prefix.
- Never commit API credentials, real workspace data, exports or customer-specific variants. Example IDs in placeholders and the manual are fake.
- UI and docs text: no em dashes or en dashes (the lone "—" used as empty-cell marker is a glyph, not text).

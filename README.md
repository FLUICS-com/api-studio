# FLUICS CONNECT RestfulAPI Studio

Move data between spreadsheets and your FLUICS CONNECT inventory in bulk, through the FLUICS CONNECT public API:

- **Import**: paste rows from Excel or upload .xlsx/.csv, map the columns to workspace properties, preview and register new items.
- **Export**: pick items by Label ID, UUID or Gridify search, choose the fields and copy the table to Excel or save it as CSV.
- **Bulk Edit**: set a property value or move items to another location, with a before/after preview.

The app is one HTML file plus a small PHP proxy. No build step, no database.

## Use it

The latest version is hosted at https://link.fluics.com/apistudio. You need a FLUICS CONNECT API key (Key ID and Secret), available from FLUICS.

## Host it yourself

1. Put `fc-studio.html`, `fc-studio-manual.html` and `fc-studio-proxy.php` in one folder on any PHP web server (Apache, nginx with PHP-FPM, or locally `php -S localhost:8080`).
2. Open `fc-studio.html` and enter your API credentials (gear icon, top right).

The proxy is needed because browsers block direct cross-origin calls to the FLUICS API. It only forwards paths of the public API v2 to `api.fluics.com`, passes the Authorization header through and stores nothing. Static hosting without PHP (for example GitHub Pages) does not work, unless you enter the URL of another proxy in the credentials dialog.

API credentials are stored only in your browser (localStorage) and are never part of the configuration exports.

## Documentation

- User manual: [fc-studio-manual.html](fc-studio-manual.html), also linked in the app (book icon).
- FLUICS CONNECT public API (Swagger): https://devapi.fluics.com/swagger/index.html?urls.primaryName=V2.0-PUBLIC
- Gridify advanced search: https://fluics.com/manuals/advanced-search/

## Third-party code

SheetJS (Apache-2.0) is loaded from cdn.jsdelivr.net when an .xlsx file is imported.

## Support

FLUICS GmbH, Fürstenstr. 15, 80333 München, Germany. support@fluics.com, https://fluics.com

## License

MIT, see [LICENSE](LICENSE). The software is provided as is and used under your own responsibility: check the preview before importing or applying bulk edits.

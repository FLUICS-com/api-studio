"""Local dev server for RestfulAPI Studio, for testing without PHP.

Serves a folder and relays /fc-api/v2/... to the FLUICS public API (what
fc-studio-proxy.php does on PHP hosts; on localhost the app calls /fc-api).
POST /__shot?name=x.png saves a data-URL image into SHOTS (used to capture
the manual screenshots with html-to-image from the page).

Usage: python3 tools/dev_server.py . 8766 [shots-folder]
Then open http://localhost:8766/fc-studio.html
"""
import http.server, os, sys, urllib.request, urllib.error
ROOT = sys.argv[1]; PORT = int(sys.argv[2]); API = "https://api.fluics.com/api/public"; SHOTS = sys.argv[3] if len(sys.argv) > 3 else "img"
class H(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *a, **k): super().__init__(*a, directory=ROOT, **k)
    def relay(self):
        if not self.path.startswith("/fc-api/"): return self.send_error(405)
        rest = self.path[len("/fc-api/"):]
        if not rest.startswith("v2/") or ".." in rest: return self.send_error(400)
        n = int(self.headers.get("Content-Length") or 0); body = self.rfile.read(n) if n else None
        hdr = {k: v for k, v in self.headers.items() if k.lower() in ("authorization", "content-type")}
        req = urllib.request.Request(API + "/" + rest, data=body, headers=hdr, method=self.command)
        try: r = urllib.request.urlopen(req, timeout=60); code, data, ct = r.status, r.read(), r.headers.get("Content-Type")
        except urllib.error.HTTPError as e: code, data, ct = e.code, e.read(), e.headers.get("Content-Type")
        self.send_response(code); self.send_header("Content-Type", ct or "application/json")
        self.send_header("Content-Length", str(len(data))); self.end_headers(); self.wfile.write(data)
    def do_GET(self):
        return self.relay() if self.path.startswith("/fc-api/") else super().do_GET()
    def do_OPTIONS(self):
        self.send_response(204); self.cors(); self.end_headers()
    def cors(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "*")
        self.send_header("Access-Control-Allow-Private-Network", "true")
    def do_POST(self):
        if self.path.startswith("/__shot?name="):
            import base64, re
            name = re.sub(r"[^A-Za-z0-9_.-]", "", self.path.split("=", 1)[1])
            data = self.rfile.read(int(self.headers["Content-Length"])).decode()
            if data.startswith("d="):
                import urllib.parse; data = urllib.parse.parse_qs(data)["d"][0]
            open(os.path.join(SHOTS, name), "wb").write(base64.b64decode(data.split(",", 1)[1]))
            self.send_response(200); self.cors(); self.send_header("Content-Type", "text/html"); self.end_headers()
            self.wfile.write(b"<p>saved</p><script>window.close()</script>"); return
        return self.relay()
    do_PUT = do_PATCH = do_DELETE = relay
    def log_message(self, fmt, *a): sys.stderr.write("%s %s\n" % (self.command, self.path.split("?")[0]))
http.server.ThreadingHTTPServer(("127.0.0.1", PORT), H).serve_forever()

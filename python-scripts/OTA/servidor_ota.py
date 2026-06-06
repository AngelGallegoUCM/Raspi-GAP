# servidor_ota.py
import http.server, ssl, shutil, os

class OTAHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/firmware.bin":
            with open("firmware.bin", "rb") as f:
                data = f.read()
            self.send_response(200)
            self.send_header("Content-Type", "application/octet-stream")
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            self.wfile.write(data)
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        print(f"[OTA] {args[0]} {args[1]} {args[2]}")

server = http.server.HTTPServer(("0.0.0.0", 8070), OTAHandler)
ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
ctx.load_cert_chain("cert.pem", "key.pem")
server.socket = ctx.wrap_socket(server.socket, server_side=True)
print("Servidor OTA")
server.serve_forever()
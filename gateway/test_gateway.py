"""Exercise the actual Apache config with isolated HTTP upstreams, without databases."""
import argparse
import http.client
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer


class Upstream(BaseHTTPRequestHandler):
    def do_GET(self):
        body = self.rfile.read(int(self.headers.get('Content-Length', 0)))
        result = json.dumps({
            'module': self.server.module,
            'path': self.path,
            'method': self.command,
            'authorization': self.headers.get('Authorization'),
            'fake_role': self.headers.get('X-User-Role'),
            'body': body.decode(),
        }).encode()
        self.send_response(401 if 'unauthorized' in self.path else 200)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Content-Length', str(len(result)))
        self.end_headers()
        self.wfile.write(result)

    do_POST = do_GET
    do_PUT = do_GET
    do_DELETE = do_GET

    def log_message(self, *_):
        pass


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--apache-root', default='E:/Xampp/apache')
    args = parser.parse_args()
    root = Path(__file__).resolve().parent
    servers = []
    process = None
    runtime = root / 'runtime'
    runtime.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='gateway-test-', dir=runtime) as directory:
        temporary = Path(directory)
        assert temporary.resolve().is_relative_to(runtime.resolve())
        (temporary / 'runtime').mkdir()
        (temporary / 'public').mkdir()
        config = (root / 'httpd.conf').read_text()
        try:
            for module in range(1, 6):
                server = ThreadingHTTPServer(('127.0.0.1', 0), Upstream)
                server.module = module
                servers.append(server)
                threading.Thread(target=server.serve_forever, daemon=True).start()
                config = config.replace(f':800{module}', f':{server.server_port}')
            with socket.socket() as reservation:
                reservation.bind(('127.0.0.1', 0))
                port = reservation.getsockname()[1]
            config = config.replace(':8000', f':{port}')
            config_path = temporary / 'httpd.conf'
            config_path.write_text(config)
            environment = dict(os.environ, APACHE_ROOT=Path(args.apache_root).as_posix(),
                               GATEWAY_ROOT=temporary.as_posix())
            command = [str(Path(args.apache_root) / 'bin/httpd.exe'),
                       '-d', args.apache_root, '-f', str(config_path)]
            subprocess.run(command + ['-t'], env=environment, check=True)
            process = subprocess.Popen(command + ['-X'], env=environment,
                                       creationflags=subprocess.CREATE_NO_WINDOW)

            def request(path, method='GET', body=None, origin='http://localhost:3000'):
                connection = http.client.HTTPConnection('127.0.0.1', port, timeout=5)
                connection.request(method, path, body, {
                    'Authorization': 'Bearer integration-test-token',
                    'X-User-Role': 'admin', 'Origin': origin,
                    'Content-Type': 'application/json',
                })
                response = connection.getresponse()
                result = response.status, response.getheaders(), response.read()
                connection.close()
                return result

            for attempt in range(100):
                try:
                    request('/')
                    break
                except OSError:
                    if process.poll() is not None:
                        raise RuntimeError((temporary / 'runtime/error.log').read_text())
                    time.sleep(0.1)
            else:
                raise RuntimeError('Gateway did not start')

            routes = {
                '/api/v1/auth/me': 1, '/api/v1/admin/users': 1,
                '/api/v1/profile': 1, '/api/v1/directory/staff': 1,
                '/api/auth/login': 1, '/api/catalog/departments': 2,
                '/api/departments/1/staff': 2, '/api/support-types/1/fields': 2,
                '/api/staff-candidates': 2, '/api/faqs': 2,
                '/api/requests/7/comments': 3, '/api/sla/tickets': 3,
                '/api/catalog-usage': 3, '/api/news/5/download': 4,
                '/api/admin/news': 4, '/api/reports/export-pdf': 5,
                '/api/ratings': 5,
            }
            for path, module in routes.items():
                status, headers, raw = request(path + '?page=2&search=a%20b')
                data = json.loads(raw)
                assert status == 200 and data['module'] == module, (path, data)
                expected = path.replace('/api/auth/', '/api/v1/auth/')
                assert data['path'] == expected + '?page=2&search=a%20b', data
                assert data['authorization'] == 'Bearer integration-test-token'
                assert data['fake_role'] is None
                origins = [v for k, v in headers if k.lower() == 'access-control-allow-origin']
                assert origins == ['http://localhost:3000'], origins
            for method in ['POST', 'PUT', 'DELETE']:
                status, _, raw = request('/api/requests/7', method, '{"title":"test"}')
                data = json.loads(raw)
                assert status == 200 and data['method'] == method
                assert data['body'] == '{"title":"test"}'
            assert request('/api/requests/unauthorized')[0] == 401
            assert request('/api/requests', 'OPTIONS')[0] == 204
            assert request('/api/requests-unknown')[0] == 404
            assert request('/.env')[0] == 404
            status, headers, _ = request('/')
            assert status == 302
            assert dict(headers)['Location'] == f'http://localhost:{servers[0].server_port}/'
            _, headers, _ = request('/api/news', origin='https://untrusted.example')
            assert not any(k.lower() == 'access-control-allow-origin' for k, _ in headers)
            print(f'PASS: {len(routes)} route mappings; methods/body/token/query forwarding; '
                  'identity-header removal; CORS/preflight; 401; 404; portal redirect.')
        finally:
            if process is not None:
                process.terminate()
                process.wait(timeout=10)
            for server in servers:
                server.shutdown()
                server.server_close()


if __name__ == '__main__':
    main()

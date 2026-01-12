#!/usr/bin/env node
/**
 * Minimal GET request receiver for development.
 * Photobooth sends GET callbacks to `config.get_request.server`.
 * This helper responds 200 OK and logs incoming paths.
 */

const http = require('http');

const PORT = process.env.GETSERVER_PORT || 9100;

const server = http.createServer((req, res) => {
    // Log method + path; keep response simple
    console.log(`[getserver] ${req.method} ${req.url}`);
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('ok\n');
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`[getserver] listening on ${PORT}`);
});

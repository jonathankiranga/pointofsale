'use strict';

const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const crypto = require('node:crypto');
const { PrinterManager } = require('./lib/printer-manager');

const configPath = path.join(__dirname, 'config.json');
if (!fs.existsSync(configPath)) {
    console.error('Run Install-POS.ps1 to configure the printer and live POS address.');
    process.exit(1);
}

let config;
try {
    config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
} catch (error) {
    console.error('Invalid POSPrinter/config.json: ' + error.message);
    process.exit(1);
}

const serverUrl = String(config.serverUrl || '').trim();
if (!/^https:\/\/[^/]+/i.test(serverUrl)) {
    console.error('Configure serverUrl as the HTTPS URL of POSajax/print-agent.php.');
    process.exit(1);
}

const host = config.serviceHost || '127.0.0.1';
const port = Number(config.servicePort || 3000);
const manager = new PrinterManager(config);
const identityPath = path.join(__dirname, 'device.json');
const pollInterval = Math.max(1000, Number(config.pollIntervalMs || 3000));
let identity;
let stopping = false;
let pollRunning = false;
let registered = false;

function loadIdentity() {
    if (fs.existsSync(identityPath)) {
        const saved = JSON.parse(fs.readFileSync(identityPath, 'utf8'));
        if (/^[a-f0-9]{32}$/.test(saved.deviceId) && /^[a-f0-9]{64}$/.test(saved.deviceSecret)) {
            return saved;
        }
    }
    const created = {
        deviceId: crypto.randomBytes(16).toString('hex'),
        deviceSecret: crypto.randomBytes(32).toString('hex')
    };
    fs.writeFileSync(identityPath, JSON.stringify(created, null, 2), { mode: 0o600 });
    return created;
}

identity = loadIdentity();

async function api(action, data, authenticated) {
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (authenticated) headers.Authorization = 'Bearer ' + identity.deviceSecret;
    const response = await fetch(serverUrl, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(Object.assign({ action: action, device_id: identity.deviceId }, data || {})),
        signal: AbortSignal.timeout(20000)
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || ('POS server returned HTTP ' + response.status));
    }
    return result;
}

async function register() {
    return api('register', {
        device_secret: identity.deviceSecret,
        device_name: os.hostname() + ' POS printer'
    }, false);
}

async function acknowledge(jobId, printed, error) {
    return api('ack', {
        job_id: jobId,
        printed: printed,
        error: error || ''
    }, true);
}

async function pollOnce() {
    const response = await api('poll', {}, true);
    if (!response.job) return;
    const job = response.job;
    try {
        const result = await manager.printReceipt(job.payload);
        if (job.payload.paymentMethod === 'CASH') {
            const profile = manager.getProfile(job.payload.printerId);
            if (profile.cashDrawer !== false) {
                try { await manager.openDrawer(job.payload.printerId); }
                catch (drawerError) { console.error('Receipt printed; cash drawer did not open: ' + drawerError.message); }
            }
        }
        await acknowledge(job.id, true, '');
        console.log(new Date().toISOString(), 'Printed', job.documentno, result.delivery);
    } catch (error) {
        console.error(new Date().toISOString(), 'Print failed for', job.documentno, error.message);
        try { await acknowledge(job.id, false, error.message); }
        catch (ackError) { console.error('Could not acknowledge failed print job: ' + ackError.message); }
    }
}

async function pollLoop() {
    if (stopping || pollRunning) return;
    pollRunning = true;
    try {
        if (!registered) {
            await register();
            registered = true;
            console.log('Local printer registered with the live POS server.');
        }
        await pollOnce();
    } catch (error) {
        console.error(new Date().toISOString(), 'POS print queue connection:', error.message);
    } finally {
        pollRunning = false;
        if (!stopping) setTimeout(pollLoop, pollInterval);
    }
}

const localServer = http.createServer((req, res) => {
    const route = new URL(req.url, 'http://127.0.0.1').pathname;
    res.setHeader('Content-Type', 'application/json; charset=utf-8');
    res.setHeader('Cache-Control', 'no-store');
    if (req.method === 'GET' && route === '/health') {
        res.writeHead(200);
        res.end(JSON.stringify({
            success: true,
            service: 'POS Print Agent',
            mode: 'outbound-https-polling',
            serverConfigured: true,
            printers: manager.health()
        }));
        return;
    }
    if (req.method === 'POST' && route === '/api/test-print') {
        let body = '';
        req.on('data', chunk => { body += chunk; if (body.length > 4096) req.destroy(); });
        req.on('end', async () => {
            try {
                const data = body ? JSON.parse(body) : {};
                const result = await manager.testPrint(data.printerId);
                res.writeHead(200);
                res.end(JSON.stringify({ success: true, result: result }));
            } catch (error) {
                res.writeHead(400);
                res.end(JSON.stringify({ success: false, message: error.message }));
            }
        });
        return;
    }
    res.writeHead(404);
    res.end(JSON.stringify({ success: false, message: 'Endpoint not found.' }));
});

if (!Number.isInteger(port) || port < 1 || port > 65535) {
    console.error('Invalid local servicePort.');
    process.exit(1);
}

localServer.listen(port, host, async () => {
    console.log('POS Print Agent listening on http://' + host + ':' + port);
    console.log('Printer profiles:', manager.health().map(item => item.id).join(', ') || 'none');
    try {
        await register();
        registered = true;
        console.log('Local printer registered with the live POS server.');
        pollLoop();
    } catch (error) {
        console.error('Printer registration failed:', error.message);
        setTimeout(pollLoop, pollInterval);
    }
});

function stop() {
    stopping = true;
    localServer.close(() => process.exit(0));
}
process.on('SIGINT', stop);
process.on('SIGTERM', stop);

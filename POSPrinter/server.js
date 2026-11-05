const http = require('http');
const net = require('net');
const fs = require('fs');
const path = require('path');

const configPath = path.join(__dirname, 'config.json');
if (!fs.existsSync(configPath)) {
    console.error('Missing config.json. Copy config.example.json to config.json and configure the printer.');
    process.exit(1);
}
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));

const HOST = config.serviceHost || '127.0.0.1';
const PORT = Number(config.servicePort || 3000);
const TOKEN = String(config.apiToken || '');
const PRINTER_HOST = String(config.printerHost || '');
const PRINTER_PORT = Number(config.printerPort || 9100);
const WIDTH = Math.max(32, Math.min(48, Number(config.paperColumns || 42)));
const ALLOWED_ORIGINS = Array.isArray(config.allowedOrigins) ? config.allowedOrigins : [];

if (!TOKEN || TOKEN === 'CHANGE-THIS-TO-A-LONG-RANDOM-SECRET') {
    console.error('Set a private apiToken in POSPrinter/config.json and the matching $POSPrintToken in config.php.');
    process.exit(1);
}

function json(res, status, data, origin) {
    res.writeHead(status, {
        'Content-Type': 'application/json; charset=utf-8',
        'Cache-Control': 'no-store',
        'Access-Control-Allow-Origin': origin || 'null',
        'Vary': 'Origin'
    });
    res.end(JSON.stringify(data));
}

function corsOrigin(req) {
    const origin = req.headers.origin || '';
    if (!origin) return '';
    if (ALLOWED_ORIGINS.includes('*')) return origin;
    return ALLOWED_ORIGINS.includes(origin) ? origin : null;
}

function money(value) {
    const amount = Number(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function clean(value) {
    return String(value == null ? '' : value).replace(/[\\x00-\\x1F\\x7F]/g, ' ').trim();
}

function fit(value, width) {
    const text = clean(value);
    return text.length > width ? text.slice(0, width) : text.padEnd(width, ' ');
}

function center(value) {
    const text = clean(value).slice(0, WIDTH);
    const left = Math.max(0, Math.floor((WIDTH - text.length) / 2));
    return ' '.repeat(left) + text;
}

function line(left, right) {
    const rightText = clean(right);
    const leftWidth = Math.max(0, WIDTH - rightText.length - 1);
    return fit(left, leftWidth) + ' ' + rightText.slice(0, WIDTH - leftWidth - 1);
}

function makeReceipt(data) {
    if (!data || !Array.isArray(data.items) || data.items.length === 0) {
        throw new Error('Receipt must contain at least one item.');
    }

    const output = [];
    output.push(Buffer.from([0x1B, 0x40])); // Initialize
    output.push(Buffer.from([0x1B, 0x61, 0x01])); // Center
    output.push(Buffer.from([0x1B, 0x45, 0x01]));
    output.push(Buffer.from(center(data.company || 'Retail POS') + '\n'));
    output.push(Buffer.from([0x1B, 0x45, 0x00]));
    output.push(Buffer.from(center('SALES RECEIPT') + '\n'));
    output.push(Buffer.from([0x1B, 0x61, 0x00])); // Left
    output.push(Buffer.from('-'.repeat(WIDTH) + '\n'));
    output.push(Buffer.from('Receipt: ' + clean(data.docno) + '\n'));
    output.push(Buffer.from('Cashier: ' + clean(data.userlog) + '\n'));
    output.push(Buffer.from('Date: ' + new Date().toLocaleString() + '\n'));
    output.push(Buffer.from('-'.repeat(WIDTH) + '\n'));

    for (const item of data.items) {
        const description = clean(item.description);
        output.push(Buffer.from(description.slice(0, WIDTH) + '\n'));
        if (description.length > WIDTH) {
            for (let i = WIDTH; i < description.length; i += WIDTH) {
                output.push(Buffer.from(description.slice(i, i + WIDTH) + '\n'));
            }
        }
        const qty = Number(item.quantity) || 0;
        const price = money(item.unitPrice);
        const total = money(item.lineTotal);
        output.push(Buffer.from(line(qty + ' x ' + price, total) + '\n'));
    }

    output.push(Buffer.from('-'.repeat(WIDTH) + '\n'));
    output.push(Buffer.from(line('VAT', money(data.totalVAT)) + '\n'));
    output.push(Buffer.from([0x1B, 0x45, 0x01]));
    output.push(Buffer.from(line('TOTAL ' + (data.currency || 'KES'), money(data.total)) + '\n'));
    output.push(Buffer.from([0x1B, 0x45, 0x00]));
    output.push(Buffer.from(line('Cash', money(data.cash)) + '\n'));
    output.push(Buffer.from(line('Change', money(data.change)) + '\n'));
    output.push(Buffer.from(line('Payment', clean(data.paymentMethod || 'CASH')) + '\n'));
    if (data.paymentMethod === 'MPESA' && data.mpesaPhone) {
        output.push(Buffer.from(line('M-Pesa', clean(data.mpesaPhone)) + '\n'));
    }
    output.push(Buffer.from('-'.repeat(WIDTH) + '\n'));
    output.push(Buffer.from([0x1B, 0x61, 0x01]));
    output.push(Buffer.from('Thank you. Karibu tena!\n\n\n'));
    output.push(Buffer.from([0x1D, 0x56, 0x00])); // Full cut (printer must support it)
    return Buffer.concat(output);
}

function sendToPrinter(buffer) {
    return new Promise((resolve, reject) => {
        if (!PRINTER_HOST || PRINTER_HOST === 'SET_PRINTER_IP') {
            return reject(new Error('Set printerHost in POSPrinter/config.json to the thermal printer IP address.'));
        }

        const socket = new net.Socket();
        let settled = false;
        const finish = (error) => {
            if (settled) return;
            settled = true;
            socket.destroy();
            error ? reject(error) : resolve();
        };

        socket.setTimeout(Number(config.printerTimeoutMs || 10000));
        socket.once('error', finish);
        socket.once('timeout', () => finish(new Error('Timed out connecting to the receipt printer.')));
        socket.connect(PRINTER_PORT, PRINTER_HOST, () => {
            socket.write(buffer, error => {
                if (error) return finish(error);
                socket.end();
            });
        });
        socket.once('close', hadError => {
            if (!hadError) finish();
        });
    });
}

function readBody(req) {
    return new Promise((resolve, reject) => {
        let body = '';
        req.on('data', chunk => {
            body += chunk;
            if (body.length > 1024 * 1024) {
                reject(new Error('Request body is too large.'));
                req.destroy();
            }
        });
        req.on('end', () => {
            try { resolve(JSON.parse(body || '{}')); }
            catch (_) { reject(new Error('Request body must be valid JSON.')); }
        });
        req.on('error', reject);
    });
}

const server = http.createServer(async (req, res) => {
    const origin = corsOrigin(req);
    if (origin === null) return json(res, 403, { success: false, message: 'This POS website origin is not allowed.' }, 'null');

    if (req.method === 'OPTIONS') {
        res.writeHead(204, {
            'Access-Control-Allow-Origin': origin || 'null',
            'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, X-Print-Token',
            'Access-Control-Max-Age': '600',
            'Vary': 'Origin'
        });
        return res.end();
    }

    if (req.method === 'GET' && req.url === '/health') {
        return json(res, 200, {
            success: true,
            service: 'POS receipt printer',
            printerConfigured: Boolean(PRINTER_HOST && PRINTER_HOST !== 'SET_PRINTER_IP'),
            printerHost: PRINTER_HOST || null,
            printerPort: PRINTER_PORT
        }, origin);
    }

    if (req.method !== 'POST' || (req.url !== '/print-receipt' && req.url !== '/open-drawer')) {
        return json(res, 404, { success: false, message: 'Endpoint not found.' }, origin);
    }

    if (req.headers['x-print-token'] !== TOKEN) {
        return json(res, 401, { success: false, message: 'Invalid printing service token.' }, origin);
    }

    try {
        if (req.url === '/open-drawer') {
            // ESC/POS cash drawer pulse on pin 2. Printer and drawer must support it.
            await sendToPrinter(Buffer.from([0x1B, 0x70, 0x00, 0x19, 0xFA]));
            return json(res, 200, { success: true, message: 'Cash drawer pulse sent.' }, origin);
        }

        const data = await readBody(req);
        const receipt = makeReceipt(data);
        await sendToPrinter(receipt);
        return json(res, 200, { success: true, message: 'Receipt data sent to the printer.', documentno: clean(data.docno) }, origin);
    } catch (error) {
        console.error(new Date().toISOString(), error.message);
        return json(res, 400, { success: false, message: error.message }, origin);
    }
});

server.listen(PORT, HOST, () => {
    console.log('POS printer service listening at http://' + HOST + ':' + PORT);
    console.log('Printer target: ' + (PRINTER_HOST || 'NOT CONFIGURED') + ':' + PRINTER_PORT);
});

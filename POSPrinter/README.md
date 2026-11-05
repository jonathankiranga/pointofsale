# POS Node.js receipt printer

This service sends ESC/POS receipt data to a network thermal printer over TCP (normally port 9100). It uses Node.js built-in modules; no npm dependencies are required.

## Requirements

- Node.js 18 or newer.
- An ESC/POS-compatible thermal receipt printer reachable over the network.
- The printer IP address and its raw TCP printing port (usually 9100).

## Install

1. Copy `config.example.json` to `config.json`.
2. Set `printerHost` to the printer's actual LAN IP address.
3. Set a long random `apiToken`.
4. Put the exact same token in the root `config.php` as `$POSPrintToken`.
5. Set `allowedOrigins` to the exact origin(s) from which the POS page is served. Include scheme and port, e.g. `http://localhost:90` or `https://pos.example.com`.
6. Set `$POSPrintUrl` in root `config.php` to the Node service URL. For a service on the same computer as the browser, use `http://localhost:3000/print-receipt`. For a shared printer host, use that host's LAN IP.
7. Run:

```bash
cd POSPrinter
npm start
```

Open `http://127.0.0.1:3000/health` on the computer running the service. It reports whether a printer IP is configured.

## Network printer notes

The Node service defaults to listening only on 127.0.0.1, suitable when it runs on the same PC as the POS browser. If several POS terminals must use one printer service, change `serviceHost` to `0.0.0.0`, set `$POSPrintUrl` to the server's LAN IP, add each POS website origin to `allowedOrigins`, and allow TCP port 3000 through the server firewall. Keep the API token private.

This implementation targets network ESC/POS printers. A USB-only printer needs an OS-specific spooler/USB adapter and is not supported by this raw TCP transport.

## Receipt flow

The browser sends the receipt to Node.js. Node.js confirms the printer socket write. Then the browser calls `POSajax/save-receipt.php` to save the receipt header and lines in MySQL. If printing succeeds but the database save fails, the UI explicitly reports that state; check the database before attempting another print.

The existing M-Pesa screen is not connected to Safaricom Daraja and therefore does not confirm an M-Pesa payment. Do not treat its current print action as proof of payment.

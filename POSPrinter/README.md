# POS Print Agent

Extensible Node.js local printing service. PHP/MySQL remains responsible for sales, payments, stock and accounting; this agent only transports receipt bytes to configured printer hardware or operating-system queues.

## Included adapters

- `network-escpos`: raw ESC/POS over TCP for Ethernet/Wi-Fi receipt printers (commonly port 9100); supports cash-drawer pulse.
- `system-queue`: raw ESC/POS via Windows RAW spooler or CUPS `lp -o raw` on Linux/macOS. This can address USB, Bluetooth virtual ports or LAN printers installed as raw-capable OS queues; vendor drivers may alter ESC/POS bytes.
- `plugin`: custom trusted adapter modules in `plugins/`. See `plugins/README.md`.

Direct USB, serial and Bluetooth adapters are not bundled in this release. Use a suitable OS queue or add a device-specific plugin. Compatibility depends on OS drivers, permissions, printer language and model.

## Install

1. Install Node.js 18+.
2. Copy `config.example.json` to `config.json`.
3. Set a random `apiToken` (at least 24 characters); match it to `$POSPrintToken` in the POS root `config.php`.
4. Configure one or more printer profiles and set `defaultPrinterId`.
5. Add the exact POS origin (scheme and port) to `allowedOrigins`.
6. Run `npm start`; run unit tests using `npm test`.

## API

Except `GET /health`, requests require `X-Print-Token`.

- `GET /health`: service and printer profile status.
- `GET /api/printers`: configured printer profiles.
- `POST /print-receipt`: backward-compatible endpoint used by current POS JavaScript.
- `POST /api/print`: equivalent receipt endpoint.
- `POST /open-drawer`: ESC/POS cash drawer pulse.
- `POST /api/test-print`: test receipt.

Payload accepts the current POS fields: `company`, `userlog`, `docno`, `items[]` (`description`, `quantity`, `unitPrice`, `lineTotal`), `totalVAT`, `total`, `cash`, `change`, `currency`, `paymentMethod`, `mpesaPhone`; optional `printerId` selects a profile.

A successful network result means TCP accepted the bytes; a successful OS queue result means the spooler accepted the job. Neither proves that paper physically emerged. Test on each actual printer model before production deployment.

## Security

Keep the agent on the POS terminal and bind to `127.0.0.1` for single-terminal installations. For a shared print host, bind to `0.0.0.0`, use its LAN IP, restrict firewall access, and configure exact browser origins. Do not expose this service to the public internet. An HTTP agent can be blocked as mixed content from an HTTPS POS site; use a secure local-agent deployment or HTTPS reverse proxy.

## Scope boundary

This agent does not create sales, verify M-Pesa, deduct stock or post accounts. Those remain PHP/MySQL POS responsibilities.

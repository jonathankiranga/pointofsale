# POS Print Agent

The local Node.js agent prints receipts from the live POS even when the POS is hosted on ordinary shared PHP hosting.

## How it works

1. PHP saves the sale and its accounting entries as it does today.
2. After the sale commits, PHP adds the receipt payload to the MySQL print queue.
3. The Windows agent makes an outbound HTTPS connection to `POSajax/print-agent.php` every few seconds.
4. The agent claims a queued job, sends it to the existing `PrinterManager` and acknowledges the result.
5. The agent retries failed jobs up to five attempts and starts automatically when the Windows user signs in.

The shared host does not need Node.js, a VPN, a public inbound port, or a permanent background process.

## Installation

1. Upload the updated POS application to the live HTTPS website.
2. Run `SQL/pos_print_queue.sql` against the POS company database selected by `$DefaultDatabase` in the root `config.php`.
3. On the Windows cashier computer, clone/download this repository.
4. Run PowerShell as Administrator from the repository root and execute `Install-POS.ps1`.
5. Enter the live POS website base address, then select the Windows receipt-printer queue.
6. The installer configures Node.js, installs dependencies, tests the agent, creates a Windows logon task and starts the agent.

The device identity is generated automatically and stored in the ignored local file `POSPrinter/device.json`. There is no manually created or copied print token. Do not delete that file after registration unless you intend to register the computer again.

## Printer compatibility

The installer uses the selected Windows printer queue with RAW ESC/POS output. The queue/driver must pass ESC/POS data through correctly. Some vendor drivers alter raw data. If the printer does not support ESC/POS, its own adapter or a compatible driver is required.

The current installer disables cash-drawer control for the generic Windows queue. Direct ESC/POS network profiles can enable it in `POSPrinter/config.json`.

## Scope and delivery guarantees

- PHP/MySQL remains responsible for sales, payment validation, stock and accounting.
- A printer outage never reverses a committed sale.
- A successful agent acknowledgement means the configured printer adapter accepted the job; it cannot prove that paper physically emerged.
- If Windows or the agent stops after the printer accepts paper but before the acknowledgement reaches the server, the lease expires and the job may print again. This is the unavoidable retry window without a printer that supports job-level idempotency.
- This configuration is intended for one active local print agent per POS company database. If several cashier terminals need different printers, the queue must be extended with explicit terminal-to-agent assignment before installing multiple agents.

## Local diagnostics

- Open `http://127.0.0.1:3000/health` on the cashier PC to check the local agent and configured printer.
- Agent activity and errors are written to the Node.js process output. Check the Windows Task Scheduler entry named `POS Node Print Agent` if the agent is not running.
- The live server endpoint is `POSajax/print-agent.php`; it requires HTTPS.

# POS Receipt Printing Installation Manual

**System:** SmartERPlims / POS  
**Architecture:** PHP + MySQL on public shared hosting, with a Node.js print agent on a Windows computer  
**Applies to:** The shared-hosting print queue introduced in this repository

---

## 1. Understand which computer does what

There are three places involved. Do not confuse their procedures.

| Location | What happens there | Does it need Node.js? |
|---|---|---|
| **A. Live hosting account (public POS website)** | Updated PHP files are uploaded. The MySQL print-queue tables are created. | **No** |
| **B. Administrator's computer** | Used to open the hosting control panel/phpMyAdmin, upload files, run SQL, and download the installer files. It does not need to be the cashier's computer. | No, unless it is also the cashier computer |
| **C. Cashier's Windows computer connected to the receipt printer** | Node.js and the POS print agent are installed. The agent contacts the live POS over outbound HTTPS and sends jobs to the locally installed printer. | **Yes** |

The public shared server does not connect directly to the printer and does not run a permanent Node.js process. The cashier computer starts the connection to the public server.

### Current deployment limit — read before installation

The current queue is shared by the POS company database and does not yet route jobs to individual cashier terminals. It is designed for **one active print agent per POS company database**.

Therefore:

- Install the agent on only one designated Windows computer for this company database.
- That computer must be the one connected to the receipt printer you want to use.
- Do not install and activate agents on several cashier computers against the same database. Any active agent could claim the next queued receipt.
- If each cashier must print to a different local printer, terminal-to-device assignment must be implemented before deploying multiple agents.

---

## 2. What you need before starting

### On the administrator's computer

- Access to the live POS hosting control panel or file manager/FTP.
- Access to phpMyAdmin or another MySQL tool for the POS company database.
- The live POS website address, including `https://`.
- Permission to upload the updated POS application files and run the SQL migration.
- A copy of this repository, including the `POSPrinter`, `POSajax`, `SQL` folders and `Install-POS.ps1`.

### On the cashier's Windows computer

- Windows 10 or Windows 11.
- A working internet connection.
- The receipt printer physically connected to this computer (USB, or already installed as a Windows printer).
- The correct printer driver installed.
- The printer visible under **Windows Settings → Bluetooth & devices → Printers & scanners**, or in the Windows printer list.
- A Windows account that can sign in and print to that printer.
- Administrator permission for the one-time installation.
- Permission to download/install Node.js LTS if Node.js 18 or later is not already installed. The installer uses Windows Package Manager (`winget`) for this when available.

### Printer preparation — cashier's computer

1. Connect and switch on the receipt printer.
2. Install the printer manufacturer's Windows driver, if required.
3. Open **Settings → Bluetooth & devices → Printers & scanners**.
4. Confirm that the printer appears and is not showing an installation error.
5. Print a Windows test page if the printer driver offers that option. This confirms Windows can communicate with the printer; it does not yet test POS receipt formatting.

---

## 3. Procedure A — prepare the live POS hosting account

**Perform this section using the administrator's computer.** These actions take place in the hosting control panel/file manager/FTP and the live MySQL database. They are not performed on the cashier computer.

### A1. Back up the live POS

1. Sign in to the hosting control panel.
2. Back up the current POS application files, or download a copy of the current application.
3. Export the POS company database selected by the live root `config.php` setting `$DefaultDatabase`.
4. Keep the backup somewhere outside the live application directory.

Do not skip the database backup before applying the SQL migration.

### A2. Upload the updated application files

1. Open the repository's `main` branch.
2. Obtain the updated application files from that branch.
3. Upload the changed POS application files to the corresponding locations in the live POS website, preserving the existing directory structure.
4. In particular, ensure the live website contains the updated:
   - `POSajax/pos.inc`
   - `POSajax/printreceipt.js`
   - `POSajax/save-receipt.php`
   - `POSajax/print-agent.php`
   - Root `config.php` from the updated application, if it is part of the deployment changes.
5. Do not upload the local `POSPrinter/device.json` file. It is generated separately on the cashier computer and must remain private to that installation.
6. Do not overwrite unrelated live configuration files with development/example configuration files.

The endpoint must be reachable at:

```
https://YOUR-POS-DOMAIN/POSajax/print-agent.php
```

Replace `YOUR-POS-DOMAIN` with the real domain and any application subdirectory used by your live POS. The Windows installer constructs the endpoint by appending `/POSajax/print-agent.php` to the website address you enter. If the POS is installed in a subdirectory, include that subdirectory in the address.

### A3. Create the print queue tables

**Still on the administrator's computer, using phpMyAdmin or your hosting database tool:**

1. Open phpMyAdmin.
2. Select the exact POS company database used by the live application. Check the root live `config.php` value of `$DefaultDatabase`; do not guess the database name.
3. Open the **SQL** tab.
4. Open the repository file `SQL/pos_print_queue.sql` on the administrator's computer.
5. Copy the complete SQL contents.
6. Paste the SQL into phpMyAdmin's SQL editor.
7. Execute it.
8. Confirm that both tables now exist:
   - `pos_print_devices`
   - `pos_print_jobs`

The migration uses `CREATE TABLE IF NOT EXISTS`. It creates the queue tables; it does not create a Node.js installation on the hosting server.

If phpMyAdmin reports an error, stop and resolve it before proceeding. Do not repeatedly run unrelated SQL or create tables with different names.

### A4. Confirm the live endpoint

1. In a browser on the administrator's computer, open the live endpoint URL.
2. The endpoint is POST-only. Opening it directly in a browser sends a GET request, so a **405 / POST is required** response is expected.
3. A 404 means the PHP file is missing or the URL/path is wrong.
4. A PHP error or 500 response must be investigated in the hosting PHP error log.
5. Confirm that the website uses valid HTTPS.

Do not try to test printing by opening the endpoint as a normal web page.

---

## 4. Procedure B — install the print agent on the cashier's Windows computer

**Perform this entire section on the one designated cashier computer that is connected to the receipt printer.** Do not run these steps on the shared hosting server.

### B1. Obtain the application files

On the cashier computer:

1. Sign in to Windows using the Windows account that will be used for POS printing.
2. Download the repository ZIP from the GitHub `main` branch, or clone the repository if Git is already installed.
3. If using the ZIP, extract it to a permanent folder, for example:

   `C:\POS\pointofsale`

4. Confirm that this folder contains:
   - `Install-POS.ps1`
   - `POSPrinter\package.json`
   - `POSPrinter\server.js`
   - `POSPrinter\config.example.json`

Do not run the installer from inside the compressed ZIP. Extract the files first. Keep the folder in a permanent location; the scheduled task uses that folder to start the agent.

### B2. Open PowerShell as Administrator

1. Click Start.
2. Search for **Windows PowerShell**.
3. Right-click it and select **Run as administrator**.
4. If Windows asks for permission, approve it.
5. Change directory to the folder where you extracted the repository. For example:

   ```powershell
   cd C:\POS\pointofsale
   ```

6. Confirm the installer exists:

   ```powershell
   Get-ChildItem .\Install-POS.ps1
   ```

### B3. Run the installer

Run:

```powershell
powershell -ExecutionPolicy Bypass -File .\Install-POS.ps1
```

Follow the prompts in order.

**Prompt 1 — live POS website address**

Enter the public HTTPS address of the POS website, for example:

```
https://pos.example.co.ke
```

If the POS is in a subdirectory, include it, for example:

```
https://example.co.ke/pos
```

Do not enter the phpMyAdmin address, hosting control-panel address, database hostname, or a localhost address.

The installer appends `/POSajax/print-agent.php` to the address.

**Node.js installation**

- If Node.js 18 or later is already installed, the installer uses it.
- If it is missing, the installer attempts to install Node.js LTS using `winget`.
- If `winget` is unavailable or the installation fails, stop. Install the current Node.js LTS release for Windows using the official Node.js installer, reopen PowerShell, and rerun the POS installer.

**Prompt 2 — select the receipt printer**

The installer lists printers that Windows already knows about. Each entry includes the Windows printer name and driver.

1. Identify the physical receipt printer in the list.
2. Enter the number shown beside that printer.
3. Do not select a normal office A4 printer unless that is genuinely the printer intended for receipts.

The selected Windows printer queue is saved in the agent configuration. You do not need to type a printer name or create a printer ID yourself.

**Dependency installation and tests**

The installer runs `npm install` and the agent's `npm test` suite. If either fails, the installer stops and displays an error. Do not treat a failed installation as complete.

**Automatic startup**

The installer creates a Windows Scheduled Task named:

```
POS Node Print Agent
```

It starts the agent when the configured Windows user signs in and starts it immediately after installation.

Important: this task runs at that Windows user's sign-in. It is not a service running on the public hosting server. The Windows computer must be powered on, connected to the internet, and the configured user must be signed in for the agent to operate.

### B4. Keep the generated device identity

The agent generates its own device ID and secret and stores them in:

```
POSPrinter\device.json
```

This file is created on the cashier computer when the agent registers with the live POS.

- No cashier needs to invent, copy, or enter a print token.
- Do not upload this file to the website or GitHub.
- Do not send it to another cashier or copy it to another computer.
- Do not delete it after registration. Deleting it causes that computer to generate a new identity and register again.
- Keep the extracted application folder in the same location. Moving or deleting it can prevent the scheduled task from starting.

The server stores a hash of the device secret rather than the plain secret.

---

## 5. Procedure C — verify the cashier computer and printer

**Perform these checks on the cashier's Windows computer.**

### C1. Check that the local agent is running

1. Open a browser on the cashier computer.
2. Visit:

   ```
   http://127.0.0.1:3000/health
   ```

3. Check the response. It should indicate that the local agent is responding and show its configured printer/health information.

This address is local to that Windows computer. It is not the public POS website and must not be entered into the POS website configuration.

If the page does not respond:

1. Open **Task Scheduler**.
2. Select **Task Scheduler Library**.
3. Find **POS Node Print Agent**.
4. Check its status and last run result.
5. Try starting the task.
6. If it fails, open PowerShell in the repository's `POSPrinter` folder and run:

   ```powershell
   node .\server.js
   ```

7. Read the error shown in the PowerShell window. Correct the reported problem before testing a sale.

### C2. Confirm that the device registered with the live server

The agent registers automatically when it contacts the live endpoint. On the POS company database, an administrator can inspect the `pos_print_devices` table in phpMyAdmin.

The cashier computer should appear with a device ID, device name, active status and recent `last_seen` time.

Do not edit the secret hash manually. Do not expose the contents of `device.json`.

### C3. Perform a real POS receipt test

1. Confirm the live POS website is available over HTTPS.
2. Confirm the agent is running on the cashier computer.
3. Sign in to the POS as usual.
4. Complete a small authorised test sale using the normal POS procedure.
5. Observe the POS print/queue response.
6. Check that a new row was added to `pos_print_jobs`.
7. Check that the job progresses from `queued` to `printing` and then `printed`.
8. Physically inspect the printer: confirm that paper came out, the correct receipt was printed, and the text is legible.

A database status of `printed` means the print adapter acknowledged the job. It does not independently prove that paper physically emerged. Physical inspection is required for the first test.

Do not create repeated sales just because a receipt is delayed. First inspect the queue status and agent output to avoid accidental duplicate receipts.

---

## 6. What happens during normal operation

1. The cashier completes a sale in the live POS website.
2. PHP saves the sale and accounting entries in the POS database.
3. After the sale transaction commits, PHP places the receipt payload in `pos_print_jobs`.
4. The local agent on the designated cashier computer polls the live website over outbound HTTPS.
5. The server returns a queued receipt to the agent.
6. The agent sends the receipt to the selected Windows printer queue.
7. The agent acknowledges the result to the server.
8. The queue records the job's status.

The cashier computer initiates the network connection. The shared host does not need to open a connection to the cashier computer, and no router port forwarding is required.

A printer or internet failure must not reverse a sale that has already been committed. A queued receipt can be investigated and retried according to its queue status.

---

## 7. Troubleshooting

| Symptom | Where to investigate | What to check |
|---|---|---|
| POS says receipt could not be queued | Live hosting / POS database | Confirm both queue tables exist; inspect PHP error log and database permissions. The sale may still have been saved. |
| Agent cannot register | Cashier computer and live hosting | Confirm the entered website address is correct, HTTPS works, and `POSajax/print-agent.php` is uploaded. |
| Endpoint returns 404 | Live hosting | Check the application subdirectory and exact endpoint path. |
| Endpoint returns 500 | Live hosting | Check PHP error log, database connection settings, and whether the SQL migration was applied to the correct company database. |
| Agent returns authentication error | Cashier computer and database | Do not delete `device.json`. Check that the device row exists and is active in `pos_print_devices`. |
| Local health page does not open | Cashier computer | Check the Scheduled Task, Node.js installation, and agent console output. |
| Printer is missing from installer list | Cashier computer | Install the correct Windows printer driver and confirm the printer appears in Windows Settings. Rerun the installer. |
| Agent accepts job but no paper prints | Cashier computer / printer | Check paper, printer status, Windows test page, driver and ESC/POS compatibility. Some vendor drivers do not pass raw receipt data correctly. |
| Receipt prints twice | Queue and cashier computer | Inspect job attempts and timestamps. A retry can occur if the printer accepted the receipt but the acknowledgement did not reach the server. |

### Useful database checks

Run these read-only queries in phpMyAdmin against the POS company database:

```sql
SELECT device_id, device_name, is_active, created_at, last_seen
FROM pos_print_devices
ORDER BY created_at DESC;

SELECT id, document_no, status, device_id, attempts, last_error,
       created_at, printed_at
FROM pos_print_jobs
ORDER BY id DESC
LIMIT 30;
```

Do not manually change a job to `printed` just to clear the queue. Investigate the printer and agent first.

---

## 8. Important operating limitations

1. **One active agent only for this database:** the current implementation does not yet map each cashier terminal to its own printer agent. Do not install multiple agents for the same company database until terminal-specific routing is added.
2. **Windows sign-in is required:** the Scheduled Task starts at the configured user's logon. If the computer is shut down, asleep, disconnected, or that user is signed out, it cannot print.
3. **Printer driver compatibility matters:** the current Windows system-queue profile sends RAW ESC/POS output. Some Windows drivers modify or reject raw data. A successful Windows test page does not guarantee ESC/POS receipt compatibility.
4. **Cash drawer:** the generic Windows queue profile has cash-drawer control disabled.
5. **Acknowledgement is not physical proof:** the server records the agent's acknowledgement, not a sensor reading from the paper output.
6. **Possible retry duplicate:** if a printer accepts a receipt but the agent stops before acknowledging it, the server may retry after the job lease expires. Inspect the queue before manually reprinting.
7. **Internet required:** the cashier computer needs outbound HTTPS access to the live POS website. No inbound port or VPN is required.

---

## 9. Moving the agent to another cashier computer

Do not copy `POSPrinter/device.json` from one computer to another.

For a controlled replacement of the single designated agent:

1. Stop the **POS Node Print Agent** scheduled task on the old cashier computer.
2. Confirm the old computer will no longer poll the queue.
3. Install the agent on the replacement cashier computer using Section 4.
4. Select the printer physically connected to the replacement computer.
5. Confirm the new device registers in `pos_print_devices`.
6. Run a physical receipt test before using it for normal sales.

Do not leave both computers running agents against the same company database.

---

## 10. Uninstall the local agent

**Perform this only on the cashier Windows computer.**

1. Open PowerShell as Administrator.
2. Stop and remove the scheduled task:

   ```powershell
   Stop-ScheduledTask -TaskName 'POS Node Print Agent' -ErrorAction SilentlyContinue
   Unregister-ScheduledTask -TaskName 'POS Node Print Agent' -Confirm:$false
   ```

3. The agent will no longer poll the live queue.
4. Keep or securely archive the local `POSPrinter\device.json` if you may reinstall the same device. Do not share it.
5. Remove the extracted application folder only after confirming that it is no longer needed.

Do not drop the two database tables as part of ordinary local uninstallation. They contain the server-side device registration and print-job history.

---

## 11. Installation completion checklist

### Live hosting — administrator's computer

- [ ] Live POS files backed up.
- [ ] Updated PHP files uploaded to the correct live paths.
- [ ] HTTPS website and print-agent endpoint path confirmed.
- [ ] `SQL/pos_print_queue.sql` executed against the database selected by live `$DefaultDatabase`.
- [ ] `pos_print_devices` table exists.
- [ ] `pos_print_jobs` table exists.
- [ ] PHP error log checked if the endpoint or queue reports an error.

### Cashier Windows computer

- [ ] Receipt printer installed in Windows.
- [ ] Printer can print a Windows test page.
- [ ] Repository extracted to a permanent folder.
- [ ] PowerShell run as Administrator.
- [ ] `Install-POS.ps1` completed without errors.
- [ ] Correct live HTTPS POS address entered.
- [ ] Correct Windows receipt printer selected.
- [ ] Node.js 18 or later available.
- [ ] npm installation and agent tests completed successfully.
- [ ] `POS Node Print Agent` scheduled task created and running.
- [ ] Local health endpoint responds.
- [ ] Device registration visible in `pos_print_devices`.
- [ ] Test sale created and queue status checked.
- [ ] Physical receipt inspected and confirmed.

**Do not mark the installation complete until a real receipt has physically printed from the live POS.**

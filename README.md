# Admin Action Log for Magento 2

See who changed what in your Magento admin, who logged in, and who is logged in right now, and undo unwanted edits.

Magento Open Source has no admin action log: the Action Logs report is an Adobe Commerce feature. Action Log records
admin changes, configuration saves and login attempts, lists active admin sessions with a way to end them, and can
restore products, categories, CMS content and customers to the state they were in before an edit.

## Features

- **Action Log** — every admin save, delete, mass status change, upload, order cancel / hold / unhold, role save and
  *Login as Customer* is recorded with the admin user, time, IP address and browser. Actions run from the command
  line, cron or setup scripts show as *System (CLI / cron)*.
- **Configuration changes with scope** — saving a configuration page records the new values and the website or store
  they apply to. Passwords, keys, tokens and other secrets are masked.
- **One-click restore** — for the entities you choose (products, categories, CMS pages, CMS blocks, customers), the
  state before each admin edit is kept so you can roll the change back, in the store view where it was made.
- **Login Log** — every admin login attempt, with a green *Success* or red *Failed* badge and a filter for each.
- **Active Sessions** — see which admins are logged in, from where and since when, and terminate a session to log
  that admin out.
- **Readable grids** — browsers read like "Chrome 156 on macOS" (the full User-Agent is in the tooltip) and dates are
  in your store's timezone. All three grids use Magento's standard filters, sorting and column controls.
- **Automatic cleanup** — old log entries and stale sessions are deleted after the number of days you choose.
- **Role-based access** — separate admin permissions for the Action Log, Login Log, Active Sessions, configuration and
  restoring entities.

## How it works

Turn logging on in the configuration (it is off after installation). From then on, the **Action Log** admin menu
shows three pages:

- **Action Log** — admin actions and changes, with a **Restore** link on updates that have a saved before-state.
- **Login Log** — admin login attempts.
- **Active Sessions** — current admin sessions, with **Terminate**.

Restoring requires the *Restore Entities* permission plus the admin's normal edit permission for that entity (for
example *Catalog › Products*).

## Good to know

- Logging is off by default.
- Restore brings back column and attribute values only, not images, stock or tier prices. Password hashes and tokens
  are never restored.
- Deleted entities cannot be restored; restore is offered only for updates.
- Before-states are saved only for edits made in the admin, not for storefront, cron or API changes.
- Actions are recognised by name (save, delete, mass status, upload, cancel, hold and similar). Viewing pages and the
  admin's own grid view settings are not logged.
- Cleanup needs Magento cron to be running.

## Requirements

- Magento Open Source 2.4 (developed on 2.4.8-p4)
- PHP 8.3 or 8.4
- Magento cron running (for cleanup)

## Installation

Composer (from a VCS repository):

```bash
composer config repositories.tonyapps-action-log vcs <repository-url>
composer require tonyapps/module-action-log
```

Or copy the module into `app/code/Tonyapps/ActionLog`.

Then, from the Magento root:

```bash
bin/magento module:enable Tonyapps_ActionLog
bin/magento setup:upgrade
bin/magento setup:di:compile                 # production mode only
bin/magento setup:static-content:deploy -f   # production mode only
bin/magento cache:flush
```

### Uninstall

```bash
bin/magento module:disable Tonyapps_ActionLog
bin/magento setup:upgrade
```

Then remove `app/code/Tonyapps/ActionLog` (or `composer remove tonyapps/module-action-log`). Drop the three
`tonyapps_*` tables manually if you no longer need the data.

## Configuration

- **Stores › Configuration › Advanced › Action Log** (or **Action Log › Configuration** in the admin menu):
  - **Enable Action Log** — turns logging on.
  - **Log Retention (days)** — logs, and sessions idle for longer than this, are deleted by the daily cleanup at
    02:00. `0` disables cleanup.
  - **Entities to Snapshot for Restore** — which entities keep a before-state for restore.
- **System › Permissions › User Roles › Action Log** — access to each page, configuration and *Restore Entities*.

## For developers

Architecture, decisions, data model and tests are in [AGENTS.md](AGENTS.md).

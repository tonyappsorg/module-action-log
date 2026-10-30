# Tonyapps_ActionLog

Magento 2 module that tracks admin actions and system changes, logs admin logins, monitors active admin sessions, and lets you restore changed entities.

Requires PHP 8.1–8.3.

## Installation

### Option A: copy into `app/code`

```bash
mkdir -p app/code/Tonyapps/ActionLog
# copy the contents of this repository into app/code/Tonyapps/ActionLog
```

### Option B: Composer (from a VCS repository)

```bash
composer config repositories.tonyapps-action-log vcs <repository-url>
composer require tonyapps/module-action-log
```

### Enable the module

From the Magento root:

```bash
bin/magento module:enable Tonyapps_ActionLog
bin/magento setup:upgrade
bin/magento setup:di:compile          # production mode only
bin/magento setup:static-content:deploy -f   # production mode only
bin/magento cache:flush
```

`setup:upgrade` creates the tables `tonyapps_action_log`, `tonyapps_admin_login_log` and `tonyapps_admin_session`.

## Configuration

Logging is **off by default**. Turn it on at
**Stores → Configuration → Advanced → Action Log** (or **Action Log → Configuration** in the admin menu):

| Setting | Description |
|---|---|
| Enable Action Log | Turns logging on. |
| Log Retention (days) | Logs, and sessions idle for longer than this, are deleted by the daily cleanup cron (02:00). `0` disables cleanup. |
| Entities to Snapshot for Restore | Entities whose before-state is saved so changes can be restored. Snapshots keep column and attribute values only (not images, stock, tier prices), in the store view the change was made in. Password hashes and tokens are redacted and left untouched by a restore. |

Make sure Magento cron is running so cleanup happens.

## Usage

Admin menu **Action Log**:

- **Action Log** – admin actions and changes, with restore.
- **Login Log** – admin login attempts.
- **Active Sessions** – current admin sessions, with terminate.

Access to each page is controlled by ACL under **System → Permissions → User Roles → Action Log**. Restore needs
**Action Log → Restore Entities** plus the entity's own edit permission (e.g. *Catalog → Products*), and only runs on POST.

Config changes are logged with secrets redacted: fields of type `obscure`, with an encrypted backend, or registered as
sensitive config paths, plus any key containing password, secret, token, private_key, signature or api/access key.

## Review fixes (2026-10-30)

- **Grids check ACL on data loads too**: each listing's data source declares its ACL resource, so `mui/index/render`
  refuses roles without *Action Log*, *Login Log* or *Active Sessions*.
- **Session ids are stored as SHA-256 hashes**, never raw, and the sessions grid doesn't load the column. Rows written
  before this change hold raw ids that no longer match: their sessions can't be terminated from the grid and stop
  heartbeating. Clear them with `TRUNCATE tonyapps_admin_session;` (admins stay logged in; their next login is tracked
  again), or ignore them until the cleanup cron removes them.
- **The Action Log grid no longer loads `data_before`/`data_after`**; it gets a `has_snapshot` flag instead. Restore
  is offered only for updates with a snapshot, not for deletes (the entity is gone).
- **Restore loads the store view the snapshot was taken in**, so store-view edits are rolled back.
- **Snapshots are taken only for admin saves**, not storefront, cron or API saves. The backend session is injected as
  a proxy, so saves outside the admin no longer start one.
- **Terminate is POST-only**.
- **More actions logged**: role saves, Login as Customer, and order cancel/hold/unhold (single and mass).
- **Config changes record their scope**: `data_after` is now `{"website": …, "store": …, "groups": {…}}`.

## Uninstall

```bash
bin/magento module:disable Tonyapps_ActionLog
bin/magento setup:upgrade
```

Then remove `app/code/Tonyapps/ActionLog` (or `composer remove tonyapps/module-action-log`). Drop the three `tonyapps_*` tables manually if you no longer need the data.

## Tests

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/ActionLog/Test/Unit
```

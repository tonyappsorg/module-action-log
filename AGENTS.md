# Tonyapps_ActionLog — notes for AI agents and developers

Read this before changing the module. Workspace-wide rules are in ../AGENTS.md.

Package `tonyapps/module-action-log` v1.0.0, proprietary. Tracks admin actions and system changes, logs admin logins,
monitors active admin sessions, and restores changed entities.

## Status

- Built and in use; 2026-10-30 review fixes and 2026-11-09 grid readability changes applied (see Change history).
- PHP: `composer.json` requires `~8.3.0||~8.4.0`. The old README said "Requires PHP 8.1–8.3" — that was wrong.

## Architecture

| Piece | Class / file | What it does |
|---|---|---|
| Action logging | `Observer/AdminActionLogger` on adminhtml `controller_action_predispatch` | Logs when the full action name matches `/(save\|saverole\|delete\|massStatus\|massEnable\|massDisable\|upload\|remove\|cancel\|hold)$/i` or is `loginascustomer_login_login`. Skips its own `tonyapps_actionlog*` pages and `mui_*` (admin UI saving grid views, columns, filters). `ponytail:` name-pattern match instead of ACL-based classification; add real classification if a controller is missed. |
| Writer | `Model/Logger` (`recordAction`, redaction) | `configStructure` injected as `Magento\Config\Model\Config\Structure\Proxy` (heavy, only needed on config save); `authSession` as `Magento\Backend\Model\Auth\Session\Proxy` so saves outside the admin don't start a backend session. |
| Snapshots | `Plugin/EntitySnapshotPlugin` on `Magento\Framework\Model\ResourceModel\Db\AbstractDb` and `Magento\Eav\Model\Entity\AbstractEntity` (product, category, customer are EAV and don't extend `Db\AbstractDb`) | Saves before-state of watched entities on admin saves only. |
| Config saves | `Plugin/ConfigSavePlugin` on `Magento\Config\Model\Config` | Logs config changes with scope and redacted secrets. |
| Logins | `Plugin/AuthLoginPlugin` on `Magento\Backend\Model\Auth` | `aroundLogin` writes the login log and session row; `aroundLogout` terminates the session row. |
| Sessions | `Plugin/SessionGuardPlugin` on `Magento\Backend\Model\Auth\Session` | `afterProlong` heartbeats (`touch`); `afterIsLoggedIn` destroys the session and returns false when its row is terminated. |
| Restore | `Model/Restorer`, `Controller/Adminhtml/Log/Restore` | POST only. Loads the store view the snapshot was taken in. |
| Terminate | `Controller/Adminhtml/Session/Terminate` | POST only. |
| Grids | UI component listings `tonyapps_actionlog_{log,loginlog,session}_listing`; collections `ActionLog\Grid\Collection`, virtual `AdminLoginLog\Grid\Collection`, `AdminSession\Grid\Collection` | Each data source declares its `aclResource`. Columns: `Ui/Component/Listing/Column/{Actor,LogActions,LoginStatus,SessionActions,UserAgent}`. |
| Cleanup | `Cron/Cleanup`, job `tonyapps_actionlog_cleanup`, `0 2 * * *` | Deletes action and login log rows with `created_at` older than retention, and session rows with `last_activity_at` older than retention (idle that long = expired, whatever the status). `0` days = skip. |
| Config reader | `Helper/Data` (`isEnabled`, `getRetentionDays`) | |

Admin routes: `tonyapps_actionlog/log/index`, `tonyapps_actionlog/loginlog/index`, `tonyapps_actionlog/session/index`;
menu **Action Log** > Action Log / Login Log / Active Sessions / Configuration
(`adminhtml/system_config/edit/section/tonyapps_actionlog`).

Behaviour details:

- **Config redaction:** fields of type `obscure`, with an encrypted backend, or registered as sensitive config paths,
  plus any key containing password, secret, token, private_key, signature or api/access key.
- **Config scope:** `data_after` is `{"website": …, "store": …, "groups": {…}}`.
- **Snapshots** keep column and attribute values only (not images, stock, tier prices), in the store view of the
  change. Password hashes and tokens are redacted and left untouched by a restore.
- **Restore** is offered only for updates with a snapshot (`has_snapshot` flag), not for deletes (entity is gone).
  Needs `Tonyapps_ActionLog::restore` plus the entity's own edit permission (e.g. *Catalog → Products*).
- Watched entity options (`Model/Config/Source/WatchedEntity`): `Magento\Catalog\Model\Product`, `Category`,
  `Magento\Cms\Model\Page`, `Block`, `Magento\Customer\Model\Customer`.
- Rows without a signed-in admin (CLI, cron, setup scripts) display as *System (CLI / cron)*.

## Data model

Created by `setup:upgrade` (`etc/db_schema.xml`):

| Table | Columns |
|---|---|
| `tonyapps_action_log` | `log_id` PK, `admin_user_id`, `username`, `full_action_name`, `event_type` (default `action`), `entity_type`, `entity_id` (varchar 32), `model_class`, `data_before`, `data_after` (mediumtext JSON), `ip_address`, `user_agent`, `created_at`. Indexes: created_at, admin_user_id. |
| `tonyapps_admin_login_log` | `id` PK, `admin_user_id`, `username`, `status` (success/failed), `ip_address`, `user_agent`, `created_at`. Index: created_at. |
| `tonyapps_admin_session` | `id` PK, `admin_user_id`, `username`, `session_id` (varchar 64, SHA-256 hash), `ip_address`, `user_agent`, `login_at`, `last_activity_at` (on update), `status` (default `active`). Indexes: session_id, status. |

No FKs. The Action Log grid does not load `data_before`/`data_after` (it gets `has_snapshot`); the sessions grid does
not load `session_id`.

## Configuration and ACL

Section `tonyapps_actionlog` under the **Advanced** tab (**Stores → Configuration → Advanced → Action Log**), default
scope only. ACL `Tonyapps_ActionLog::config`.

| Path | Notes |
|---|---|
| `tonyapps_actionlog/general/enabled` | Off by default. |
| `tonyapps_actionlog/general/retention_days` | Logs and sessions idle longer than this are deleted by the 02:00 cron. `0` disables cleanup. |
| `tonyapps_actionlog/general/watched_models` | Multiselect of entity classes to snapshot for restore. |

ACL tree (**System → Permissions → User Roles → Action Log**):

- `Tonyapps_ActionLog::actionlog`
  - `::log` (Action Log) > `::restore` (Restore Entities)
  - `::loginlog` (Login Log)
  - `::session` (Active Sessions)
  - `::config` (Configuration)

## Decisions and constraints

- Session ids stored only as SHA-256 hashes, never raw.
- Snapshots only for admin saves (not storefront, cron, API); the backend session is a proxy so non-admin saves don't
  start one.
- Restore and terminate are POST-only.
- Grid data loads (`mui/index/render`) are ACL-checked per listing, not just the page.
- Action detection is by controller name pattern (see Architecture ponytail note).

## Known limitations and open points

- Session rows written before the 2026-10-30 hashing change hold raw ids that no longer match: those sessions can't be
  terminated from the grid and stop heartbeating. Clear with `TRUNCATE tonyapps_admin_session;` (admins stay logged
  in; their next login is tracked again), or wait for the cleanup cron.
- Restore covers columns/attributes only; not images, stock, tier prices; not deletes.
- Cleanup depends on Magento cron.
- No `etc/db_schema_whitelist.json`; `composer.json` requires only `magento/framework` although the module uses
  Backend, Config, Catalog, Cms, Customer and Eav classes.
- Uninstall leaves the three `tonyapps_*` tables; drop them manually.

## Tests

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Tonyapps/ActionLog/Test/Unit
```

Unit tests cover `Helper/Data`, Logger redaction and writes, `AdminSession` resource, grid collection, `Restorer`,
`AdminActionLogger`, all four plugins, and the grid columns. No integration tests.

## Change history

### 2026-11-09 grid readability

- Browser column of all three grids reads "Chrome 156 on macOS"; raw User-Agent in the cell tooltip.
- Login Log status is a badge (green Success, red Failed) with a working Success/Failed filter.
- Action Log rows without a signed-in admin (CLI, cron, setup scripts) show *System (CLI / cron)*; the request column
  is called **Request** and the restore column **Restore**.
- `mui_*` requests (admin UI saving grid views, columns, filters) no longer logged.
- Dates shown in the store timezone (Stores > Configuration > General > Locale Options).

### 2026-10-30 review fixes

- **Grids check ACL on data loads too**: each listing's data source declares its ACL resource, so `mui/index/render`
  refuses roles without *Action Log*, *Login Log* or *Active Sessions*.
- **Session ids stored as SHA-256 hashes**, never raw; the sessions grid doesn't load the column. Legacy raw rows: see
  Known limitations.
- **Action Log grid no longer loads `data_before`/`data_after`**; it gets a `has_snapshot` flag. Restore only for
  updates with a snapshot, not deletes.
- **Restore loads the store view the snapshot was taken in**, so store-view edits are rolled back.
- **Snapshots only for admin saves**, not storefront, cron or API saves. Backend session injected as a proxy.
- **Terminate is POST-only**.
- **More actions logged**: role saves, Login as Customer, order cancel/hold/unhold (single and mass).
- **Config changes record their scope**: `data_after` is `{"website": …, "store": …, "groups": {…}}`.

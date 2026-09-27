# VIRA public/legal site

These pages are served by the VIRA Laravel application at `vira.synteric.co.uk`.

From the repository root, deploy with:

```bash
./deploy.sh
```

Preview the transfer without changing the server:

```bash
DRY_RUN=true ./deploy.sh
```

The complete application is deployed, not only these legal pages. The server
user, host, application path, SSH key, port and public URL can be overridden with
`SERVER_USER`, `SERVER_HOST`, `SERVER_PATH`, `SSH_KEY`, `SSH_PORT`, and
`PUBLIC_URL` environment variables. Configure the domain's document root as the
application's `public/` directory.

For shared hosting where the document root cannot be changed, the root
`.htaccess` forwards all requests into `public/` while preventing directory
listing. The explicit `public/` document root remains the preferred setup.

The deployment prefers Composer on the server. Shared hosts that do not expose
Composer over SSH are supported automatically: ensure `composer install` has
been run locally so `vendor/autoload.php` exists, and the script will upload the
local dependency directory.

The production PHP CLI must provide the PDO extension configured in `.env`
(`pdo_pgsql` for `DB_CONNECTION=pgsql`, or `pdo_mysql` for MySQL). The script
checks this before entering maintenance mode and prints the available drivers.
If the hosting control panel exposes a different PHP binary, provide it through
`REMOTE_PHP`, for example `REMOTE_PHP=/usr/local/bin/php84 ./deploy.sh`.

For a brand-new database only, a failed non-transactional MySQL migration may
leave partial tables. `FRESH_DATABASE=true ./deploy.sh` drops all configured
database tables and migrates cleanly after an explicit `ERASE` confirmation.
Never use this option when production data must be retained.

URLs:
- https://vira.synteric.co.uk/
- https://vira.synteric.co.uk/privacy-policy/
- https://vira.synteric.co.uk/terms/
- https://vira.synteric.co.uk/data-deletion/

Before publishing:
1. Ensure privacy@synteric.co.uk exists and is monitored, or replace it with a working address.
2. Confirm Synteric is the correct legal/operator name.
3. Make sure the text matches the actual VIRA implementation, vendors, permissions and retention.
4. Confirm SSL works before deployment; `.htaccess` redirects HTTP traffic to HTTPS.
5. Have the final policies reviewed professionally before relying on them commercially.

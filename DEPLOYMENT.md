# Deploy Orbit Devices

## Vercel with external MySQL

The repository includes `vercel.json` and `api/` entry points for the community `vercel-php@0.7.4` runtime (PHP 8.3). Vercel functions are short lived and their local files do not persist. This deployment uses MySQL for products, sales, sessions, and uploaded images. Image uploads keep their original validated JPG, PNG, or WebP bytes; the Vercel runtime does not include gd for resizing.

1. Provide a persistent MySQL database reachable from Vercel's functions, with encrypted transport and a dedicated database user if your provider supports these. Never commit its password.
2. Run `php spark migrate` against that database. On an empty database, set `ORBIT_ADMIN_PASSWORD` (a unique 12–72 byte password) and run `php spark db:seed DemoSeeder` once. Remove the bootstrap password after the admin account exists.
3. Import `berrycole/Orbit-Devices-Pos` to Vercel as an Other-framework project. Keep the repository root as the project root. The included `vercel.json` configures the PHP functions and routing; do not set a static-output directory.
4. Set `CI_ENVIRONMENT=production`, `DB_DRIVER=MySQLi`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Vercel project environment variables. Set `APP_BASE_URL` to the final HTTPS domain followed by `/`; if omitted, the entry point uses Vercel's deployment URL.
5. Deploy and check `/health`, then sign in as `admin`. Record a sample sale, upload a product image, and revisit after a new deployment to verify persistence.

Vercel's [PHP community runtime](https://github.com/vercel-community/php) is maintained outside Vercel's official runtimes. Its extensions and behavior may change. Vercel's [function request-body limit](https://vercel.com/docs/functions/limitations) also applies to uploads; this app's 2 MB image limit stays below it. A conventional PHP host is simpler for long-term POS operation.

## Railway with MySQL

Railway supports the supplied Dockerfile and a MySQL service. Hosting uses Railway resources and may incur charges after available credits; review the plan in your account before selecting a paid subscription.

1. Create a Railway project from the GitHub repository `berrycole/orbit-devices-pos`.
2. Add a MySQL service to the same project. Keep its database volume enabled.
3. On the Orbit application service, attach a volume mounted at `/var/www/html/writable`. This preserves uploads and sessions across deployments.
4. Add the following application variables. Use Railway variable references for the MySQL values; adjust the service name if your MySQL service has a different name.

| Variable | Value |
| --- | --- |
| `CI_ENVIRONMENT` | `production` |
| `DB_DRIVER` | `MySQLi` |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
| `ORBIT_ADMIN_PASSWORD` | A unique 12–72 byte initial password |
| `APP_BASE_URL` | The final HTTPS URL followed by `/` |

5. Under Networking, generate a public domain for target port `80`. Set `APP_BASE_URL` to that exact HTTPS URL and redeploy.
6. The start script creates writable directories, runs migrations, and seeds an empty database when `ORBIT_ADMIN_PASSWORD` is present. Do not run these commands in a build step: persistent volumes are available at runtime.
7. Sign in as `admin`. Verify a product upload and sale, confirm stock/history, then restart once and verify data persists.
8. Remove `ORBIT_ADMIN_PASSWORD` from the hosting variables after successful initialization. The stored password hash remains. Manage password changes through Staff → Edit.

The Docker container uses Apache and PHP 8.3, installs the required extensions, serves only `public/`, and listens on port 80. Keep one application replica when using its local file sessions and upload volume. `/health` confirms the web process is responding; it does not validate database credentials.

## Other compatible hosts

Render can deploy PHP through Docker, with a persistent disk for `writable` and a separately supplied MySQL database. Traditional PHP hosting also works when it provides PHP 8.2+, the required extensions, MySQL, HTTPS, and a document root pointing to `public/`. Run Composer and migrations using SSH or your host's terminal.

## Backup and updates

Back up the MySQL database and `writable/uploads` together. Commit code updates to GitHub and redeploy; migrations run on startup. Do not import demo data into an existing store. Store secrets only in `.env` locally or the host's environment-variable settings. Never upload the local SQLite preview database as part of the repository.

Official references: [Railway MySQL](https://docs.railway.com/databases/mysql), [Dockerfiles](https://docs.railway.com/builds/dockerfiles), [Volumes](https://docs.railway.com/volumes), [Pricing](https://railway.com/pricing), [Render Docker](https://render.com/docs/docker).

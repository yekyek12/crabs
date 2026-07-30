# Deployment Guide

## Free Render Blueprint With PostgreSQL

This repo is configured for Render using `render.yaml`.

The Blueprint creates:

1. `crabs` - Laravel Docker web service.
2. `crabs-ai-service` - public FastAPI web service.
3. `crabs-postgres` - managed Render PostgreSQL database.

All three resources use Render's free tier in `render.yaml`.

Database data is stored in the managed `crabs-postgres` database, not in the web service container. Normal web service deploys run migrations only for schema changes and keep existing rows. The default demo/reference seeder is guarded: it seeds an empty database, then skips future deploys when users, crab species, or model versions already exist. Set `FORCE_DEFAULT_SEEDER=true` only when you intentionally want to reapply those defaults.

Deploy steps:

1. Commit and push the repository to GitHub.
2. In Render, choose New > Blueprint.
3. Connect the GitHub repo and branch `main`.
4. Use `render.yaml` as the Blueprint path.
5. Enter the external AI provider API keys when prompted.
6. Deploy the Blueprint.

Render generates the PostgreSQL password, Laravel `APP_KEY`, and AI service token. The Laravel service receives the database hostname and credentials through `fromDatabase` references.

Free-tier limits:

1. Free web services can spin down and cold-start after inactivity.
2. Free web services cannot use persistent disks, so files stored on the Laravel container filesystem are ephemeral.
3. Free PostgreSQL databases have fixed 1 GB storage and expire after 30 days.
4. The FastAPI service is public because Render private services do not have a free instance type. Requests are still protected by `AI_SERVICE_TOKEN`.
5. If Render changes the generated public URL for `crabs-ai-service`, update `AI_SERVICE_URL` on the `crabs` service.

After deployment:

1. Open the Laravel service URL.
2. Open `/health` on the Laravel service URL and confirm it returns `{"status":"ok","database":"ok"}`.
3. Check the deploy logs for `PostgreSQL is ready.`, successful migrations, and cache creation.
4. Register a new user, redeploy the Laravel service, and confirm the user can still log in after the deploy.
5. Change or remove the seeded demo accounts before public use.
6. Export important data before the free PostgreSQL expiry, or upgrade the database plan for persistence.
7. Configure `AI_MODEL_PATH`, `AI_MODEL_NAME`, `AI_MODEL_VERSION`, `AI_MODEL_CONFIDENCE_THRESHOLD`, and `AI_MODEL_CLASSES` on the FastAPI service when a trained model is available.
8. Sync model metadata from Admin > Models after deployment.
9. Replace placeholder species data before field evaluation.

## Manual Render Web Service

If you choose New > Web Service instead of New > Blueprint, Render ignores `render.yaml` and does not create `crabs-postgres` for you.

Create the database first:

1. In Render, choose New > Postgres.
2. Use name `crabs-postgres`, database `crabs`, user `crabs`, region `Singapore`, and plan `Free`.
3. Copy the database Internal Database URL.

Then configure the Laravel web service:

1. Choose New > Web Service.
2. Select the GitHub repo and branch `main`.
3. Runtime: Docker.
4. Add environment variables:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=` a Laravel app key or any long random string
   - `DATABASE_URL=` the Render Postgres Internal Database URL
   - `AI_SERVICE_URL=https://crabs-ai-service.onrender.com`
   - `AI_SERVICE_TOKEN=` same value used on the FastAPI service
   - `SESSION_DRIVER=database`
   - `CACHE_STORE=database`
   - `QUEUE_CONNECTION=database`
   - `FORCE_DEFAULT_SEEDER=false`
5. Remove any old MySQL variables from the web service, especially `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=crabs`, `DB_USERNAME=root`, and `DB_PASSWORD`.

The startup script maps Render's `DATABASE_URL` to Laravel's PostgreSQL connection automatically. If the service still logs `Connection: mysql, Host: 127.0.0.1`, the Render web service still has old MySQL environment variables set.

Do not use `DB_CONNECTION=sqlite` for Render production data unless you also attach durable storage on a paid service. Render's default web service filesystem is recreated on deploys and restarts, so SQLite files and uploaded local files are not durable there.

## Generic Production Notes

1. Configure HTTPS for the Laravel domain; camera APIs require a secure context outside localhost.
2. Set production values for `APP_URL`, `SESSION_SECURE_COOKIE=true`, and AI service values.
3. Serve `public/` as the web root.
4. Run the FastAPI service behind private networking and protect it with `AI_SERVICE_TOKEN`.
5. Configure backups for the database and `storage/app/private`.

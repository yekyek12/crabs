# Deployment Guide

## Free Render Blueprint With PostgreSQL

This repo is configured for Render using `render.yaml`.

The Blueprint creates:

1. `crabs` - Laravel Docker web service.
2. `crabs-ai-service` - public FastAPI web service.
3. `crabs-postgres` - managed Render PostgreSQL database.

All three resources use Render's free tier in `render.yaml`.

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
2. Check the deploy logs for `PostgreSQL is ready.`, successful migrations, and cache creation.
3. Change or remove the seeded demo accounts before public use.
4. Export important data before the free PostgreSQL expiry, or upgrade the database plan for persistence.
5. Configure `AI_MODEL_PATH`, `AI_MODEL_NAME`, `AI_MODEL_VERSION`, `AI_MODEL_CONFIDENCE_THRESHOLD`, and `AI_MODEL_CLASSES` on the FastAPI service when a trained model is available.
6. Sync model metadata from Admin > Models after deployment.
7. Replace placeholder species data before field evaluation.

## Generic Production Notes

1. Configure HTTPS for the Laravel domain; camera APIs require a secure context outside localhost.
2. Set production values for `APP_URL`, `SESSION_SECURE_COOKIE=true`, and AI service values.
3. Serve `public/` as the web root.
4. Run the FastAPI service behind private networking and protect it with `AI_SERVICE_TOKEN`.
5. Configure backups for the database and `storage/app/private`.

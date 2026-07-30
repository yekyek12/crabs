# Deployment Guide

## Render Blueprint With PostgreSQL

This repo is configured for Render using `render.yaml`.

The Blueprint creates:

1. `crabs` - Laravel Docker web service.
2. `crabs-ai-service` - private FastAPI service.
3. `crabs-postgres` - managed Render PostgreSQL database.

Deploy steps:

1. Commit and push the repository to GitHub.
2. In Render, choose New > Blueprint.
3. Connect the GitHub repo and branch `main`.
4. Use `render.yaml` as the Blueprint path.
5. Enter the external AI provider API keys when prompted.
6. Deploy the Blueprint.

Render generates the PostgreSQL password, Laravel `APP_KEY`, and AI service token. The Laravel service receives the database hostname and credentials through `fromDatabase` references.

After deployment:

1. Open the Laravel service URL.
2. Check the deploy logs for `PostgreSQL is ready.`, successful migrations, and cache creation.
3. Change or remove the seeded demo accounts before public use.
4. Configure database backups with `pg_dump` or Render PostgreSQL backups according to your selected database plan.
5. Configure `AI_MODEL_PATH`, `AI_MODEL_NAME`, `AI_MODEL_VERSION`, `AI_MODEL_CONFIDENCE_THRESHOLD`, and `AI_MODEL_CLASSES` on the FastAPI service when a trained model is available.
6. Sync model metadata from Admin > Models after deployment.
7. Replace placeholder species data before field evaluation.

## Generic Production Notes

1. Configure HTTPS for the Laravel domain; camera APIs require a secure context outside localhost.
2. Set production values for `APP_URL`, `SESSION_SECURE_COOKIE=true`, and AI service values.
3. Serve `public/` as the web root.
4. Run the FastAPI service behind private networking and protect it with `AI_SERVICE_TOKEN`.
5. Configure backups for the database and `storage/app/private`.

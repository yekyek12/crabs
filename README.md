# Crab Recognition AI

AI-Based Mobile Progressive Web Application for crab recognition using Laravel and a separate Python FastAPI inference service.

This repository contains the Laravel PWA shell, recognition workflow, private image storage, role-based access, species directory, feedback, admin dashboard, PWA manifest/service worker, and a FastAPI adapter service. No trained crab model or manuscript dataset was found in the workspace, so sample species data and the Python predictor are clearly marked placeholders.

## Install Laravel App

Start Apache and MySQL in XAMPP Control Panel v3.3.0, then create the local database:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS crabs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve
```

XAMPP users can run PHP with `C:\xampp\php\php.exe` if PHP is not on PATH.

## Start AI Service

```bash
cd ai-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 9000
```

## Environment

```env
AI_SERVICE_URL=http://127.0.0.1:9000
AI_SERVICE_TOKEN=
AI_REQUEST_TIMEOUT=60
AI_CONFIDENCE_THRESHOLD=0.60
AI_HIGH_CONFIDENCE_THRESHOLD=0.85
AI_CONSENSUS_ENABLED=true
AI_MIN_PROVIDER_AGREEMENT=2
AI_ALLOW_SINGLE_PROVIDER_RESULT=true
AI_GLOBAL_DETECTION=true
AI_PROVIDER_ORDER=gemini,anthropic,groq,openrouter,cohere,wisdomgate
AI_MODEL_PATH=
AI_MODEL_NAME="YOLO Crab Recognition Adapter"
AI_MODEL_VERSION=placeholder-1.0.0
AI_MODEL_CONFIDENCE_THRESHOLD=0.60
AI_MODEL_CLASSES=
```

Set `AI_MODEL_PATH` in the FastAPI environment to load a trained Ultralytics YOLO model. When no model path is configured, the adapter keeps returning the safe placeholder response.

## Deploy To Render

The included `render.yaml` provisions the Laravel web app, a private FastAPI service, and a private MySQL 8.4 service with persistent storage. See `docs/DEPLOYMENT.md` for the Render Blueprint steps.

## Added Feature Areas

- Admin species CRUD with model class mapping.
- Admin feedback review with expert species corrections and retraining flags.
- AI model registry, active-version control, and model metadata sync.
- Recognition performance dashboard with low-confidence, failed-scan, reviewed-accuracy, and top-species metrics.
- Detection-box overlay on result images when the AI returns normalized bounding boxes.
- Offline scan queue in the PWA scanner.
- Species directory search and filtering.
- CSV and PDF recognition-history exports.
- Optional scan location and capture notes.
- Confidence-based result guidance.

## Test Accounts

- Administrator: `admin@example.com`
- User: `test@example.com`

Factory passwords use Laravel's default generated password. Set known passwords with Tinker or update the seeder before production use.

## AI Endpoints

- `GET /health`
- `GET /api/v1/model`
- `POST /api/v1/predict`

## Important Limitations

- No trained YOLOv26 weights, class-order file, preprocessing details, or evaluation metrics were present.
- The FastAPI service validates images and returns deterministic placeholder no-detection responses until `AI_MODEL_PATH` points to a trained model and the optional model dependency is installed.
- Seeded species information is placeholder text and must be replaced with verified manuscript/project data.
- Recognition results are decision-support only and are not legal, medical, biological, food-safety, or scientific certification.

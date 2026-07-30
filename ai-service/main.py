from time import perf_counter
from typing import Optional
import io
import os

from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image, ImageStat

APP_TOKEN = os.getenv("AI_SERVICE_TOKEN", "")
MODEL_NAME = os.getenv("AI_MODEL_NAME", "YOLO Crab Recognition Adapter")
MODEL_VERSION = os.getenv("AI_MODEL_VERSION", "placeholder-1.0.0")
CONFIDENCE = float(os.getenv("AI_PLACEHOLDER_CONFIDENCE", "0.0"))
MODEL_PATH = os.getenv("AI_MODEL_PATH", "")
MODEL_CONFIDENCE_THRESHOLD = float(os.getenv("AI_MODEL_CONFIDENCE_THRESHOLD", "0.60"))
IMAGE_MAX_EDGE = int(os.getenv("AI_IMAGE_MAX_EDGE", "1600"))
CLASS_NAMES = [item.strip() for item in os.getenv("AI_MODEL_CLASSES", "").split(",") if item.strip()]
MODEL_LOAD_ERROR = ""
YOLO_MODEL = None

if MODEL_PATH:
    try:
        from ultralytics import YOLO  # type: ignore

        if not os.path.exists(MODEL_PATH):
            MODEL_LOAD_ERROR = f"Configured model file does not exist: {MODEL_PATH}"
        else:
            YOLO_MODEL = YOLO(MODEL_PATH)
            MODEL_NAME = os.getenv("AI_MODEL_NAME", "Ultralytics YOLO Crab Model")
    except Exception as exc:
        MODEL_LOAD_ERROR = str(exc)

app = FastAPI(title="Crab Recognition AI Service", version="1.0.0")


def require_token(authorization: Optional[str]) -> None:
    if APP_TOKEN and authorization != f"Bearer {APP_TOKEN}":
        raise HTTPException(status_code=401, detail="Invalid AI service token")


def optimize_for_inference(picture: Image.Image) -> Image.Image:
    if IMAGE_MAX_EDGE <= 0 or max(picture.size) <= IMAGE_MAX_EDGE:
        return picture

    optimized = picture.copy()
    optimized.thumbnail((IMAGE_MAX_EDGE, IMAGE_MAX_EDGE), Image.Resampling.LANCZOS)
    return optimized


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_loaded": YOLO_MODEL is not None,
        "model_error": MODEL_LOAD_ERROR or None,
        "model": {"name": MODEL_NAME, "version": MODEL_VERSION},
    }


@app.get("/")
def root():
    return health()


@app.get("/api/v1/model")
def model():
    names = getattr(YOLO_MODEL, "names", None) if YOLO_MODEL is not None else None
    if isinstance(names, dict):
        classes = [{"id": key, "name": value} for key, value in names.items()]
    elif CLASS_NAMES:
        classes = [{"id": index, "name": value} for index, value in enumerate(CLASS_NAMES)]
    else:
        classes = []

    return {
        "name": MODEL_NAME,
        "version": MODEL_VERSION,
        "classes": classes,
        "model_loaded": YOLO_MODEL is not None,
        "model_error": MODEL_LOAD_ERROR or None,
        "confidence_threshold": MODEL_CONFIDENCE_THRESHOLD,
    }


@app.post("/api/v1/predict")
async def predict(image: UploadFile = File(...), authorization: Optional[str] = Header(default=None)):
    require_token(authorization)
    start = perf_counter()
    if image.content_type not in {"image/jpeg", "image/png", "image/webp"}:
        raise HTTPException(status_code=415, detail="Unsupported image type")
    data = await image.read()
    if len(data) > 8 * 1024 * 1024:
        raise HTTPException(status_code=413, detail="Image is too large")
    try:
        picture = Image.open(io.BytesIO(data)).convert("RGB")
    except Exception as exc:
        raise HTTPException(status_code=422, detail="Corrupt or unreadable image") from exc

    picture = optimize_for_inference(picture)
    stat = ImageStat.Stat(picture.resize((1, 1)))
    brightness = round(sum(stat.mean) / 765, 4)
    warnings = []
    if brightness < 0.20:
        warnings.append("The image is too dark. Move to a brighter area.")

    if YOLO_MODEL is not None:
        results = YOLO_MODEL(picture, verbose=False)
        result = results[0] if results else None
        boxes = getattr(result, "boxes", None) if result is not None else None
        if boxes is None or len(boxes) == 0:
            return {
                "success": False,
                "prediction": None,
                "image_quality": {"blur_score": None, "brightness_score": brightness, "acceptable": not warnings, "warnings": warnings or ["No crab detection was returned by the model."]},
                "model": {"name": MODEL_NAME, "version": MODEL_VERSION},
                "processing_time_ms": int((perf_counter() - start) * 1000),
            }

        confidences = boxes.conf.detach().cpu().numpy().tolist()
        classes = boxes.cls.detach().cpu().numpy().astype(int).tolist()
        xyxy = boxes.xyxy.detach().cpu().numpy().tolist()
        best_index = max(range(len(confidences)), key=lambda index: confidences[index])
        class_id = classes[best_index]
        model_names = getattr(result, "names", None) or getattr(YOLO_MODEL, "names", {}) or {}
        class_name = model_names.get(class_id) if isinstance(model_names, dict) else None
        if not class_name and 0 <= class_id < len(CLASS_NAMES):
            class_name = CLASS_NAMES[class_id]
        class_name = class_name or f"class_{class_id}"
        x1, y1, x2, y2 = xyxy[best_index]
        width, height = picture.size
        confidence = round(float(confidences[best_index]), 5)
        if confidence < MODEL_CONFIDENCE_THRESHOLD:
            warnings.append("Model confidence is below the configured threshold.")

        return {
            "success": True,
            "prediction": {
                "class_name": class_name,
                "class_id": class_id,
                "confidence": confidence,
                "bounding_box": {
                    "x1": round(x1 / width, 5),
                    "y1": round(y1 / height, 5),
                    "x2": round(x2 / width, 5),
                    "y2": round(y2 / height, 5),
                },
            },
            "image_quality": {"blur_score": None, "brightness_score": brightness, "acceptable": not warnings, "warnings": warnings},
            "model": {"name": MODEL_NAME, "version": MODEL_VERSION},
            "processing_time_ms": int((perf_counter() - start) * 1000),
        }

    return {
        "success": False,
        "prediction": None,
        "image_quality": {"blur_score": None, "brightness_score": brightness, "acceptable": not warnings, "warnings": warnings or [MODEL_LOAD_ERROR or "No trained model file is configured on the AI service."]},
        "model": {"name": MODEL_NAME, "version": MODEL_VERSION},
        "processing_time_ms": int((perf_counter() - start) * 1000),
    }

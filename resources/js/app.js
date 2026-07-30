import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { createIcons, Activity, AlertTriangle, ArrowRight, BadgeCheck, BarChart3, Bot, Camera, CheckCircle2, ClipboardCheck, Clock3, Database, Download, Eye, EyeOff, ExternalLink, FileSearch, FileText, Filter, Gauge, Globe2, History, Home, Info, Leaf, LockKeyhole, LogIn, LogOut, Mail, MapPin, Menu, MessageCircle, Moon, Pencil, Plus, RefreshCw, Save, ScanLine, Search, SendHorizontal, Settings, ShieldAlert, ShieldCheck, Smartphone, Sparkles, Sun, Trash2, Upload, UserPlus, UserRound, Users, WifiOff, X } from 'lucide';

createIcons({ icons: { Activity, AlertTriangle, ArrowRight, BadgeCheck, BarChart3, Bot, Camera, CheckCircle2, ClipboardCheck, Clock3, Database, Download, Eye, EyeOff, ExternalLink, FileSearch, FileText, Filter, Gauge, Globe2, History, Home, Info, Leaf, LockKeyhole, LogIn, LogOut, Mail, MapPin, Menu, MessageCircle, Moon, Pencil, Plus, RefreshCw, Save, ScanLine, Search, SendHorizontal, Settings, ShieldAlert, ShieldCheck, Smartphone, Sparkles, Sun, Trash2, Upload, UserPlus, UserRound, Users, WifiOff, X } });

const themeToggle = document.querySelector('[data-theme-toggle]');
const themeLabel = themeToggle?.querySelector('[data-theme-label]');
const themeColorMeta = document.querySelector('meta[name="theme-color"]');

function setAppTheme(theme, persist = true) {
    const nextTheme = theme === 'night' ? 'night' : 'day';
    const isNight = nextTheme === 'night';

    document.documentElement.dataset.theme = nextTheme;
    document.documentElement.style.colorScheme = isNight ? 'dark' : 'light';
    themeColorMeta?.setAttribute('content', isNight ? '#071a18' : '#0f766e');

    if (themeToggle) {
        themeToggle.setAttribute('aria-pressed', String(isNight));
        themeToggle.setAttribute('aria-label', isNight ? 'Switch to day theme' : 'Switch to night theme');
        themeToggle.title = isNight ? 'Switch to day theme' : 'Switch to night theme';
    }

    if (themeLabel) {
        themeLabel.textContent = isNight ? 'Day theme' : 'Night theme';
    }

    if (persist) {
        try {
            localStorage.setItem('crabai-theme', nextTheme);
        } catch {
            // Theme still applies for this page even when storage is unavailable.
        }
    }
}

setAppTheme(document.documentElement.dataset.theme, false);
themeToggle?.addEventListener('click', () => {
    setAppTheme(document.documentElement.dataset.theme === 'night' ? 'day' : 'night');
});

let deferredInstallPrompt;
let serviceWorkerReady = false;
let installPromptWaiters = [];

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const baseUrl = document.querySelector('meta[name="app-base-url"]')?.content || window.location.origin;
        navigator.serviceWorker.register(`${baseUrl.replace(/\/$/, '')}/sw.js`)
            .then(() => navigator.serviceWorker.ready)
            .then(() => {
                serviceWorkerReady = true;
                updateInstallButtonState();
            })
            .catch(() => {
                serviceWorkerReady = false;
            });
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    installPromptWaiters.forEach((resolve) => resolve(event));
    installPromptWaiters = [];
    updateInstallButtonState();
});

const installAppButton = document.getElementById('installAppButton');
const authModal = document.getElementById('authModal');
const loginModalForm = document.getElementById('loginModalForm');
const registerModalForm = document.getElementById('registerModalForm');
const startButton = document.getElementById('startCamera');
const captureButton = document.getElementById('capture');
const video = document.getElementById('camera');
const canvas = document.getElementById('snapshot');
const input = document.getElementById('imageInput');
const cameraStage = document.querySelector('.camera-stage');
const imageInputLabel = document.getElementById('imageInputLabel');
const scanForm = document.getElementById('scanForm');
const analyzeButton = document.getElementById('analyzeButton');
const analyzeStatus = document.getElementById('analyzeStatus');
const locateScanButton = document.getElementById('locateScan');
const latitudeInput = document.getElementById('latitudeInput');
const longitudeInput = document.getElementById('longitudeInput');
const locationAccuracyInput = document.getElementById('locationAccuracyInput');
const locationLabelInput = document.getElementById('locationLabelInput');
const locationStatus = document.getElementById('locationStatus');
const offlineQueuePanel = document.getElementById('offlineQueuePanel');
const offlineQueueCount = document.getElementById('offlineQueueCount');
const syncOfflineQueueButton = document.getElementById('syncOfflineQueue');
const checklistItems = Array.from(document.querySelectorAll('.capture-check'));
const autoCaptureCheckItems = Array.from(document.querySelectorAll('input[type="hidden"][name="capture_checks[]"]'));
const checklistProgress = document.getElementById('checklistProgress');
const checklistStatus = document.getElementById('checklistStatus');
const manuallyCheckedCaptureChecks = new Set();
const ANALYSIS_IMAGE_MAX_EDGE = 1280;
const ANALYSIS_IMAGE_QUALITY = 0.72;
const ANALYSIS_IMAGE_SMALL_FILE_BYTES = 700 * 1024;
const MAX_DEVICE_LOCATION_ACCURACY_METERS = Math.max(1, Number(scanForm?.dataset.maxLocationAccuracy || 100));
const chatShell = document.querySelector('.crab-chat');
const chatForm = document.getElementById('crabChatForm');
const chatInput = document.getElementById('crabChatInput');
const chatLog = document.getElementById('chatLog');
const chatSuggestions = document.getElementById('chatSuggestions');
const moreSheet = document.getElementById('moreSheet');
const moreOpenButtons = Array.from(document.querySelectorAll('[data-more-open]'));
const tutorialSheet = document.getElementById('pageTutorial');
const tutorialOpenButtons = Array.from(document.querySelectorAll('[data-tutorial-open]'));
const tutorialMobileQuery = window.matchMedia('(max-width: 640px), (pointer: coarse)');
const mapBoard = document.querySelector('[data-map-points]');
const mapGrid = document.getElementById('recognitionMapGrid');
const fullscreenLoader = document.getElementById('fullscreenLoader');
const fullscreenLoaderTitle = fullscreenLoader?.querySelector('[data-loading-title]');
const fullscreenLoaderDetail = fullscreenLoader?.querySelector('[data-loading-detail]');
let stream;
let recognitionLeafletMap;
let scanImagePreparedForSubmit = false;

function showFullscreenLoader(message = 'Please wait', detail = 'Preparing your request.') {
    if (!fullscreenLoader) return;

    if (fullscreenLoaderTitle) {
        fullscreenLoaderTitle.textContent = message;
    }

    if (fullscreenLoaderDetail) {
        fullscreenLoaderDetail.textContent = detail;
    }

    fullscreenLoader.hidden = false;
    document.body.classList.add('is-loading');
}

function hideFullscreenLoader() {
    if (!fullscreenLoader) return;

    fullscreenLoader.hidden = true;
    document.body.classList.remove('is-loading');
}

function isRunningAsInstalledApp() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
        || document.referrer.startsWith('android-app://');
}

async function isAppAlreadyInstalled() {
    if (isRunningAsInstalledApp()) {
        return true;
    }

    if ('getInstalledRelatedApps' in navigator) {
        try {
            const relatedApps = await navigator.getInstalledRelatedApps();

            return relatedApps.length > 0;
        } catch {
            return false;
        }
    }

    return false;
}

async function updateInstallButtonState() {
    if (!installAppButton) return;

    if (await isAppAlreadyInstalled()) {
        installAppButton.hidden = false;
        installAppButton.classList.add('is-installed');
        installAppButton.setAttribute('aria-label', 'App already installed');
        installAppButton.title = 'App already installed';
        installAppButton.querySelector('span')?.replaceChildren('Installed');
        return;
    }

    installAppButton.classList.remove('is-installed');
    installAppButton.hidden = false;
    installAppButton.setAttribute('aria-label', 'Install app');
    installAppButton.title = deferredInstallPrompt ? 'Install app' : 'Preparing install';
    installAppButton.querySelector('span')?.replaceChildren('Install App');
}

function waitForInstallPrompt(timeout = 4000) {
    if (deferredInstallPrompt) {
        return Promise.resolve(deferredInstallPrompt);
    }

    return new Promise((resolve) => {
        const timer = setTimeout(() => {
            installPromptWaiters = installPromptWaiters.filter((waiter) => waiter !== resolve);
            resolve(null);
        }, timeout);

        installPromptWaiters.push((event) => {
            clearTimeout(timer);
            resolve(event);
        });
    });
}

function openAuthModal(mode = 'login') {
    if (!authModal || !loginModalForm || !registerModalForm) return false;

    const isRegister = mode === 'register';
    loginModalForm.hidden = isRegister;
    registerModalForm.hidden = !isRegister;
    authModal.hidden = false;
    document.body.classList.add('modal-open');
    createIcons({ icons: { Eye, EyeOff, LogIn, LockKeyhole, Mail, UserPlus, UserRound, X } });

    const firstInput = (isRegister ? registerModalForm : loginModalForm).querySelector('input');
    setTimeout(() => firstInput?.focus(), 30);

    return true;
}

function closeAuthModal() {
    if (!authModal) return;

    authModal.hidden = true;
    document.body.classList.remove('modal-open');
}

function tutorialStorageKey() {
    return `crabai-tutorial-seen:${tutorialSheet?.dataset.tutorialKey || window.location.pathname}`;
}

function wasTutorialSeen() {
    try {
        return localStorage.getItem(tutorialStorageKey()) === '1';
    } catch {
        return false;
    }
}

function rememberTutorialSeen() {
    try {
        localStorage.setItem(tutorialStorageKey(), '1');
    } catch {
        // The tutorial still closes even when storage is unavailable.
    }
}

function openTutorialSheet({ focusClose = true } = {}) {
    if (!tutorialSheet) return;

    closeMoreSheet();
    tutorialSheet.hidden = false;
    document.body.classList.add('tutorial-open');
    tutorialOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
    createIcons({ icons: { Info, X } });

    if (focusClose) {
        setTimeout(() => tutorialSheet.querySelector('button[data-tutorial-close]')?.focus(), 30);
    }
}

function closeTutorialSheet({ remember = true } = {}) {
    if (!tutorialSheet) return;

    tutorialSheet.hidden = true;
    document.body.classList.remove('tutorial-open');
    tutorialOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));

    if (remember) {
        rememberTutorialSeen();
    }
}

function maybeShowTutorialOnboarding() {
    if (!tutorialSheet || !tutorialMobileQuery.matches || wasTutorialSeen()) return;

    setTimeout(() => {
        if (tutorialSheet.hidden && !wasTutorialSeen()) {
            openTutorialSheet({ focusClose: false });
        }
    }, 450);
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-tutorial-open]')) {
        event.preventDefault();
        openTutorialSheet();
        return;
    }

    if (event.target.closest('[data-tutorial-close]')) {
        closeTutorialSheet();
        return;
    }

    const trigger = event.target.closest('[data-auth-modal]');
    if (trigger && openAuthModal(trigger.dataset.authModal)) {
        event.preventDefault();
        return;
    }

    if (event.target.closest('[data-auth-close]')) {
        closeAuthModal();
    }

    if (event.target.closest('[data-more-open]')) {
        openMoreSheet();
    }

    if (event.target.closest('[data-more-close]')) {
        closeMoreSheet();
    }

    const passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle) {
        const input = passwordToggle.closest('.password-field')?.querySelector('input');
        if (!input) return;

        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(shouldShow));
        passwordToggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && authModal && !authModal.hidden) {
        closeAuthModal();
    }

    if (event.key === 'Escape' && moreSheet && !moreSheet.hidden) {
        closeMoreSheet();
    }

    if (event.key === 'Escape' && tutorialSheet && !tutorialSheet.hidden) {
        closeTutorialSheet();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-loading-form]');
    if (!form || (typeof form.checkValidity === 'function' && !form.checkValidity())) return;

    showFullscreenLoader(form.dataset.loadingMessage, form.dataset.loadingDetail);
    form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach((control) => {
        control.disabled = true;
        control.setAttribute('aria-busy', 'true');
    });
});

window.addEventListener('pageshow', hideFullscreenLoader);

function openMoreSheet() {
    if (!moreSheet) return;

    moreSheet.hidden = false;
    document.body.classList.add('more-open');
    moreOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'true'));
    createIcons({ icons: { BarChart3, Database, FileText, Leaf, MapPin, UserRound, X } });
}

function closeMoreSheet() {
    if (!moreSheet) return;

    moreSheet.hidden = true;
    document.body.classList.remove('more-open');
    moreOpenButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
}

installAppButton?.addEventListener('click', async () => {
    if (await isAppAlreadyInstalled()) {
        alert('Crab Recognition AI is already installed on this device.');
        updateInstallButtonState();
        return;
    }

    if (!deferredInstallPrompt) {
        installAppButton.setAttribute('aria-busy', 'true');
        installAppButton.querySelector('span')?.replaceChildren('Preparing...');

        if ('serviceWorker' in navigator && !serviceWorkerReady) {
            try {
                await navigator.serviceWorker.ready;
                serviceWorkerReady = true;
            } catch {
                serviceWorkerReady = false;
            }
        }

        await waitForInstallPrompt();

        installAppButton.removeAttribute('aria-busy');
        installAppButton.querySelector('span')?.replaceChildren('Install App');

        if (!deferredInstallPrompt) {
            alert('The app is still preparing for installation. Use Chrome or Edge, refresh the page once, wait a few seconds, then tap Install App again.');
        }

        return;
    }

    deferredInstallPrompt.prompt();
    const choice = await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;

    if (choice.outcome === 'accepted') {
        alert('Crab Recognition AI is being installed on this device.');
    }

    updateInstallButtonState();
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    alert('Crab Recognition AI has been installed on this device.');
    updateInstallButtonState();
});

window.matchMedia('(display-mode: standalone)').addEventListener?.('change', updateInstallButtonState);
updateInstallButtonState();
maybeShowTutorialOnboarding();

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
        video.srcObject = stream;
        cameraStage?.classList.add('has-camera');
        updateCaptureChecklistState();
    } catch {
        alert('Camera permission was denied or no camera was found. Please upload an image from your gallery.');
    }
}

function captureImage() {
    if (!stream) {
        alert('Open the camera first or upload an image from your gallery.');
        return;
    }
    const sourceWidth = video.videoWidth || ANALYSIS_IMAGE_MAX_EDGE;
    const sourceHeight = video.videoHeight || ANALYSIS_IMAGE_MAX_EDGE;
    const scale = Math.min(1, ANALYSIS_IMAGE_MAX_EDGE / sourceWidth, ANALYSIS_IMAGE_MAX_EDGE / sourceHeight);
    canvas.width = Math.max(1, Math.round(sourceWidth * scale));
    canvas.height = Math.max(1, Math.round(sourceHeight * scale));
    const context = canvas.getContext('2d');
    if (!context) {
        alert('The camera image could not be prepared. Please upload an image from your gallery.');
        return;
    }

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    canvas.toBlob((blob) => {
        const file = new File([blob], 'crab-capture.jpg', { type: 'image/jpeg' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        scanImagePreparedForSubmit = false;
        resetCaptureChecklistForNewImage();
    }, 'image/jpeg', ANALYSIS_IMAGE_QUALITY);
}

function optimizedImageName(file) {
    const baseName = (file.name || 'crab-scan').replace(/\.[^.]+$/, '');

    return `${baseName}-optimized.jpg`;
}

function canOptimizeImage(file) {
    return file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
}

function imageSourceSize(source) {
    return {
        width: source.width || source.naturalWidth || 0,
        height: source.height || source.naturalHeight || 0,
    };
}

function loadImageElement(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Image could not be prepared for analysis.'));
        };
        image.src = url;
    });
}

async function decodeImageFile(file) {
    if ('createImageBitmap' in window) {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            return loadImageElement(file);
        }
    }

    return loadImageElement(file);
}

function canvasToJpegBlob(canvasElement) {
    return new Promise((resolve) => {
        canvasElement.toBlob(resolve, 'image/jpeg', ANALYSIS_IMAGE_QUALITY);
    });
}

async function optimizeImageForAnalysis(file, { preserveMetadata = false } = {}) {
    if (!canOptimizeImage(file) || preserveMetadata) {
        return file;
    }

    const source = await decodeImageFile(file);
    const { width, height } = imageSourceSize(source);
    if (!width || !height) {
        source.close?.();
        return file;
    }

    const scale = Math.min(1, ANALYSIS_IMAGE_MAX_EDGE / width, ANALYSIS_IMAGE_MAX_EDGE / height);
    const shouldResize = scale < 1;
    const shouldRecompress = file.type !== 'image/jpeg' || file.size > ANALYSIS_IMAGE_SMALL_FILE_BYTES;
    if (!shouldResize && !shouldRecompress) {
        source.close?.();
        return file;
    }

    const targetWidth = Math.max(1, Math.round(width * scale));
    const targetHeight = Math.max(1, Math.round(height * scale));
    const workCanvas = document.createElement('canvas');
    workCanvas.width = targetWidth;
    workCanvas.height = targetHeight;

    const context = workCanvas.getContext('2d', { alpha: false });
    if (!context) {
        source.close?.();
        return file;
    }

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, targetWidth, targetHeight);
    context.drawImage(source, 0, 0, targetWidth, targetHeight);
    source.close?.();

    const blob = await canvasToJpegBlob(workCanvas);
    if (!blob || (!shouldResize && blob.size >= file.size * 0.92)) {
        return file;
    }

    return new File([blob], optimizedImageName(file), {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });
}

function replaceScanInputFile(file) {
    try {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        return input.files?.[0] === file || input.files?.[0]?.name === file.name;
    } catch {
        return false;
    }
}

async function prepareScanImageForSubmit() {
    const file = input?.files?.[0];
    if (!file) return;

    const needsPhotoMetadata = !hasScanCoordinates();
    if (analyzeStatus) {
        analyzeStatus.hidden = false;
        analyzeStatus.textContent = needsPhotoMetadata
            ? 'Keeping photo GPS metadata for validation...'
            : 'Optimizing image for faster analysis...';
    }

    const optimizedFile = await optimizeImageForAnalysis(file, { preserveMetadata: needsPhotoMetadata });
    if (optimizedFile === file) return;

    if (replaceScanInputFile(optimizedFile) && imageInputLabel) {
        imageInputLabel.textContent = optimizedFile.name;
    }
}

function resetCaptureChecklist() {
    manuallyCheckedCaptureChecks.clear();
    syncCaptureChecklistDom();
}

function syncCaptureChecklistDom() {
    checklistItems.forEach((item) => {
        const checked = manuallyCheckedCaptureChecks.has(item.value);
        item.checked = checked;
        item.defaultChecked = false;
        if (!checked) {
            item.removeAttribute('checked');
        }
        item.closest('li')?.classList.toggle('is-checked', checked);
    });
}

function resetCaptureChecklistForNewImage() {
    resetCaptureChecklist();
    updateCaptureChecklistState();

    requestAnimationFrame(() => {
        resetCaptureChecklist();
        updateCaptureChecklistState();
    });
}

function captureChecklistValuesForSubmission() {
    if (checklistItems.length === 0) {
        return autoCaptureCheckItems.map((item) => item.value).filter(Boolean);
    }

    return checklistItems
        .filter((item) => manuallyCheckedCaptureChecks.has(item.value))
        .map((item) => item.value);
}

function hasScanCoordinates() {
    if (!latitudeInput || !longitudeInput) return true;

    if (latitudeInput.value.trim() === '' || longitudeInput.value.trim() === '') return false;

    const accuracy = Number(locationAccuracyInput?.value);

    return Number.isFinite(accuracy) && accuracy > 0 && accuracy <= MAX_DEVICE_LOCATION_ACCURACY_METERS;
}

function isScanReady() {
    const hasImage = Boolean(input?.files?.length);
    const allChecksComplete = checklistItems.length === 0
        ? autoCaptureCheckItems.length > 0
        : checklistItems.every((item) => manuallyCheckedCaptureChecks.has(item.value));

    return hasImage && allChecksComplete;
}

function updateCaptureChecklistState() {
    if (!scanForm) return;

    if (checklistItems.length > 0) {
        syncCaptureChecklistDom();
    }

    const checkedCount = checklistItems.filter((item) => manuallyCheckedCaptureChecks.has(item.value)).length;
    const hasImage = Boolean(input?.files?.length);
    const hasLocation = hasScanCoordinates();
    const ready = isScanReady();

    if (imageInputLabel) {
        imageInputLabel.textContent = hasImage ? input.files[0].name : 'JPEG, PNG, or WEBP';
    }

    if (checklistProgress) {
        checklistProgress.textContent = `${checkedCount}/${checklistItems.length} ready`;
        checklistProgress.classList.toggle('is-ready', checkedCount === checklistItems.length);
    }

    if (checklistStatus) {
        if (ready) {
            checklistStatus.textContent = hasLocation ? 'Ready to analyze.' : 'Ready if the uploaded photo has GPS metadata.';
        } else if (!hasImage) {
            checklistStatus.textContent = 'Select or capture an image first.';
        } else {
            checklistStatus.textContent = 'Complete every checklist item before analysis.';
        }
        checklistStatus.classList.toggle('is-ready', ready);
    }

    if (analyzeButton && !scanForm.classList.contains('is-analyzing')) {
        analyzeButton.disabled = !ready;
        analyzeButton.querySelector('.button-label').textContent = ready ? 'Analyze Image' : (!hasImage ? 'Select Image' : 'Complete Checklist');
    }
}

function setLocationStatus(message, ready = false, warning = false) {
    if (!locationStatus) return;

    locationStatus.textContent = message;
    locationStatus.classList.toggle('is-ready', ready);
    locationStatus.classList.toggle('is-warning', warning);
}

function preciseLocationRequiredMessage() {
    return `Precise GPS required: use location within ${Math.round(MAX_DEVICE_LOCATION_ACCURACY_METERS)}m or a GPS-tagged photo`;
}

function formatLocationAccuracy(meters) {
    const accuracy = Number(meters);
    if (!Number.isFinite(accuracy) || accuracy <= 0) return null;

    if (accuracy >= 1000) {
        return `+/- ${(accuracy / 1000).toFixed(1)} km`;
    }

    return `+/- ${Math.round(accuracy)} m`;
}

function isSecureLocationContext() {
    if (window.isSecureContext) return true;

    return ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
}

function clearLocationInputs() {
    if (latitudeInput) latitudeInput.value = '';
    if (longitudeInput) longitudeInput.value = '';
    if (locationAccuracyInput) locationAccuracyInput.value = '';
}

async function geolocationPermissionState() {
    if (!navigator.permissions?.query) return 'prompt';

    try {
        const permission = await navigator.permissions.query({ name: 'geolocation' });
        return permission.state;
    } catch {
        return 'prompt';
    }
}

function currentPosition(options) {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, options);
    });
}

function watchedPosition(options, timeout = 22000) {
    return new Promise((resolve, reject) => {
        let watchId;
        let bestPosition = null;
        const timer = window.setTimeout(() => {
            if (watchId !== undefined) {
                navigator.geolocation.clearWatch(watchId);
            }
            if (bestPosition) {
                resolve(bestPosition);
                return;
            }
            reject({ code: 3 });
        }, timeout);

        watchId = navigator.geolocation.watchPosition((position) => {
            if (!bestPosition || locationAccuracy(position) < locationAccuracy(bestPosition)) {
                bestPosition = position;
            }

            if (isReliablePosition(position)) {
                window.clearTimeout(timer);
                navigator.geolocation.clearWatch(watchId);
                resolve(position);
            }
        }, (error) => {
            window.clearTimeout(timer);
            navigator.geolocation.clearWatch(watchId);
            if (bestPosition) {
                resolve(bestPosition);
                return;
            }
            reject(error);
        }, options);
    });
}

async function bestMobileLocation() {
    const attempts = [
        () => currentPosition({ enableHighAccuracy: true, timeout: 22000, maximumAge: 0 }),
        () => watchedPosition({ enableHighAccuracy: true, maximumAge: 0 }, 30000),
        () => currentPosition({ enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 }),
    ];
    let lastError = null;
    let bestPosition = null;

    for (const attempt of attempts) {
        try {
            const position = await attempt();
            if (!bestPosition || locationAccuracy(position) < locationAccuracy(bestPosition)) {
                bestPosition = position;
            }
            if (isReliablePosition(position)) {
                return position;
            }
        } catch (error) {
            lastError = error;
            if (error?.code === 1) break;
        }
    }

    if (bestPosition) {
        throw { code: 'LOW_ACCURACY', accuracy: locationAccuracy(bestPosition) };
    }

    throw lastError || { code: 2 };
}

function geolocationErrorMessage(error) {
    if (error?.code === 'LOW_ACCURACY') {
        const formattedAccuracy = formatLocationAccuracy(error.accuracy);

        return `GPS is too broad${formattedAccuracy ? ` (${formattedAccuracy})` : ''}. Move outdoors and try again.`;
    }

    if (error?.code === 1) {
        return 'Location blocked. Enable site location permission in browser settings.';
    }

    if (error?.code === 2) {
        return 'Location unavailable. Turn on device location and try again.';
    }

    if (error?.code === 3) {
        return 'Location timed out. Try again with a clearer signal.';
    }

    return 'Location could not be attached';
}

function locationAccuracy(position) {
    const accuracy = Number(position?.coords?.accuracy);

    return Number.isFinite(accuracy) && accuracy > 0 ? accuracy : Number.POSITIVE_INFINITY;
}

function isReliablePosition(position) {
    return locationAccuracy(position) <= MAX_DEVICE_LOCATION_ACCURACY_METERS;
}

function attachScanLocation(position) {
    const latitude = position.coords.latitude.toFixed(7);
    const longitude = position.coords.longitude.toFixed(7);
    const accuracy = Number(position.coords.accuracy);
    const formattedAccuracy = formatLocationAccuracy(accuracy);
    if (!isReliablePosition(position)) {
        clearLocationInputs();
        setLocationStatus(`GPS is too broad${formattedAccuracy ? ` (${formattedAccuracy})` : ''}. Move outdoors and try again.`, false, true);
        updateCaptureChecklistState();

        return false;
    }

    latitudeInput.value = latitude;
    longitudeInput.value = longitude;
    if (locationAccuracyInput) {
        locationAccuracyInput.value = Number.isFinite(accuracy) ? accuracy.toFixed(2) : '';
    }
    if (locationLabelInput && !locationLabelInput.value.trim()) {
        locationLabelInput.value = formattedAccuracy ? `${latitude}, ${longitude} (${formattedAccuracy})` : `${latitude}, ${longitude}`;
    }

    setLocationStatus(formattedAccuracy ? `Reliable GPS attached (${formattedAccuracy})` : 'Reliable GPS attached', true);
    updateCaptureChecklistState();

    return true;
}

locateScanButton?.addEventListener('click', async () => {
    if (!navigator.geolocation || !latitudeInput || !longitudeInput) {
        clearLocationInputs();
        setLocationStatus('Location unavailable on this browser.', false, true);
        updateCaptureChecklistState();
        return;
    }

    if (!isSecureLocationContext()) {
        clearLocationInputs();
        setLocationStatus('Location needs HTTPS on mobile.', false, true);
        updateCaptureChecklistState();
        return;
    }

    locateScanButton.disabled = true;
    locateScanButton.setAttribute('aria-busy', 'true');

    try {
        const permissionState = await geolocationPermissionState();
        if (permissionState === 'denied') {
            clearLocationInputs();
            setLocationStatus('Location blocked. Enable site permission.', false, true);
            updateCaptureChecklistState();
            return;
        }

        setLocationStatus(permissionState === 'prompt' ? `Allow location when prompted (+/- ${Math.round(MAX_DEVICE_LOCATION_ACCURACY_METERS)}m target).` : `Searching for precise GPS (+/- ${Math.round(MAX_DEVICE_LOCATION_ACCURACY_METERS)}m target)...`);
        attachScanLocation(await bestMobileLocation());
    } catch (error) {
        clearLocationInputs();
        setLocationStatus(geolocationErrorMessage(error), false, true);
        updateCaptureChecklistState();
    } finally {
        locateScanButton.disabled = false;
        locateScanButton.removeAttribute('aria-busy');
    }
});

function queueId() {
    return window.crypto?.randomUUID ? window.crypto.randomUUID() : `scan-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function openScanQueueDb() {
    return new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error('Offline storage is unavailable in this browser.'));
            return;
        }

        const request = indexedDB.open('crabai-offline-scans', 1);
        request.onupgradeneeded = () => request.result.createObjectStore('scans', { keyPath: 'id' });
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error('Offline queue could not open.'));
    });
}

function queueTransaction(mode = 'readonly') {
    return openScanQueueDb().then((db) => db.transaction('scans', mode).objectStore('scans'));
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(reader.error || new Error('Image could not be queued.'));
        reader.readAsDataURL(file);
    });
}

function dataUrlToFile(dataUrl, filename, fallbackType = 'image/jpeg') {
    const [prefix, data] = dataUrl.split(',');
    const mime = prefix.match(/data:(.*?);base64/)?.[1] || fallbackType;
    const binary = atob(data);
    const bytes = new Uint8Array(binary.length);
    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return new File([bytes], filename, { type: mime });
}

async function allQueuedScans() {
    const store = await queueTransaction();

    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

async function updateOfflineQueueUi() {
    if (!offlineQueuePanel || !offlineQueueCount) return;

    try {
        const queued = await allQueuedScans();
        offlineQueueCount.textContent = queued.length;
        offlineQueuePanel.hidden = queued.length === 0;
        offlineQueuePanel.classList.toggle('is-offline', queued.length > 0 && !navigator.onLine);
        if (syncOfflineQueueButton) {
            syncOfflineQueueButton.disabled = queued.length === 0 || !navigator.onLine;
            syncOfflineQueueButton.toggleAttribute('aria-disabled', syncOfflineQueueButton.disabled);
        }
    } catch {
        offlineQueuePanel.hidden = true;
        if (syncOfflineQueueButton) {
            syncOfflineQueueButton.disabled = true;
            syncOfflineQueueButton.setAttribute('aria-disabled', 'true');
        }
    }
}

async function saveOfflineScan() {
    const file = input?.files?.[0];
    if (!scanForm || !file) return;

    const store = await queueTransaction('readwrite');
    const record = {
        id: queueId(),
        action: scanForm.action,
        createdAt: new Date().toISOString(),
        filename: file.name || 'offline-crab-scan.jpg',
        type: file.type || 'image/jpeg',
        dataUrl: await readFileAsDataUrl(file),
        fields: {
            latitude: latitudeInput?.value || '',
            longitude: longitudeInput?.value || '',
            location_accuracy_meters: locationAccuracyInput?.value || '',
            location_label: locationLabelInput?.value || '',
            capture_notes: document.getElementById('captureNotesInput')?.value || '',
            capture_checks: captureChecklistValuesForSubmission(),
        },
    };

    await new Promise((resolve, reject) => {
        const request = store.put(record);
        request.onsuccess = resolve;
        request.onerror = () => reject(request.error);
    });
}

async function deleteQueuedScan(id) {
    const store = await queueTransaction('readwrite');
    await new Promise((resolve, reject) => {
        const request = store.delete(id);
        request.onsuccess = resolve;
        request.onerror = () => reject(request.error);
    });
}

async function replayQueuedScans() {
    if (!navigator.onLine) {
        updateOfflineQueueUi();
        return;
    }

    syncOfflineQueueButton?.setAttribute('aria-busy', 'true');
    syncOfflineQueueButton?.setAttribute('disabled', 'disabled');

    try {
        const queued = await allQueuedScans();
        for (const record of queued) {
            const formData = new FormData();
            formData.append('image', dataUrlToFile(record.dataUrl, record.filename, record.type));
            Object.entries(record.fields || {}).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => formData.append(`${key}[]`, item));
                    return;
                }
                formData.append(key, value);
            });

            const response = await fetch(record.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'text/html,application/xhtml+xml',
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (response.ok || response.redirected) {
                await deleteQueuedScan(record.id);
            }
        }
    } catch (error) {
        console.error(error);
    } finally {
        syncOfflineQueueButton?.removeAttribute('aria-busy');
        updateOfflineQueueUi();
    }
}

startButton?.addEventListener('click', startCamera);
captureButton?.addEventListener('click', captureImage);
input?.addEventListener('change', () => {
    scanImagePreparedForSubmit = false;
    resetCaptureChecklistForNewImage();
});
checklistItems.forEach((item) => item.addEventListener('change', (event) => {
    if (event.isTrusted && item.checked) {
        manuallyCheckedCaptureChecks.add(item.value);
    } else {
        manuallyCheckedCaptureChecks.delete(item.value);
    }

    updateCaptureChecklistState();
}));
resetCaptureChecklist();
updateCaptureChecklistState();
updateOfflineQueueUi();
window.addEventListener('pageshow', () => {
    resetCaptureChecklistForNewImage();
});
window.addEventListener('online', replayQueuedScans);
window.addEventListener('offline', updateOfflineQueueUi);
syncOfflineQueueButton?.addEventListener('click', replayQueuedScans);
scanForm?.addEventListener('submit', async (event) => {
    if (!isScanReady()) {
        event.preventDefault();
        updateCaptureChecklistState();
        if (!input?.files?.length) {
            scanForm.reportValidity();
        }
        return;
    }

    if (!scanImagePreparedForSubmit) {
        event.preventDefault();
        scanForm.classList.add('is-analyzing');
        if (analyzeButton) {
            analyzeButton.disabled = true;
            analyzeButton.setAttribute('aria-busy', 'true');
            analyzeButton.querySelector('.button-label').textContent = 'Preparing...';
        }
        startButton?.setAttribute('disabled', 'disabled');
        captureButton?.setAttribute('disabled', 'disabled');

        try {
            await prepareScanImageForSubmit();
            scanImagePreparedForSubmit = true;
        } catch (error) {
            scanForm.classList.remove('is-analyzing');
            if (analyzeButton) {
                analyzeButton.disabled = false;
                analyzeButton.removeAttribute('aria-busy');
            }
            startButton?.removeAttribute('disabled');
            captureButton?.removeAttribute('disabled');
            updateCaptureChecklistState();
            alert(error.message || 'The image could not be optimized. Please try another image.');
            return;
        }

        if (!navigator.onLine) {
            try {
                await saveOfflineScan();
                scanForm.reset();
                scanImagePreparedForSubmit = false;
                if (locationAccuracyInput) {
                    locationAccuracyInput.value = '';
                }
                resetCaptureChecklistForNewImage();
                setLocationStatus(preciseLocationRequiredMessage());
                if (analyzeStatus) {
                    analyzeStatus.hidden = false;
                    analyzeStatus.textContent = 'Scan queued for upload when connection returns.';
                }
                scanForm.classList.remove('is-analyzing');
                startButton?.removeAttribute('disabled');
                captureButton?.removeAttribute('disabled');
                updateCaptureChecklistState();
                await updateOfflineQueueUi();
            } catch (error) {
                scanForm.classList.remove('is-analyzing');
                if (analyzeButton) {
                    analyzeButton.disabled = false;
                    analyzeButton.removeAttribute('aria-busy');
                }
                startButton?.removeAttribute('disabled');
                captureButton?.removeAttribute('disabled');
                updateCaptureChecklistState();
                alert(error.message || 'The scan could not be queued offline.');
            }
            return;
        }

        scanForm.requestSubmit();
        return;
    }

    if (!navigator.onLine) {
        event.preventDefault();
        try {
            await saveOfflineScan();
            scanForm.reset();
            scanImagePreparedForSubmit = false;
            if (locationAccuracyInput) {
                locationAccuracyInput.value = '';
            }
            resetCaptureChecklistForNewImage();
            setLocationStatus(preciseLocationRequiredMessage());
            if (analyzeStatus) {
                analyzeStatus.hidden = false;
                analyzeStatus.textContent = 'Scan queued for upload when connection returns.';
            }
            scanForm.classList.remove('is-analyzing');
            startButton?.removeAttribute('disabled');
            captureButton?.removeAttribute('disabled');
            updateCaptureChecklistState();
            await updateOfflineQueueUi();
        } catch (error) {
            scanForm.classList.remove('is-analyzing');
            if (analyzeButton) {
                analyzeButton.disabled = false;
                analyzeButton.removeAttribute('aria-busy');
            }
            startButton?.removeAttribute('disabled');
            captureButton?.removeAttribute('disabled');
            updateCaptureChecklistState();
            alert(error.message || 'The scan could not be queued offline.');
        }
        return;
    }

    scanForm.classList.add('is-analyzing');
    if (analyzeButton) {
        analyzeButton.disabled = true;
        analyzeButton.setAttribute('aria-busy', 'true');
        analyzeButton.querySelector('.button-label').textContent = 'Analyzing...';
    }
    if (analyzeStatus) {
        analyzeStatus.hidden = false;
        analyzeStatus.textContent = 'Analyzing image with AI providers...';
    }
    startButton?.setAttribute('disabled', 'disabled');
    captureButton?.setAttribute('disabled', 'disabled');
});

function appendChatMessage(role, text) {
    if (!chatLog) return null;

    const message = document.createElement('article');
    message.className = `chat-message ${role === 'user' ? 'user-message' : 'bot-message'}`;
    message.innerHTML = `
        <span class="chat-avatar"><i data-lucide="${role === 'user' ? 'user-round' : 'bot'}"></i></span>
        <div class="chat-bubble">
            <strong>${role === 'user' ? 'You' : 'CrabAI'}</strong>
            <p></p>
        </div>
    `;
    message.querySelector('p').textContent = text;
    chatLog.appendChild(message);
    createIcons({ icons: { Bot, UserRound } });
    message.scrollIntoView({ block: 'end', behavior: 'smooth' });

    return message;
}

function appendTypingMessage() {
    if (!chatLog) return null;

    const message = document.createElement('article');
    message.className = 'chat-message bot-message typing-message';
    message.innerHTML = `
        <span class="chat-avatar"><i data-lucide="bot"></i></span>
        <div class="chat-bubble">
            <strong>CrabAI</strong>
            <p><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span><span class="chat-typing-dot"></span></p>
        </div>
    `;
    chatLog.appendChild(message);
    createIcons({ icons: { Bot } });
    message.scrollIntoView({ block: 'end', behavior: 'smooth' });

    return message;
}

async function sendCrabQuestion(message) {
    if (!chatShell || !chatForm || !chatInput) return;

    appendChatMessage('user', message);
    chatInput.value = '';
    chatInput.style.height = '';
    chatForm.classList.add('is-sending');
    chatForm.querySelector('button')?.setAttribute('disabled', 'disabled');
    const typing = appendTypingMessage();

    try {
        const response = await fetch(chatShell.dataset.chatEndpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ message }),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Crab chat is unavailable.');

        typing?.remove();
        appendChatMessage('bot', data.answer);
        if (Array.isArray(data.suggestions) && chatSuggestions) {
            chatSuggestions.innerHTML = '';
            data.suggestions.slice(0, 3).forEach((suggestion) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.chatPrompt = suggestion;
                button.textContent = suggestion;
                chatSuggestions.appendChild(button);
            });
        }
    } catch (error) {
        typing?.remove();
        appendChatMessage('bot', error.message || 'Crab chat is unavailable right now.');
    } finally {
        chatForm.classList.remove('is-sending');
        chatForm.querySelector('button')?.removeAttribute('disabled');
        chatInput.focus();
    }
}

chatInput?.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = `${Math.min(chatInput.scrollHeight, 120)}px`;
});

chatForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const message = chatInput?.value.trim();
    if (message) sendCrabQuestion(message);
});

chatSuggestions?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-chat-prompt]');
    if (button) sendCrabQuestion(button.dataset.chatPrompt);
});

function renderRecognitionMap() {
    if (!mapBoard || !mapGrid) return;

    let points = [];
    let rangeLayers = [];
    try {
        points = JSON.parse(mapBoard.dataset.mapPoints || '[]');
    } catch {
        points = [];
    }
    try {
        rangeLayers = JSON.parse(mapBoard.dataset.rangeLayers || '[]');
    } catch {
        rangeLayers = [];
    }

    const validPoints = points
        .map((point) => ({
            ...point,
            latitude: Number(point.latitude),
            longitude: Number(point.longitude),
            accuracy: point.accuracy === null || point.accuracy === undefined ? null : Number(point.accuracy),
        }))
        .filter((point) => Number.isFinite(point.latitude) && Number.isFinite(point.longitude));
    const drawableRangeLayers = rangeLayers
        .map((layer) => ({
            ...layer,
            regions: (layer.regions || []).filter((region) => isValidLeafletBounds(region.bounds)),
        }))
        .filter((layer) => layer.regions.length > 0);
    const hasMapContent = validPoints.length > 0 || drawableRangeLayers.length > 0;

    const emptyState = mapGrid.querySelector('.map-empty-state');
    emptyState?.toggleAttribute('hidden', hasMapContent);
    mapBoard.classList.toggle('is-empty', !hasMapContent);
    if (!hasMapContent) return;

    if (recognitionLeafletMap) {
        recognitionLeafletMap.remove();
    }

    recognitionLeafletMap = L.map(mapGrid, {
        scrollWheelZoom: false,
        tap: true,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(recognitionLeafletMap);

    const bounds = [];
    const rangeBounds = [];
    let focusedMarker = null;
    let focusedLatLng = null;
    drawableRangeLayers.forEach((layer, layerIndex) => {
        const color = globalRangeColor(layerIndex);
        layer.regions.forEach((region) => {
            L.rectangle(region.bounds, {
                color,
                weight: 1.6,
                opacity: 0.74,
                dashArray: '7 5',
                fillColor: color,
                fillOpacity: 0.08,
            })
                .bindPopup(buildGlobalRangePopup(layer, region))
                .addTo(recognitionLeafletMap);

            rangeBounds.push(region.bounds[0], region.bounds[1]);
        });
    });

    validPoints.forEach((point) => {
        const latLng = [point.latitude, point.longitude];
        const color = '#dc2626';
        const marker = L.marker(latLng, {
            icon: L.divIcon({
                className: `crab-map-marker location-pin${point.focused ? ' is-focused' : ''}`,
                html: '<span aria-hidden="true"></span>',
                iconSize: [30, 40],
                iconAnchor: [15, 38],
                popupAnchor: [0, -34],
            }),
            title: point.species || 'Unknown crab',
        }).addTo(recognitionLeafletMap);

        marker.bindPopup(buildRecognitionMapPopup(point));
        if (point.focused) {
            focusedMarker = marker;
            focusedLatLng = latLng;
        }

        if (Number.isFinite(point.accuracy) && point.accuracy > 0) {
            L.circle(latLng, {
                radius: point.accuracy,
                color,
                weight: 1,
                opacity: 0.55,
                fillColor: color,
                fillOpacity: 0.08,
            }).addTo(recognitionLeafletMap);
        }

        bounds.push(latLng);
    });

    if (focusedMarker && focusedLatLng) {
        recognitionLeafletMap.setView(focusedLatLng, 16);
    } else if (bounds.length === 1) {
        if (rangeBounds.length > 0) {
            recognitionLeafletMap.fitBounds(L.latLngBounds([...bounds, ...rangeBounds]).pad(0.12), { maxZoom: 8 });
        } else {
            recognitionLeafletMap.setView(bounds[0], 15);
        }
    } else {
        recognitionLeafletMap.fitBounds(L.latLngBounds([...bounds, ...rangeBounds]).pad(0.16), { maxZoom: bounds.length > 0 ? (rangeBounds.length > 0 ? 8 : 16) : 5 });
    }

    mapGrid.classList.add('is-leaflet-ready');
    window.setTimeout(() => {
        recognitionLeafletMap?.invalidateSize();
    }, 80);
}

function globalRangeColor(index) {
    return ['#2563eb', '#0f766e', '#c2410c', '#7c3aed', '#0e7490', '#be123c'][index % 6];
}

function isValidLeafletBounds(bounds) {
    if (!Array.isArray(bounds) || bounds.length !== 2) return false;

    return bounds.every((point) => Array.isArray(point)
        && point.length === 2
        && Number.isFinite(Number(point[0]))
        && Number.isFinite(Number(point[1])));
}

function appendPopupRow(container, label, value) {
    if (!value) return;

    const row = document.createElement('p');
    const rowLabel = document.createElement('span');
    const rowValue = document.createElement('strong');
    rowLabel.textContent = label;
    rowValue.textContent = value;
    row.append(rowLabel, rowValue);
    container.appendChild(row);
}

function buildRecognitionMapPopup(point) {
    const popup = document.createElement('article');
    popup.className = 'crab-map-popup';

    if (point.image_url) {
        const media = document.createElement('figure');
        media.className = 'crab-map-popup-media';

        const image = document.createElement('img');
        image.src = point.image_url;
        image.alt = `${point.species || 'Crab'} scan image`;
        image.loading = 'lazy';

        media.appendChild(image);
        popup.appendChild(media);
    }

    const title = document.createElement('h3');
    title.textContent = point.species || 'Unknown crab';
    popup.appendChild(title);

    appendPopupRow(popup, 'Scan', point.reference);
    appendPopupRow(popup, 'Confidence', point.confidence === null || point.confidence === undefined ? 'N/A' : `${point.confidence}% ${point.level || ''}`.trim());
    appendPopupRow(popup, 'Location', point.location || point.coordinates);
    appendPopupRow(popup, 'Reliability', point.location_reliability);
    appendPopupRow(popup, 'GPS accuracy', formatLocationAccuracy(point.accuracy));
    if (point.global_range) {
        appendPopupRow(popup, 'Possible global range', point.global_range.label || point.global_range.range_text);
    }
    appendPopupRow(popup, 'Captured', point.date);
    appendPopupRow(popup, 'Status', point.status ? point.status.replaceAll('_', ' ') : null);
    if (point.ai_consensus) {
        appendPopupRow(popup, 'AI reliability', point.ai_consensus.label);
        appendPopupRow(popup, 'AI coverage', `${point.ai_consensus.provider_count}/${point.ai_consensus.required_provider_count} providers`);
        appendPopupRow(popup, 'AI agreement', `${point.ai_consensus.agreement_count}/${point.ai_consensus.usable_provider_count} usable, min ${point.ai_consensus.minimum_agreement}`);
        appendPopupRow(popup, 'AI issues', point.ai_consensus.provider_errors_count > 0 ? `${point.ai_consensus.provider_errors_count} provider issue(s)` : 'None');
    }
    appendPopupRow(popup, 'User', point.captured_by);

    const actions = document.createElement('div');
    actions.className = 'crab-map-popup-actions';

    const link = document.createElement('a');
    link.href = point.url;
    link.className = 'crab-map-popup-link';
    link.textContent = 'Open scan';
    actions.appendChild(link);

    if (point.maps_url) {
        const gpsLink = document.createElement('a');
        gpsLink.href = point.maps_url;
        gpsLink.className = 'crab-map-popup-link muted';
        gpsLink.target = '_blank';
        gpsLink.rel = 'noopener';
        gpsLink.textContent = 'GPS map';
        actions.appendChild(gpsLink);
    }

    popup.appendChild(actions);

    return popup;
}

function buildGlobalRangePopup(layer, region) {
    const popup = document.createElement('article');
    popup.className = 'crab-map-popup range-popup';

    const title = document.createElement('h3');
    title.textContent = layer.species || 'Possible crab range';
    popup.appendChild(title);

    appendPopupRow(popup, 'Scientific name', layer.scientific_name);
    appendPopupRow(popup, 'Region', region.label || layer.label);
    appendPopupRow(popup, 'Range', layer.range_text || layer.label);
    appendPopupRow(popup, 'Layer type', layer.source === 'ai_range_text' ? 'AI range interpretation' : 'Supported species range');

    return popup;
}

renderRecognitionMap();
window.addEventListener('pagehide', () => stream?.getTracks().forEach((track) => track.stop()));

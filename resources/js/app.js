import { createIcons, Activity, AlertTriangle, ArrowRight, BadgeCheck, BarChart3, Bot, Camera, CheckCircle2, ClipboardCheck, Clock3, Database, Download, Eye, EyeOff, ExternalLink, FileSearch, FileText, Filter, Gauge, History, Home, Info, Leaf, LockKeyhole, LogIn, LogOut, Mail, MapPin, Menu, MessageCircle, Moon, Pencil, Plus, RefreshCw, Save, ScanLine, Search, SendHorizontal, Settings, ShieldAlert, ShieldCheck, Smartphone, Sparkles, Sun, Trash2, Upload, UserPlus, UserRound, Users, WifiOff, X } from 'lucide';

createIcons({ icons: { Activity, AlertTriangle, ArrowRight, BadgeCheck, BarChart3, Bot, Camera, CheckCircle2, ClipboardCheck, Clock3, Database, Download, Eye, EyeOff, ExternalLink, FileSearch, FileText, Filter, Gauge, History, Home, Info, Leaf, LockKeyhole, LogIn, LogOut, Mail, MapPin, Menu, MessageCircle, Moon, Pencil, Plus, RefreshCw, Save, ScanLine, Search, SendHorizontal, Settings, ShieldAlert, ShieldCheck, Smartphone, Sparkles, Sun, Trash2, Upload, UserPlus, UserRound, Users, WifiOff, X } });

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
const locationLabelInput = document.getElementById('locationLabelInput');
const locationStatus = document.getElementById('locationStatus');
const offlineQueuePanel = document.getElementById('offlineQueuePanel');
const offlineQueueCount = document.getElementById('offlineQueueCount');
const syncOfflineQueueButton = document.getElementById('syncOfflineQueue');
const checklistItems = Array.from(document.querySelectorAll('.capture-check'));
const checklistProgress = document.getElementById('checklistProgress');
const checklistStatus = document.getElementById('checklistStatus');
const chatShell = document.querySelector('.crab-chat');
const chatForm = document.getElementById('crabChatForm');
const chatInput = document.getElementById('crabChatInput');
const chatLog = document.getElementById('chatLog');
const chatSuggestions = document.getElementById('chatSuggestions');
const moreSheet = document.getElementById('moreSheet');
const moreOpenButtons = Array.from(document.querySelectorAll('[data-more-open]'));
const mapBoard = document.querySelector('[data-map-points]');
const mapGrid = document.getElementById('recognitionMapGrid');
const fullscreenLoader = document.getElementById('fullscreenLoader');
const fullscreenLoaderTitle = fullscreenLoader?.querySelector('[data-loading-title]');
const fullscreenLoaderDetail = fullscreenLoader?.querySelector('[data-loading-detail]');
let stream;

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

document.addEventListener('click', (event) => {
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
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    canvas.toBlob((blob) => {
        const file = new File([blob], 'crab-capture.jpg', { type: 'image/jpeg' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        updateCaptureChecklistState();
    }, 'image/jpeg', 0.86);
}

function isScanReady() {
    const hasImage = Boolean(input?.files?.length);
    const allChecksComplete = checklistItems.length > 0 && checklistItems.every((item) => item.checked);

    return hasImage && allChecksComplete;
}

function updateCaptureChecklistState() {
    if (!scanForm || checklistItems.length === 0) return;

    const checkedCount = checklistItems.filter((item) => item.checked).length;
    const hasImage = Boolean(input?.files?.length);
    const ready = isScanReady();

    if (imageInputLabel) {
        imageInputLabel.textContent = hasImage ? input.files[0].name : 'JPEG, PNG, or WEBP';
    }

    checklistItems.forEach((item) => {
        item.closest('li')?.classList.toggle('is-checked', item.checked);
    });

    if (checklistProgress) {
        checklistProgress.textContent = `${checkedCount}/${checklistItems.length} ready`;
        checklistProgress.classList.toggle('is-ready', checkedCount === checklistItems.length);
    }

    if (checklistStatus) {
        if (ready) {
            checklistStatus.textContent = 'Ready to analyze.';
        } else if (!hasImage) {
            checklistStatus.textContent = 'Select or capture an image first.';
        } else {
            checklistStatus.textContent = 'Complete every checklist item before analysis.';
        }
        checklistStatus.classList.toggle('is-ready', ready);
    }

    if (analyzeButton && !scanForm.classList.contains('is-analyzing')) {
        analyzeButton.disabled = !ready;
        analyzeButton.querySelector('.button-label').textContent = ready ? 'Analyze Image' : (hasImage ? 'Complete Checklist' : 'Select Image');
    }
}

function setLocationStatus(message, ready = false) {
    if (!locationStatus) return;

    locationStatus.textContent = message;
    locationStatus.classList.toggle('is-ready', ready);
}

locateScanButton?.addEventListener('click', () => {
    if (!navigator.geolocation || !latitudeInput || !longitudeInput) {
        setLocationStatus('Location unavailable');
        return;
    }

    setLocationStatus('Locating...');
    navigator.geolocation.getCurrentPosition((position) => {
        const latitude = position.coords.latitude.toFixed(7);
        const longitude = position.coords.longitude.toFixed(7);
        latitudeInput.value = latitude;
        longitudeInput.value = longitude;
        if (locationLabelInput && !locationLabelInput.value.trim()) {
            locationLabelInput.value = `${latitude}, ${longitude}`;
        }
        setLocationStatus('Location attached', true);
    }, () => {
        setLocationStatus('Location permission denied');
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
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
        offlineQueuePanel.hidden = queued.length === 0 && navigator.onLine;
        offlineQueuePanel.classList.toggle('is-offline', !navigator.onLine);
    } catch {
        offlineQueuePanel.hidden = true;
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
            location_label: locationLabelInput?.value || '',
            capture_notes: document.getElementById('captureNotesInput')?.value || '',
            capture_checks: checklistItems.filter((item) => item.checked).map((item) => item.value),
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

    updateOfflineQueueUi();
}

startButton?.addEventListener('click', startCamera);
captureButton?.addEventListener('click', captureImage);
input?.addEventListener('change', updateCaptureChecklistState);
checklistItems.forEach((item) => item.addEventListener('change', updateCaptureChecklistState));
updateCaptureChecklistState();
updateOfflineQueueUi();
window.addEventListener('online', replayQueuedScans);
window.addEventListener('offline', updateOfflineQueueUi);
syncOfflineQueueButton?.addEventListener('click', replayQueuedScans);
scanForm?.addEventListener('submit', async (event) => {
    if (!isScanReady()) {
        event.preventDefault();
        updateCaptureChecklistState();
        scanForm.reportValidity();
        return;
    }

    if (!navigator.onLine) {
        event.preventDefault();
        try {
            await saveOfflineScan();
            scanForm.reset();
            updateCaptureChecklistState();
            setLocationStatus('Location optional');
            if (analyzeStatus) {
                analyzeStatus.hidden = false;
                analyzeStatus.textContent = 'Scan queued for upload when connection returns.';
            }
            await updateOfflineQueueUi();
        } catch (error) {
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
    if (analyzeStatus) analyzeStatus.hidden = false;
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
    try {
        points = JSON.parse(mapBoard.dataset.mapPoints || '[]');
    } catch {
        points = [];
    }

    mapGrid.querySelectorAll('.map-marker').forEach((marker) => marker.remove());
    mapGrid.querySelector('.map-empty-state')?.toggleAttribute('hidden', points.length > 0);
    if (points.length === 0) return;

    const latitudes = points.map((point) => Number(point.latitude)).filter(Number.isFinite);
    const longitudes = points.map((point) => Number(point.longitude)).filter(Number.isFinite);
    const minLat = Math.min(...latitudes);
    const maxLat = Math.max(...latitudes);
    const minLng = Math.min(...longitudes);
    const maxLng = Math.max(...longitudes);
    const latRange = Math.max(0.0001, maxLat - minLat);
    const lngRange = Math.max(0.0001, maxLng - minLng);

    points.forEach((point) => {
        const latitude = Number(point.latitude);
        const longitude = Number(point.longitude);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

        const left = 8 + (((longitude - minLng) / lngRange) * 84);
        const top = 8 + (((maxLat - latitude) / latRange) * 84);
        const marker = document.createElement('a');
        marker.className = `map-marker ${point.level || 'unknown'}`;
        marker.href = point.url;
        marker.style.left = `${left}%`;
        marker.style.top = `${top}%`;
        const icon = document.createElement('i');
        icon.dataset.lucide = 'map-pin';
        const label = document.createElement('span');
        const title = document.createElement('strong');
        const detail = document.createElement('small');
        title.textContent = point.species || 'Unknown crab';
        detail.textContent = `${point.confidence === null ? 'N/A' : `${point.confidence}%`} - ${point.location || point.reference}`;
        label.append(title, detail);
        marker.append(icon, label);
        mapGrid.appendChild(marker);
    });

    createIcons({ icons: { MapPin } });
}

renderRecognitionMap();
window.addEventListener('pagehide', () => stream?.getTracks().forEach((track) => track.stop()));

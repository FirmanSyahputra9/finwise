window.finwiseToast = window.finwiseToast || {
    show(message, type = 'success', duration = 3000) {
        window.dispatchEvent(new CustomEvent('app-toast', { detail: { message, type, duration } }));
    },
    dismiss() {
        window.dispatchEvent(new CustomEvent('app-toast', { detail: { message: '', duration: 1 } }));
    },
};

function bindDashboardCat() {
    const cat = document.getElementById('dashboard-cat');
    const preview = document.getElementById('profile-cat-preview');
    const swatches = document.querySelectorAll('[data-pet-kind][data-pet-color]');
    const sparkleControls = document.querySelectorAll('[data-sparkle-setting][data-sparkle-value]');
    const allowed = { coat: ['black', 'gray', 'white'], ball: ['pink', 'blue', 'yellow', 'purple'] };
    const sparkleAllowed = { type: ['heart', 'star', 'twinkle', 'flower'], count: ['4', '8', '12', '16'], color: ['pink', 'yellow', 'blue', 'purple', 'green', 'red'] };
    const sparkleDefault = { type: 'twinkle', count: '8', color: 'pink' };
    const getColor = (kind) => {
        try {
            const saved = localStorage.getItem(`finwise-cat-${kind}`);
            return allowed[kind].includes(saved) ? saved : (kind === 'coat' ? 'black' : 'pink');
        } catch (_) { return kind === 'coat' ? 'black' : 'pink'; }
    };
    const setColor = (kind, color) => {
        if (!allowed[kind]?.includes(color)) return;
        try { localStorage.setItem(`finwise-cat-${kind}`, color); } catch (_) {}
        document.querySelectorAll(`[data-pet-kind="${kind}"]`).forEach((swatch) => {
            swatch.setAttribute('aria-pressed', String(swatch.dataset.petColor === color));
        });
        if (cat) cat.dataset[kind] = color;
        if (preview) preview.dataset[kind] = color;
    };
    const getSparkleSetting = (setting) => {
        try {
            const value = localStorage.getItem(`finwise-cat-sparkle-${setting}`);
            return sparkleAllowed[setting].includes(value) ? value : sparkleDefault[setting];
        } catch (_) { return sparkleDefault[setting]; }
    };
    const setSparkleSetting = (setting, value) => {
        if (!sparkleAllowed[setting]?.includes(value)) return;
        try { localStorage.setItem(`finwise-cat-sparkle-${setting}`, value); } catch (_) {}
        document.querySelectorAll(`[data-sparkle-setting="${setting}"]`).forEach((control) => {
            control.setAttribute('aria-pressed', String(control.dataset.sparkleValue === value));
        });
    };

    if (preview) {
        preview.dataset.coat = getColor('coat');
        preview.dataset.ball = getColor('ball');
    }

    if (cat) {
        cat.dataset.coat = getColor('coat');
        cat.dataset.ball = getColor('ball');
    }

    swatches.forEach((swatch) => {
        const kind = swatch.dataset.petKind;
        const color = getColor(kind);
        swatch.setAttribute('aria-pressed', String(swatch.dataset.petColor === color));
        if (swatch.dataset.bound === 'true') return;
        swatch.dataset.bound = 'true';
        swatch.addEventListener('click', () => setColor(kind, swatch.dataset.petColor));
    });

    sparkleControls.forEach((control) => {
        const setting = control.dataset.sparkleSetting;
        control.setAttribute('aria-pressed', String(control.dataset.sparkleValue === getSparkleSetting(setting)));
        if (control.dataset.sparkleBound === 'true') return;
        control.dataset.sparkleBound = 'true';
        control.addEventListener('click', () => setSparkleSetting(setting, control.dataset.sparkleValue));
    });

    if (!cat) return;

    if (cat.dataset.interactive !== 'true') {
        cat.dataset.interactive = 'true';

        cat.addEventListener('pointermove', (event) => {
            const bounds = cat.getBoundingClientRect();
            const x = Math.max(-3, Math.min(3, ((event.clientX - bounds.left) / bounds.width - 0.5) * 6));
            const y = Math.max(-2, Math.min(2, ((event.clientY - bounds.top) / bounds.height - 0.37) * 4));
            cat.style.setProperty('--cat-gaze-x', `${x}px`);
            cat.style.setProperty('--cat-gaze-y', `${y}px`);
        });
        cat.addEventListener('pointerleave', () => {
            cat.style.setProperty('--cat-gaze-x', '0px');
            cat.style.setProperty('--cat-gaze-y', '0px');
        });

        const showSparkles = (event) => {
            const types = { heart: '♥', star: '★', twinkle: '✦', flower: '❀' };
            const colors = { pink: '#e5516b', yellow: '#eabf37', blue: '#3984d8', purple: '#9272dc', green: '#00a982', red: '#e66e70' };
            const type = getSparkleSetting('type');
            const count = Number(getSparkleSetting('count'));
            const color = colors[getSparkleSetting('color')];
            const bounds = cat.getBoundingClientRect();
            const originX = Number.isFinite(event?.clientX) && event.clientX ? event.clientX : bounds.left + bounds.width / 2;
            const originY = Number.isFinite(event?.clientY) && event.clientY ? event.clientY : bounds.top + bounds.height / 2;
            const layer = document.createElement('div');
            layer.className = 'cat-sparkle-layer';
            layer.setAttribute('aria-hidden', 'true');

            for (let index = 0; index < count; index += 1) {
                const angle = (Math.PI * 2 * index / count) + (Math.random() - .5) * .28;
                const startDistance = 24 + Math.random() * 22;
                const startX = Math.cos(angle) * startDistance;
                const startY = Math.sin(angle) * startDistance;
                const endX = startX + Math.cos(angle) * (18 + Math.random() * 24);
                const endY = startY - (58 + Math.random() * 58);
                const particle = document.createElement('span');
                particle.className = 'cat-sparkle-particle';
                particle.textContent = types[type];
                particle.style.left = `${originX}px`;
                particle.style.top = `${originY}px`;
                particle.style.setProperty('--sparkle-color', color);
                particle.style.setProperty('--sparkle-start-x', `${startX}px`);
                particle.style.setProperty('--sparkle-start-y', `${startY}px`);
                particle.style.setProperty('--sparkle-mid-x', `${startX + (endX - startX) * .72}px`);
                particle.style.setProperty('--sparkle-mid-y', `${startY + (endY - startY) * .72}px`);
                particle.style.setProperty('--sparkle-end-x', `${endX}px`);
                particle.style.setProperty('--sparkle-end-y', `${endY}px`);
                particle.style.animationDelay = `${index * 48}ms`;
                layer.append(particle);
            }

            document.body.append(layer);
            window.setTimeout(() => layer.remove(), 2400);
        };

        const play = (event) => {
            showSparkles(event);
            cat.classList.remove('cat-playing');
            void cat.getBoundingClientRect();
            cat.classList.add('cat-playing');
            window.setTimeout(() => cat.classList.remove('cat-playing'), 1800);
        };
        cat.addEventListener('click', play);
        cat.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                play();
            }
        });
    }

}

function bindProfilePhotoInput() {
    const input = document.querySelector('[data-profile-photo-input]');
    if (!input || input.dataset.bound === 'true') return;
    input.dataset.bound = 'true';

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;

        const status = input.closest('div')?.querySelector('[data-profile-photo-status]');
        const setStatus = (message, type = 'success', duration = 3000) => {
            if (status) status.textContent = message;
            if (message) window.finwiseToast.show(message, type, duration);
            else window.finwiseToast.dismiss();
        };
        const conversionError = input.dataset.photoError || 'Could not convert this image to WebP.';
        const uploadError = input.dataset.photoUploadError || 'Could not upload this photo. Please try again.';
        const saveError = input.dataset.photoSaveError || 'Could not save this photo. Please try again.';

        let bitmap;
        try {
            if (!file.type.startsWith('image/')) throw new Error(conversionError);
            setStatus(input.dataset.photoCrop || 'Adjust the crop, then save.', 'info');

            bitmap = await createImageBitmap(file);
            const modal = input.parentElement.querySelector('[data-photo-crop-modal]');
            const canvas = modal.querySelector('[data-photo-crop-canvas]');
            const context = canvas.getContext('2d');
            if (!context) throw new Error(conversionError);
            const zoom = modal.querySelector('[data-photo-crop-zoom]');
            zoom.value = '1';
            const cropSize = canvas.width;
            let scale = Math.max(cropSize / bitmap.width, cropSize / bitmap.height);
            let offsetX = (cropSize - bitmap.width * scale) / 2;
            let offsetY = (cropSize - bitmap.height * scale) / 2;
            const draw = () => {
                context.clearRect(0, 0, cropSize, cropSize);
                context.fillStyle = '#f1f5f9';
                context.fillRect(0, 0, cropSize, cropSize);
                context.drawImage(bitmap, offsetX, offsetY, bitmap.width * scale, bitmap.height * scale);
            };
            const clamp = () => {
                const width = bitmap.width * scale;
                const height = bitmap.height * scale;
                offsetX = Math.min(0, Math.max(cropSize - width, offsetX));
                offsetY = Math.min(0, Math.max(cropSize - height, offsetY));
            };
            let dragging = false;
            let pointer = null;
            const canvasPoint = (event) => {
                const bounds = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX - bounds.left) * cropSize / bounds.width,
                    y: (event.clientY - bounds.top) * cropSize / bounds.height,
                };
            };
            const move = (event) => {
                if (!dragging || !pointer) return;
                const point = canvasPoint(event);
                offsetX += point.x - pointer.x;
                offsetY += point.y - pointer.y;
                pointer = point;
                clamp(); draw();
            };
            const endDrag = () => { dragging = false; pointer = null; };
            canvas.onpointerdown = (event) => {
                dragging = true;
                pointer = canvasPoint(event);
                canvas.setPointerCapture(event.pointerId);
            };
            canvas.onpointermove = move;
            canvas.onpointerup = endDrag;
            canvas.onpointercancel = endDrag;
            zoom.oninput = () => {
                const oldScale = scale;
                const centerX = (cropSize / 2 - offsetX) / oldScale;
                const centerY = (cropSize / 2 - offsetY) / oldScale;
                scale = Math.max(cropSize / bitmap.width, cropSize / bitmap.height) * Number(zoom.value);
                offsetX = cropSize / 2 - centerX * scale;
                offsetY = cropSize / 2 - centerY * scale;
                clamp(); draw();
            };
            const cleanup = () => {
                modal.hidden = true;
                zoom.oninput = null;
                canvas.onpointerdown = canvas.onpointermove = canvas.onpointerup = canvas.onpointercancel = null;
                bitmap.close();
            };
            modal.hidden = false;
            draw();

            const result = await new Promise((resolve) => {
                modal.querySelector('[data-photo-crop-cancel]').onclick = () => resolve(null);
                modal.querySelector('[data-photo-crop-save]').onclick = async () => {
                    setStatus(input.dataset.photoConverting || 'Converting photo to WebP…', 'loading', 0);
                    const output = document.createElement('canvas');
                    output.width = output.height = 1024;
                    const out = output.getContext('2d');
                    const sourceX = -offsetX / scale;
                    const sourceY = -offsetY / scale;
                    const sourceSize = cropSize / scale;
                    out.drawImage(bitmap, sourceX, sourceY, sourceSize, sourceSize, 0, 0, output.width, output.height);
                    try {
                        let blob = null;
                        for (const quality of [.86, .76, .66, .56]) {
                            blob = await new Promise((done) => output.toBlob(done, 'image/webp', quality));
                            if (blob && blob.size <= 1.5 * 1024 * 1024) break;
                        }
                        resolve(blob);
                    }
                    catch (_) { resolve(null); }
                };
            });
            cleanup();
            if (!result) { setStatus(''); return; }
            if (result.type !== 'image/webp' || result.size > 1.5 * 1024 * 1024) throw new Error(conversionError);

            setStatus(input.dataset.photoUploading || 'Uploading photo…', 'loading', 0);
            const form = new FormData();
            form.append('photo', result, `profile-${Date.now()}.webp`);
            const response = await fetch(input.dataset.photoUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: form,
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || payload.errors?.photo?.[0] || uploadError);

            const avatar = document.querySelector('[data-profile-avatar]');
            if (avatar && payload.url) {
                avatar.innerHTML = '';
                const image = document.createElement('img');
                image.src = payload.url;
                image.alt = input.dataset.photoAlt || 'Profile photo';
                image.className = 'h-full w-full object-cover';
                avatar.append(image);
            }
            setStatus(payload.message || input.dataset.photoSaved || 'Profile photo saved.');
        } catch (error) {
            setStatus(error.message || saveError, 'error');
            if (bitmap && !bitmap.closed) bitmap.close();
        } finally {
            input.value = '';
        }
    });
}

async function compressChatPhoto(file) {
    const bitmap = await createImageBitmap(file);
    try {
        let best = null;
        for (const maxDimension of [1600, 1400, 1200, 1000, 800]) {
            const ratio = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * ratio));
            canvas.height = Math.max(1, Math.round(bitmap.height * ratio));
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Canvas is unavailable.');
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

            for (const quality of [.84, .74, .64, .54]) {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/webp', quality));
                if (!blob || blob.type !== 'image/webp') throw new Error('WebP encoding is unavailable.');
                if (!best || blob.size < best.size) best = blob;
                if (blob.size <= 1.5 * 1024 * 1024) return blob;
            }
        }

        if (!best || best.size > 5 * 1024 * 1024) throw new Error('The compressed photo is too large.');
        return best;
    } finally {
        bitmap.close();
    }
}

function bindChatPhotoInputs() {
    if (document.documentElement.dataset.chatPhotoBound === 'true') return;
    document.documentElement.dataset.chatPhotoBound = 'true';
    let queueActive = false;

    document.addEventListener('change', async (event) => {
        const input = event.target.closest?.('[data-chat-photo-input]');
        if (!input) return;
        event.preventDefault();
        event.stopImmediatePropagation();

        const files = Array.from(input.files || []);
        input.value = '';
        if (files.length === 0) return;

        const form = input.closest('form');
        const queue = form.querySelector('[data-chat-photo-queue]');
        const sendButton = form.querySelector('[data-chat-send-button]');
        if (form.dataset.photoQueueActive === 'true') {
            window.finwiseToast.show(input.dataset.chatPhotoBusy || 'Wait for the current photo queue to finish.', 'info', 4000);
            return;
        }
        const queuedCount = Number(form.dataset.photoCount || 0);
        if (files.length + queuedCount > 20) {
            window.finwiseToast.show(input.dataset.chatPhotoLimitError || 'You can send up to 20 photos in one message.', 'error', 5000);
            return;
        }

        const component = window.Livewire?.all().find((item) => item.el.contains(input));
        const wire = component?.$wire;
        if (!wire?.uploadMultiple) {
            window.finwiseToast.show(input.dataset.chatPhotoError || 'Could not process this photo.', 'error', 5000);
            return;
        }

        form.dataset.photoQueueActive = 'true';
        queueActive = true;
        input.disabled = true;
        sendButton.disabled = true;
        queue.hidden = false;

        for (let index = 0; index < files.length; index++) {
            const file = files[index];
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 rounded-xl bg-light px-2 py-1.5 text-[10px]';
            const icon = document.createElement('span');
            icon.className = 'grid size-7 shrink-0 place-items-center rounded-lg bg-white text-nav';
            icon.textContent = `${index + 1}/${files.length}`;
            const details = document.createElement('div');
            details.className = 'min-w-0 flex-1';
            const name = document.createElement('div');
            name.className = 'truncate font-medium text-dark';
            name.textContent = file.name;
            const state = document.createElement('div');
            state.className = 'text-dark/50';
            state.textContent = input.dataset.chatPhotoConverting || 'Compressing photo to WebP…';
            const track = document.createElement('div');
            track.className = 'mt-1 h-1 overflow-hidden rounded-full bg-dark/10';
            const bar = document.createElement('div');
            bar.className = 'h-full w-0 rounded-full bg-nav transition-[width]';
            track.append(bar);
            details.append(name, state, track);
            row.append(icon, details);
            queue.append(row);

            try {
                if (!file.type.startsWith('image/')) throw new Error('Unsupported photo.');
                const blob = await compressChatPhoto(file);
                const converted = new File([blob], `chat-photo-${Date.now()}-${index}.webp`, { type: 'image/webp' });
                state.textContent = input.dataset.chatPhotoUploading || 'Uploading photo…';
                await new Promise((resolve, reject) => {
                    wire.uploadMultiple('photos', [converted], resolve, () => reject(new Error('Upload failed.')), (progressEvent) => {
                        const progress = progressEvent.detail?.progress ?? 0;
                        bar.style.width = `${progress}%`;
                    });
                });
                bar.style.width = '100%';
                state.textContent = input.dataset.chatPhotoReady || 'Ready to send';
                form.dataset.photoCount = String(Number(form.dataset.photoCount || 0) + 1);
            } catch (_) {
                row.classList.add('text-red-600');
                state.textContent = input.dataset.chatPhotoFailed || 'Processing failed';
                bar.classList.add('bg-red-500');
            }
        }

        input.disabled = false;
        sendButton.disabled = false;
        delete form.dataset.photoQueueActive;
        queueActive = false;
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target.closest?.('[data-chat-composer]');
        if (queueActive || form?.dataset.photoQueueActive === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('chat-photos-sent', () => {
        const queue = document.querySelector('[data-chat-photo-queue]');
        if (queue) {
            queue.replaceChildren();
            queue.hidden = true;
        }
        const form = document.querySelector('[data-chat-composer]');
        if (form) delete form.dataset.photoCount;
    });
}

function bindChatImageViewer() {
    if (document.documentElement.dataset.chatImageViewerBound === 'true') return;
    document.documentElement.dataset.chatImageViewerBound = 'true';

    let lastImageButton = null;
    let imageGallery = [];
    let imageIndex = 0;
    let swipeStart = null;
    const renderImageStrip = (viewer) => {
        const strip = viewer.querySelector('[data-chat-image-strip]');
        const position = viewer.querySelector('[data-chat-image-position]');
        strip.replaceChildren();
        strip.hidden = imageGallery.length < 2;
        position.hidden = imageGallery.length < 2;

        imageGallery.forEach((image, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.chatImageThumb = 'true';
            button.dataset.imageIndex = String(index);
            button.className = 'relative size-14 shrink-0 overflow-hidden rounded-lg border-2 border-transparent';
            button.setAttribute('aria-label', `${index + 1} / ${imageGallery.length}`);
            const thumbnail = document.createElement('img');
            thumbnail.src = image.url;
            thumbnail.alt = '';
            thumbnail.loading = 'lazy';
            thumbnail.className = 'size-full object-cover';
            const number = document.createElement('span');
            number.className = 'absolute bottom-0 right-0 rounded-tl bg-black/70 px-1 text-[9px] text-white';
            number.textContent = String(index + 1);
            button.append(thumbnail, number);
            strip.append(button);
        });
    };
    const showImage = (viewer, index) => {
        imageIndex = (index + imageGallery.length) % imageGallery.length;
        const image = imageGallery[imageIndex];
        viewer.querySelector('[data-chat-image-full]').src = image.url;
        const download = viewer.querySelector('[data-chat-image-download]');
        download.href = image.downloadUrl;
        download.download = image.downloadName;
        const position = viewer.querySelector('[data-chat-image-position]');
        position.textContent = viewer.dataset.photoPosition
            .replace(':current', String(imageIndex + 1))
            .replace(':total', String(imageGallery.length));
        viewer.querySelectorAll('[data-chat-image-thumb]').forEach((button, index) => {
            button.classList.toggle('border-white', index === imageIndex);
            button.classList.toggle('border-transparent', index !== imageIndex);
            button.setAttribute('aria-current', index === imageIndex ? 'true' : 'false');
            if (index === imageIndex) button.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        });
    };
    const closeViewer = (viewer) => {
        const image = viewer.querySelector('[data-chat-image-full]');
        const download = viewer.querySelector('[data-chat-image-download]');
        viewer.hidden = true;
        image.removeAttribute('src');
        download.href = '#';
        download.removeAttribute('download');
        imageGallery = [];
        imageIndex = 0;
        viewer.querySelector('[data-chat-image-strip]').replaceChildren();
        viewer.querySelector('[data-chat-image-position]').textContent = '';
        lastImageButton?.focus();
        lastImageButton = null;
    };

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest?.('[data-chat-image-open]');
        if (openButton) {
            const viewer = document.querySelector('[data-chat-image-viewer]');
            if (!viewer) return;
            lastImageButton = openButton;
            try {
                imageGallery = JSON.parse(openButton.dataset.imageGallery || '[]');
            } catch (_) { imageGallery = []; }
            if (imageGallery.length === 0) return;
            viewer.hidden = false;
            renderImageStrip(viewer);
            showImage(viewer, Number(openButton.dataset.imageIndex || 0));
            viewer.querySelector('[data-chat-image-close]').focus();
            return;
        }

        const viewer = document.querySelector('[data-chat-image-viewer]');
        const thumbnail = event.target.closest?.('[data-chat-image-thumb]');
        if (viewer && !viewer.hidden && thumbnail) {
            showImage(viewer, Number(thumbnail.dataset.imageIndex));
            return;
        }
        if (viewer && !viewer.hidden && (event.target === viewer || event.target.closest?.('[data-chat-image-close]'))) {
            closeViewer(viewer);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const viewer = document.querySelector('[data-chat-image-viewer]');
        if (viewer && !viewer.hidden) closeViewer(viewer);
    });

    document.addEventListener('keydown', (event) => {
        const viewer = document.querySelector('[data-chat-image-viewer]');
        if (!viewer || viewer.hidden || imageGallery.length < 2) return;
        if (event.key === 'ArrowLeft') showImage(viewer, imageIndex - 1);
        if (event.key === 'ArrowRight') showImage(viewer, imageIndex + 1);
    });

    document.addEventListener('pointerdown', (event) => {
        const stage = event.target.closest?.('[data-chat-image-stage]');
        const viewer = document.querySelector('[data-chat-image-viewer]');
        if (!stage || !viewer || viewer.hidden || imageGallery.length < 2) return;
        swipeStart = { x: event.clientX, y: event.clientY, pointerId: event.pointerId };
    });

    document.addEventListener('pointerup', (event) => {
        if (!swipeStart || swipeStart.pointerId !== event.pointerId) return;
        const deltaX = event.clientX - swipeStart.x;
        const deltaY = event.clientY - swipeStart.y;
        swipeStart = null;
        if (Math.abs(deltaX) < 45 || Math.abs(deltaX) <= Math.abs(deltaY) * 1.2) return;
        const viewer = document.querySelector('[data-chat-image-viewer]');
        if (viewer && !viewer.hidden) showImage(viewer, imageIndex + (deltaX < 0 ? 1 : -1));
    });

    document.addEventListener('pointercancel', () => { swipeStart = null; });
}

function bindChatLocalTimes() {
    const messages = document.getElementById('chat-messages');
    if (!messages) return;

    const localize = () => {
        const locale = document.documentElement.lang || undefined;
        messages.querySelectorAll('time[data-chat-local-time][datetime]').forEach((time) => {
            const date = new Date(time.dateTime);
            if (Number.isNaN(date.getTime())) return;
            const formatted = new Intl.DateTimeFormat(locale, {
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23',
            }).format(date);
            if (time.textContent !== formatted) time.textContent = formatted;
        });
    };

    localize();
    if (messages.dataset.localTimeBound === 'true') return;
    messages.dataset.localTimeBound = 'true';
    new MutationObserver(localize).observe(messages, { childList: true, characterData: true, subtree: true });
}

function bindChatReadObserver() {
    const feed = document.getElementById('chat-messages');
    if (!feed || feed.dataset.readObserverBound === 'true') return;
    feed.dataset.readObserverBound = 'true';

    let root = feed;
    while (root && !root.hasAttribute('wire:id')) root = root.parentElement;
    const componentId = root?.getAttribute('wire:id');
    const seen = new Set();
    const pending = new Set();
    let timer = null;

    const flush = () => {
        timer = null;
        if (pending.size === 0 || !componentId || !window.Livewire) return;
        const ids = [...pending];
        pending.clear();
        window.Livewire.find(componentId)?.$wire.markMessagesRead(ids);
    };
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting || entry.intersectionRatio < 0.5) continue;
            const message = entry.target;
            const id = message.dataset.chatMessageId;
            if (!id || message.dataset.chatSenderId === feed.dataset.chatUserId || seen.has(id)) continue;
            seen.add(id);
            pending.add(id);
            if (!timer) timer = setTimeout(flush, 250);
            observer.unobserve(message);
        }
    }, { root: feed, threshold: 0.5 });

    const watch = (node) => {
        if (!(node instanceof Element)) return;
        if (node.matches('[data-chat-message-id]')) observer.observe(node);
        node.querySelectorAll('[data-chat-message-id]').forEach((message) => observer.observe(message));
    };
    feed.querySelectorAll('[data-chat-message-id]').forEach((message) => observer.observe(message));
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach(watch));
    }).observe(feed, { childList: true, subtree: true });

    const unread = feed.querySelector('[data-chat-first-unread]');
    if (unread) {
        feed.scrollTop = unread.getBoundingClientRect().top - feed.getBoundingClientRect().top + feed.scrollTop;
    } else {
        feed.scrollTop = feed.scrollHeight;
    }
}

function bindBottomNavSwipe() {
    if (document.documentElement.dataset.bottomNavSwipeBound === 'true') return;
    document.documentElement.dataset.bottomNavSwipeBound = 'true';

    let start = null;
    let navigating = false;
    let lastNavigation = 0;
    const ignored = 'input, textarea, select, button, a, [contenteditable="true"], [data-no-swipe]';
    const routes = ['dashboard', 'analysis', 'transactions', 'savings-goals', 'profile'];

    document.addEventListener('touchstart', (event) => {
        const touch = event.changedTouches[0];
        if (!touch || navigating || Date.now() - lastNavigation < 450 || !event.target.closest('main') || event.target.closest(ignored)) {
            start = null;
            return;
        }
        start = { x: touch.clientX, y: touch.clientY };
    }, { passive: true });

    document.addEventListener('touchend', (event) => {
        const touch = event.changedTouches[0];
        if (!start || !touch) return;
        const dx = touch.clientX - start.x;
        const dy = touch.clientY - start.y;
        start = null;
        if (Math.abs(dx) < 75 || Math.abs(dx) < Math.abs(dy) * 1.25) return;

        const nav = document.getElementById('bottom-navigation');
        if (!nav) return;
        const items = [...nav.querySelectorAll('[data-bottom-nav-item]')]
            .filter((item) => routes.includes(item.dataset.bottomNavRoute))
            .sort((a, b) => Number(a.dataset.bottomNavItem) - Number(b.dataset.bottomNavItem));
        const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
        const current = items.findIndex((item) => {
            const itemPath = new URL(item.href, window.location.origin).pathname.replace(/\/$/, '') || '/';
            return itemPath === currentPath;
        });
        if (current < 0) return;
        const next = items[current + (dx > 0 ? 1 : -1)];
        if (!next) return;
        navigating = true;
        lastNavigation = Date.now();
        next.click();
    }, { passive: true });

    document.addEventListener('touchcancel', () => { start = null; }, { passive: true });
    document.addEventListener('livewire:navigated', () => { navigating = false; }, { passive: true });
}

function bindCustomConfirmations() {
    const modal = document.querySelector('[data-confirm-modal]');
    if (!modal || modal.dataset.bound === 'true') return;
    modal.dataset.bound = 'true';

    const message = modal.querySelector('#app-confirm-message');
    const cancel = modal.querySelector('[data-confirm-cancel]');
    const accept = modal.querySelector('[data-confirm-accept]');
    let pendingButton = null;
    let previousFocus = null;
    const close = (confirmed) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        const button = pendingButton;
        pendingButton = null;
        if (confirmed && button?.isConnected && !button.disabled) {
            button.dataset.confirmApproved = 'true';
            button.click();
        }
        previousFocus?.focus();
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-confirm]');
        if (!button) return;
        if (button.dataset.confirmApproved === 'true') {
            delete button.dataset.confirmApproved;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        if (pendingButton) return;
        pendingButton = button;
        previousFocus = button;
        message.textContent = button.dataset.confirm;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        cancel.focus();
    }, true);

    cancel.addEventListener('click', () => close(false));
    accept.addEventListener('click', () => close(true));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(false); });
    document.addEventListener('keydown', (event) => {
        if (!pendingButton) return;
        if (event.key === 'Escape') { event.preventDefault(); close(false); }
        if (event.key === 'Enter') { event.preventDefault(); close(true); }
    });
}

bindDashboardCat();
bindProfilePhotoInput();
bindChatPhotoInputs();
bindChatImageViewer();
bindChatLocalTimes();
bindChatReadObserver();
bindBottomNavSwipe();
bindCustomConfirmations();
document.addEventListener('livewire:navigated', () => {
    bindDashboardCat();
    bindProfilePhotoInput();
    bindChatPhotoInputs();
    bindChatImageViewer();
    bindChatLocalTimes();
    bindChatReadObserver();
    bindCustomConfirmations();
});

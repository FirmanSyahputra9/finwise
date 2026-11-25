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
            const move = (event) => {
                if (!dragging || !pointer) return;
                offsetX += event.clientX - pointer.x;
                offsetY += event.clientY - pointer.y;
                pointer = { x: event.clientX, y: event.clientY };
                clamp(); draw();
            };
            const endDrag = () => { dragging = false; pointer = null; };
            canvas.onpointerdown = (event) => {
                dragging = true;
                pointer = { x: event.clientX, y: event.clientY };
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
                    const ratio = output.width / cropSize;
                    out.drawImage(bitmap, -offsetX * ratio, -offsetY * ratio, bitmap.width * scale * ratio, bitmap.height * scale * ratio);
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
bindBottomNavSwipe();
bindCustomConfirmations();
document.addEventListener('livewire:navigated', () => {
    bindDashboardCat();
    bindProfilePhotoInput();
    bindCustomConfirmations();
});

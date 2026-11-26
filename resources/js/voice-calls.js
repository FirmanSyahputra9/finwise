let callState = null;
let startingCall = false;
let incomingTimer = null;
let callTimer = null;
let incomingBusy = false;
let callBusy = false;
let lastRoot = null;

const root = () => document.querySelector('[data-voice-call-manager]');
const overlay = (node) => node?.querySelector('[data-call-overlay]');

function requestJson(url, method = 'GET', body = null) {
    const node = root();
    return fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': node?.dataset.csrf || '',
        },
        body: body ? JSON.stringify(body) : undefined,
    }).then(async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || `Request failed (${response.status})`);
        return data;
    });
}

function showCall(state, status) {
    const node = root();
    const modal = overlay(node);
    if (!node || !modal) return;
    modal.hidden = false;
    const title = modal.querySelector('[data-call-title]');
    const label = modal.querySelector('[data-call-status]');
    const incomingActions = modal.querySelector('[data-call-incoming-actions]');
    const activeActions = modal.querySelector('[data-call-active-actions]');
    const muteButton = modal.querySelector('[data-call-mute]');
    title.textContent = state.remoteName || node.dataset.labelIncoming;
    label.textContent = status;
    incomingActions.hidden = state.role !== 'callee' || state.call.status !== 'accepted' && state.call.status !== 'connecting';
    activeActions.hidden = !state.accepted && state.role !== 'caller';
    muteButton.hidden = !state.accepted;
}

function hideCall() {
    const node = root();
    const modal = overlay(node);
    if (modal) modal.hidden = true;
}

function scheduleIncomingPoll(delay = 5000) {
    clearTimeout(incomingTimer);
    incomingTimer = setTimeout(pollIncoming, delay);
}

async function pollIncoming() {
    const node = root();
    if (!node || callState || incomingBusy) return scheduleIncomingPoll();
    incomingBusy = true;
    try {
        const response = await requestJson(node.dataset.incomingUrl);
        const call = response?.call;
        const callId = typeof call?.id === 'string' ? call.id : typeof call?.uuid === 'string' ? call.uuid : null;
        if (call && callId && !callState) {
            callState = { id: callId, connectionId: call.connection_id, role: 'callee', call: { status: 'ringing' }, remoteName: call.caller, accepted: false, lastSignalId: 0, pendingCandidates: [], localCandidates: [], signalChain: Promise.resolve() };
            showCall(callState, node.dataset.labelIncoming);
            scheduleCallPoll(600);
        }
    } catch (_) {
        // A later poll retries transient network or session errors.
    } finally {
        incomingBusy = false;
        if (!callState) scheduleIncomingPoll();
    }
}

function scheduleCallPoll(delay = 1800) {
    clearTimeout(callTimer);
    callTimer = setTimeout(pollCall, delay);
}

async function postSignal(state, type, payload) {
    state.signalChain = state.signalChain.then(() => requestJson(`/chat/calls/${state.id}/signals`, 'POST', { type, payload }));
    return state.signalChain;
}

async function flushCandidates(state) {
    const buffered = state.pendingCandidates.splice(0);
    for (const candidate of buffered) await state.peer.addIceCandidate(candidate);
}

async function receiveSignal(state, signal) {
    if (signal.type === 'candidate') {
        if (state.peer.remoteDescription) await state.peer.addIceCandidate(signal.payload);
        else state.pendingCandidates.push(signal.payload);
        return;
    }

    if (signal.type === 'offer' && state.role === 'callee') {
        await state.peer.setRemoteDescription(signal.payload);
        await flushCandidates(state);
        const answer = await state.peer.createAnswer();
        await state.peer.setLocalDescription(answer);
        await postSignal(state, 'answer', state.peer.localDescription.toJSON());
        state.localDescriptionSent = true;
        for (const candidate of state.localCandidates.splice(0)) await postSignal(state, 'candidate', candidate);
        return;
    }

    if (signal.type === 'answer' && state.role === 'caller') {
        await state.peer.setRemoteDescription(signal.payload);
        await flushCandidates(state);
    }
}

async function startPeer(state) {
    if (state.starting || state.peer) return;
    state.starting = true;
    try {
        state.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        state.peer = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
        state.localStream.getTracks().forEach((track) => state.peer.addTrack(track, state.localStream));
        state.peer.ontrack = (event) => {
            const audio = root()?.querySelector('[data-call-remote]');
            if (audio) {
                audio.srcObject = event.streams[0];
                audio.play().catch(() => {});
            }
        };
        state.peer.onicecandidate = (event) => {
            if (!event.candidate) return;
            const candidate = event.candidate.toJSON();
            if (!state.localDescriptionSent) state.localCandidates.push(candidate);
            else postSignal(state, 'candidate', candidate).catch(() => {});
        };
        state.peer.oniceconnectionstatechange = () => {
            if (state.peer?.iceConnectionState === 'connected' || state.peer?.iceConnectionState === 'completed') {
                showCall(state, root()?.dataset.labelActive || 'Connected');
            }
            if (state.peer?.iceConnectionState === 'failed') {
                showCall(state, root()?.dataset.labelFailed || 'Could not connect');
            }
        };

        state.accepted = true;
        showCall(state, root()?.dataset.labelConnecting || 'Connecting');
        if (state.role === 'caller') {
            const offer = await state.peer.createOffer();
            await state.peer.setLocalDescription(offer);
            await postSignal(state, 'offer', state.peer.localDescription.toJSON());
            state.localDescriptionSent = true;
            for (const candidate of state.localCandidates.splice(0)) await postSignal(state, 'candidate', candidate);
        }
    } catch (error) {
        const label = root()?.dataset.labelPermission || 'Allow microphone access and use HTTPS to call.';
        showCall(state, error?.name === 'NotAllowedError' || error?.name === 'NotFoundError' ? label : root()?.dataset.labelFailed || label);
        await requestJson(`/chat/calls/${state.id}/end`, 'POST').catch(() => {});
        finishCall(label, false);
    } finally {
        state.starting = false;
    }
}

async function pollCall() {
    const state = callState;
    if (!state || callBusy) return;
    if (typeof state.id !== 'string' || state.id.length === 0) {
        finishCall(root()?.dataset.labelFailed || 'Call failed', false);
        return;
    }
    callBusy = true;
    try {
        const response = await requestJson(`/chat/calls/${state.id}/signals?after=${state.lastSignalId}`);
        if (response.status === 'rejected' || response.status === 'missed' || response.status === 'ended') {
            const message = response.status === 'missed' ? root()?.dataset.labelMissed : root()?.dataset.labelEnded;
            finishCall(message || 'Call ended', false);
            return;
        }
        state.call.status = response.status;
        if (response.status === 'accepted' && state.role === 'caller') await startPeer(state);
        for (const signal of response.signals || []) {
            state.lastSignalId = Math.max(state.lastSignalId, signal.id);
            if (signal.type === 'offer' && !state.peer) await startPeer(state);
            if (state.peer) await receiveSignal(state, signal);
        }
    } catch (_) {
        // Keep retrying while the call is active.
    } finally {
        callBusy = false;
        if (callState === state) scheduleCallPoll();
    }
}

function finishCall(message = '', serverEnded = false) {
    const state = callState;
    if (!state) return;
    callState = null;
    clearTimeout(callTimer);
    state.peer?.close();
    state.localStream?.getTracks().forEach((track) => track.stop());
    const audio = root()?.querySelector('[data-call-remote]');
    if (audio) audio.srcObject = null;
    const node = root();
    const status = overlay(node)?.querySelector('[data-call-status]');
    if (status && message) status.textContent = message;
    const incomingActions = overlay(node)?.querySelector('[data-call-incoming-actions]');
    const activeActions = overlay(node)?.querySelector('[data-call-active-actions]');
    if (incomingActions) incomingActions.hidden = true;
    if (activeActions) activeActions.hidden = true;
    if (serverEnded) requestJson(`/chat/calls/${state.id}/end`, 'POST').catch(() => {});
    if (message) setTimeout(hideCall, 2200);
    else hideCall();
        scheduleIncomingPoll(5000);
}

async function endCall(serverEnded = true) {
    const state = callState;
    if (state && serverEnded) await requestJson(`/chat/calls/${state.id}/end`, 'POST').catch(() => {});
    finishCall(root()?.dataset.labelEnded || 'Call ended', false);
}

function bindVoiceCalls() {
    const node = root();
    if (node === lastRoot) return;
    lastRoot = node;
    if (node) scheduleIncomingPoll(250);
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest?.('[data-voice-call-start], [data-call-accept], [data-call-reject], [data-call-end], [data-call-mute]');
    if (!button) return;
    const node = root();
    if (!node) return;

    if (button.matches('[data-voice-call-start]')) {
        if (callState || startingCall) return;
        startingCall = true;
        try {
            const call = await requestJson(button.dataset.startUrl, 'POST', {});
            callState = { id: call.id, role: 'caller', call, remoteName: button.dataset.remoteName, accepted: false, lastSignalId: 0, pendingCandidates: [], localCandidates: [], signalChain: Promise.resolve() };
            showCall(callState, node.dataset.labelCalling);
            scheduleCallPoll(500);
        } catch (_) {
            window.finwiseToast?.show(node.dataset.labelFailed, 'error', 5000);
        } finally {
            startingCall = false;
        }
        return;
    }

    if (button.matches('[data-call-accept]') && callState?.role === 'callee') {
        try {
            await requestJson(`/chat/calls/${callState.id}/accept`, 'POST', {});
            callState.call.status = 'connecting';
            await startPeer(callState);
            scheduleCallPoll(300);
        } catch (_) {
            finishCall(node.dataset.labelFailed, false);
        }
        return;
    }

    if (button.matches('[data-call-reject]') && callState?.role === 'callee') {
        await requestJson(`/chat/calls/${callState.id}/reject`, 'POST', {}).catch(() => {});
        finishCall(node.dataset.labelEnded, false);
        return;
    }

    if (button.matches('[data-call-end]')) return endCall();
    if (button.matches('[data-call-mute]')) {
        const muted = button.getAttribute('aria-pressed') === 'true';
        button.setAttribute('aria-pressed', String(!muted));
        callState?.localStream?.getAudioTracks().forEach((track) => { track.enabled = muted; });
    }
});

document.addEventListener('livewire:navigated', () => {
    lastRoot = null;
    bindVoiceCalls();
});
window.addEventListener('pagehide', () => {
    const state = callState;
    if (!state) return;
    navigator.sendBeacon(`/chat/calls/${state.id}/end`, new Blob([JSON.stringify({ _token: root()?.dataset.csrf || '' })], { type: 'application/json' }));
});

bindVoiceCalls();

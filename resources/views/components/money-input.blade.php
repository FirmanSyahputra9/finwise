@props([
    'model',
    'value' => null,
    'id' => null,
    'name' => null,
    'placeholder' => '0',
    'class' => 'field',
    'ariaLabel' => null,
    'offlineField' => null,
])

<div class="min-w-0 flex-1" x-data="{
    display: @js((string) ($value ?? '')),
    model: @js($model),
    beforeDisplay: '',
    beforeDigitCaret: 0,
    init() {
        this.sync($wire.get(this.model));
        this.$watch(() => $wire.get(this.model), value => this.sync(value));
    },
    sync(value) {
        const digits = String(value ?? '').replace(/\D/g, '').replace(/^0+/, '');
        this.display = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    rememberInput(event) {
        this.beforeDisplay = this.display;
        this.beforeDigitCaret = event.target.value.slice(0, event.target.selectionStart ?? 0).replace(/\D/g, '').length;
    },
    allowInsertion(event, inserted) {
        if (/[^\d.]/.test(inserted)) {
            event.preventDefault();
            return false;
        }

        const input = event.target;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        const existingDigits = input.value.replace(/\D/g, '').length;
        const selectedDigits = input.value.slice(start, end).replace(/\D/g, '').length;
        const insertedNumbers = inserted.replace(/\D/g, '');
        const insertedDigits = insertedNumbers.length;
        if (existingDigits - selectedDigits + insertedDigits > 13) {
            event.preventDefault();
            return false;
        }

        const nextDigits = input.value.slice(0, start).replace(/\D/g, '')
            + insertedNumbers
            + input.value.slice(end).replace(/\D/g, '');
        if (nextDigits.startsWith('00')) {
            event.preventDefault();
            return false;
        }

        return true;
    },
    beforeInput(event) {
        if (!event.inputType?.startsWith('insert')) return;
        const inserted = event.data ?? event.dataTransfer?.getData('text/plain') ?? '';
        if (!this.allowInsertion(event, inserted)) return;
        this.rememberInput(event);
    },
    beforePaste(event) {
        const inserted = event.clipboardData?.getData('text/plain') ?? '';
        if (!this.allowInsertion(event, inserted)) return;
        this.rememberInput(event);
    },
    caretAtDigits(value, digitCount) {
        if (digitCount <= 0) return 0;
        let count = 0;
        for (let index = 0; index < value.length; index++) {
            if (/\d/.test(value[index]) && ++count >= digitCount) return index + 1;
        }
        return value.length;
    },
    update(event) {
        const input = event.target;
        let digits = input.value.replace(/\D/g, '');
        if (digits.length > 13) {
            this.display = this.beforeDisplay;
            input.value = this.beforeDisplay;
            const caret = this.caretAtDigits(this.beforeDisplay, this.beforeDigitCaret);
            this.$nextTick(() => input.setSelectionRange(caret, caret));
            return;
        }
        const caretDigits = input.value.slice(0, input.selectionStart ?? 0).replace(/\D/g, '').length;
        const leadingZeroes = Math.max(0, (digits.match(/^0+/)?.[0].length ?? 0) - 1);
        digits = digits.replace(/^0{2,}/, '0');
        this.sync(digits);
        input.value = this.display;
        const caret = this.caretAtDigits(this.display, Math.max(0, caretDigits - leadingZeroes));
        this.$nextTick(() => input.setSelectionRange(caret, caret));
        // Keep each keystroke client-side; submit/save actions sync the pending value.
        $wire.set(this.model, digits || null, false);
    }
}">
    <input
        @if ($id) id="{{ $id }}" @endif
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :value="display"
        x-on:beforeinput="beforeInput($event)"
        x-on:paste="beforePaste($event)"
        x-on:input="update($event)"
        placeholder="{{ $placeholder }}"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        class="{{ $class }}"
    >
    <input type="hidden" name="{{ $name ?? $model }}" wire:model="{{ $model }}" @if ($offlineField) data-offline-field="{{ $offlineField }}" @endif>
</div>

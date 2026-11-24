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
    init() {
        this.sync($wire.get(this.model));
        this.$watch(() => $wire.get(this.model), value => this.sync(value));
    },
    sync(value) {
        const digits = String(value ?? '').replace(/\D/g, '');
        this.display = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    update(event) {
        const digits = event.target.value.replace(/\D/g, '');
        this.sync(digits);
        $wire.set(this.model, digits ? Number(digits) : null);
    }
}">
    <input
        @if ($id) id="{{ $id }}" @endif
        type="text"
        inputmode="numeric"
        autocomplete="off"
        x-model="display"
        x-on:input="update($event)"
        placeholder="{{ $placeholder }}"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        class="{{ $class }}"
    >
    <input type="hidden" name="{{ $name ?? $model }}" wire:model="{{ $model }}" @if ($offlineField) data-offline-field="{{ $offlineField }}" @endif>
</div>

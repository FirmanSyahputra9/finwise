            <div x-data="{ open: false, edit: false, security: false, help: false, appearance: { theme: 'light', accent: 'green', textColor: 'default', font: 'poppins', size: '16', pattern: 'none', analysisChart: 'bar' }, init() { this.appearance = window.finwiseAppearance.read() }, saveAppearance() { this.appearance = window.finwiseAppearance.save(this.appearance); window.dispatchEvent(new CustomEvent('app-toast', { detail: { message: @js(__('ui.saved')), type: 'success' } })) } }" x-on:profile-updated.window="edit = false" x-on:password-updated.window="security = false">
    <x-screen :title="__('ui.profile')">
        <x-slot:top>
            <div class="mt-6 flex justify-center" data-profile-photo-area>
                <div data-profile-avatar class="size-[117px] overflow-hidden rounded-full bg-sky text-mint ring-4 ring-light">
                    @if ($user->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="{{ __('ui.profile_photo') }}" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full w-full place-items-center text-5xl font-semibold">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex flex-col items-center">
                <label for="profile-photo-upload" aria-label="{{ __('ui.change_photo') }}" title="{{ __('ui.change_photo') }}" class="btn grid size-10 cursor-pointer place-items-center rounded-full p-0"><x-icon name="camera" class="size-5" /></label>
                <input id="profile-photo-upload" type="file" accept=".webp,.png,.jpg,.jpeg,.svg,image/webp,image/png,image/jpeg,image/svg+xml" class="sr-only" data-profile-photo-input data-photo-url="{{ route('profile.photo') }}"
                       data-photo-converting="{{ __('ui.photo_converting') }}" data-photo-uploading="{{ __('ui.photo_uploading') }}" data-photo-error="{{ __('ui.photo_convert_error') }}" data-photo-upload-error="{{ __('ui.photo_upload_error') }}" data-photo-save-error="{{ __('ui.photo_save_error') }}" data-photo-saved="{{ __('ui.photo_saved') }}" data-photo-alt="{{ __('ui.profile_photo') }}" data-photo-crop="{{ __('ui.photo_crop_hint') }}">
                <div data-photo-crop-modal hidden class="fixed inset-0 z-[70] grid place-items-center bg-black/60 p-5">
                    <div class="card-3d w-full max-w-sm rounded-3xl bg-white p-5 text-center">
                        <h2 class="text-lg font-semibold text-dark">{{ __('ui.photo_crop') }}</h2>
                        <p class="mt-1 text-xs text-dark/60">{{ __('ui.photo_crop_hint') }}</p>
                        <canvas data-photo-crop-canvas width="280" height="280" class="mx-auto mt-4 touch-none rounded-2xl"></canvas>
                        <label class="mt-4 block text-sm text-dark">{{ __('ui.photo_zoom') }}
                            <input data-photo-crop-zoom type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full">
                        </label>
                        <div class="mt-5 flex gap-3">
                            <button type="button" data-photo-crop-cancel class="btn-soft flex-1">{{ __('ui.cancel') }}</button>
                            <button type="button" data-photo-crop-save class="btn flex-1">{{ __('ui.save') }}</button>
                        </div>
                    </div>
                </div>
                <p data-profile-photo-status aria-live="polite" class="sr-only"></p>
            </div>
        </x-slot:top>

        <div class="text-center">
            <p class="text-xl font-bold capitalize text-dark">{{ $user->name }}</p>
            <p class="text-[13px] font-semibold text-ink">{{ __('ui.account_id') }}: {{ $user->profileId() }}</p>
        </div>

        <details class="card-3d group mt-6 rounded-[24px] bg-white/70 p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span><span class="block text-base font-semibold text-ink">{{ __('ui.appearance') }}</span><span class="mt-1 block text-xs text-dark/60">{{ __('ui.appearance_hint') }}</span></span>
                <span class="text-dark/60 transition-transform group-open:rotate-180">⌄</span>
            </summary>
            <div class="sticky top-2 z-20 mt-4 flex items-center justify-between rounded-2xl bg-white p-3 shadow-md ring-1 ring-dark/10">
                <div>
                    <p class="text-xs text-dark/60">{{ __('ui.preview') }}</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ __('ui.preview_text') }}</p>
                </div>
                <svg id="profile-cat-preview" data-coat="black" data-ball="pink" viewBox="0 0 240 220" role="img" aria-label="Pratinjau warna kucing dan bola" class="h-20 w-20 shrink-0">
                    <defs>
                        <radialGradient id="profile-fur" cx="34%" cy="24%" r="78%"><stop offset="0%" stop-color="var(--cat-highlight)" /><stop offset="58%" stop-color="var(--cat-coat)" /><stop offset="100%" stop-color="var(--cat-shadow)" /></radialGradient>
                        <linearGradient id="profile-tail" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--cat-highlight)" /><stop offset="100%" stop-color="var(--cat-shadow)" /></linearGradient>
                        <linearGradient id="profile-ear" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--cat-ear-light)" /><stop offset="100%" stop-color="var(--cat-ear-color)" /></linearGradient>
                        <radialGradient id="profile-ball" cx="30%" cy="25%" r="75%"><stop stop-color="var(--cat-ball-highlight)" /><stop offset="100%" stop-color="var(--cat-ball-color)" /></radialGradient>
                    </defs>
                    <path class="cat-tail cat-coat-stroke" d="M145 145 C195 145 200 95 178 75" stroke-width="12" stroke-linecap="round" fill="none" />
                    <ellipse cx="120" cy="145" rx="43" ry="38" class="cat-coat" />
                    <ellipse cx="100" cy="174" rx="12" ry="6" class="cat-shade" /><ellipse cx="140" cy="174" rx="12" ry="6" class="cat-shade" />
                    <polygon points="88,61 86,28 112,47" class="cat-coat" /><polygon points="91,56 90,38 105,47" class="cat-ear" />
                    <polygon points="152,61 154,28 128,47" class="cat-coat" /><polygon points="149,56 150,38 135,47" class="cat-ear" />
                    <circle cx="120" cy="82" r="36" class="cat-coat" />
                    <ellipse cx="105" cy="81" rx="7" ry="9" fill="#ffd84d" /><ellipse cx="135" cy="81" rx="7" ry="9" fill="#ffd84d" />
                    <ellipse cx="105" cy="81" rx="2.5" ry="7" fill="#111" /><ellipse cx="135" cy="81" rx="2.5" ry="7" fill="#111" />
                    <path d="M116 94 h8 l-4 5 z" fill="#f7a8b8" />
                    <g transform="translate(194 182)" class="cat-ball-spin"><circle r="12" class="cat-ball-fill" /><path d="M-9 -4 q9 6 18 0 M-10 3 q10 6 20 0" class="cat-ball-thread" stroke-width="1.8" stroke-linecap="round" fill="none" /><ellipse cx="-3" cy="-7" rx="3" ry="1.5" fill="white" opacity=".4" /></g>
                </svg>
            </div>
            <div class="mt-4 space-y-3">
                <label class="block text-sm font-medium text-dark">{{ __('ui.language') }}
                    <select wire:model.live="language" class="field mt-1" aria-label="{{ __('ui.language') }}">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                        <option value="ja">Japanese</option>
                    </select>
                    <span class="mt-1 block text-xs text-dark/60">{{ __('ui.language_hint') }}</span>
                </label>
            </div>
            <div class="mt-4 space-y-3">
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.theme') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.theme') }}">
                        <button type="button" class="appearance-choice" x-bind:aria-pressed="appearance.theme === 'light'" x-on:click="appearance.theme = 'light'; saveAppearance()">{{ __('ui.light') }}</button>
                        <button type="button" class="appearance-choice" x-bind:aria-pressed="appearance.theme === 'dark'" x-on:click="appearance.theme = 'dark'; saveAppearance()">{{ __('ui.dark') }}</button>
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.accent') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.accent') }}">
                        @foreach ([['green', '#00d09e', __('ui.theme_green')], ['blue', '#3984d8', __('ui.theme_blue')], ['pink', '#e779ad', __('ui.theme_pink')], ['purple', '#9272dc', __('ui.theme_purple')], ['orange', '#f0a24d', __('ui.theme_orange')], ['red', '#e66e70', __('ui.theme_red')]] as [$value, $color, $label])
                            <button type="button" class="appearance-dot" style="background:{{ $color }}" aria-label="{{ $label }}" title="{{ $label }}" x-bind:aria-pressed="appearance.accent === '{{ $value }}'" x-on:click="appearance.accent = '{{ $value }}'; saveAppearance()"></button>
                        @endforeach
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.pattern') }}</p>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('ui.pattern') }}">
                        <button type="button" class="appearance-choice" x-bind:aria-pressed="appearance.pattern === 'none'" x-on:click="appearance.pattern = 'none'; saveAppearance()">{{ __('ui.pattern_none') }}</button>
                        <button type="button" class="appearance-choice flex items-center gap-2" x-bind:aria-pressed="appearance.pattern === 'fuzzy-duck-40'" x-on:click="appearance.pattern = 'fuzzy-duck-40'; saveAppearance()"><span aria-hidden="true" class="pattern-fuzzy-duck-40 size-7 rounded-md"></span>{{ __('ui.pattern_fuzzy_duck') }}</button>
                        <button type="button" class="appearance-choice flex items-center gap-2" x-bind:aria-pressed="appearance.pattern === 'rare-snake-68'" x-on:click="appearance.pattern = 'rare-snake-68'; saveAppearance()"><span aria-hidden="true" class="pattern-rare-snake-68 size-7 rounded-md"></span>{{ __('ui.pattern_rare_snake') }}</button>
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.text_color') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.text_color') }}">
                        @foreach ([['default', 'linear-gradient(135deg,#111827 50%,#f8fafc 50%)', __('ui.auto_theme')], ['black', '#111827', __('ui.black')], ['white', '#f8fafc', __('ui.white')], ['blue', '#1d4ed8', __('ui.theme_blue')], ['pink', '#be185d', __('ui.theme_pink')], ['green', '#047857', __('ui.theme_green')]] as [$value, $color, $label])
                            <button type="button" class="appearance-dot" style="background:{{ $color }}" aria-label="{{ $label }}" title="{{ $label }}" x-bind:aria-pressed="appearance.textColor === '{{ $value }}'" x-on:click="appearance.textColor = '{{ $value }}'; saveAppearance()"></button>
                        @endforeach
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.font') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.font') }}">
                        @foreach ([['poppins', 'Poppins'], ['system', 'System'], ['arial', 'Arial'], ['georgia', 'Georgia']] as [$value, $label])
                            <button type="button" class="appearance-choice px-2" x-bind:aria-pressed="appearance.font === '{{ $value }}'" x-on:click="appearance.font = '{{ $value }}'; saveAppearance()">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.font_size') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.font_size') }}">
                        @foreach (['14', '16', '18', '20'] as $size)
                            <button type="button" class="appearance-choice min-w-9 px-2" x-bind:aria-pressed="appearance.size === '{{ $size }}'" x-on:click="appearance.size = '{{ $size }}'; saveAppearance()">{{ $size }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="card-3d flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-mint px-4 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.analysis_chart') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.analysis_chart') }}">
                        @foreach ([['bar', __('ui.chart_bar')], ['line', __('ui.chart_line')], ['pie', __('ui.chart_pie')]] as [$value, $label])
                            <button type="button" class="appearance-choice" x-bind:aria-pressed="appearance.analysisChart === '{{ $value }}'" x-on:click="appearance.analysisChart = '{{ $value }}'; saveAppearance()">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-3d mt-4 divide-y divide-dark/10 rounded-2xl bg-mint px-4">
                <div class="flex items-center justify-between gap-3 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.cat_color') }}</p>
                    <div class="flex gap-3" role="group" aria-label="{{ __('ui.cat_color') }}">
                        <button type="button" class="cat-color-swatch" data-pet-kind="coat" data-pet-color="black" aria-label="{{ __('ui.black') }}" title="{{ __('ui.black') }}" style="background:#2b2b3a"></button>
                        <button type="button" class="cat-color-swatch" data-pet-kind="coat" data-pet-color="gray" aria-label="{{ __('ui.gray') }}" title="{{ __('ui.gray') }}" style="background:#858895"></button>
                        <button type="button" class="cat-color-swatch" data-pet-kind="coat" data-pet-color="white" aria-label="{{ __('ui.white') }}" title="{{ __('ui.white') }}" style="background:#f7f4eb"></button>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.ball_color') }}</p>
                    <div class="flex gap-3" role="group" aria-label="{{ __('ui.ball_color') }}">
                        <button type="button" class="cat-color-swatch" data-pet-kind="ball" data-pet-color="pink" aria-label="{{ __('ui.theme_pink') }}" title="{{ __('ui.theme_pink') }}" style="background:#e5516b"></button>
                        <button type="button" class="cat-color-swatch" data-pet-kind="ball" data-pet-color="blue" aria-label="{{ __('ui.theme_blue') }}" title="{{ __('ui.theme_blue') }}" style="background:#3984d8"></button>
                        <button type="button" class="cat-color-swatch" data-pet-kind="ball" data-pet-color="yellow" aria-label="{{ __('ui.yellow') }}" title="{{ __('ui.yellow') }}" style="background:#eabf37"></button>
                        <button type="button" class="cat-color-swatch" data-pet-kind="ball" data-pet-color="purple" aria-label="{{ __('ui.theme_purple') }}" title="{{ __('ui.theme_purple') }}" style="background:#9272dc"></button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.sparkle_style') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.sparkle_style') }}">
                        @foreach ([['heart', '♥', __('ui.sparkle_heart')], ['star', '★', __('ui.sparkle_star')], ['twinkle', '✦', __('ui.sparkle_twinkle')], ['flower', '❀', __('ui.sparkle_flower')]] as [$value, $symbol, $label])
                            <button type="button" class="appearance-choice min-w-9 px-2 text-base" data-sparkle-setting="type" data-sparkle-value="{{ $value }}" aria-label="{{ $label }}" title="{{ $label }}">{{ $symbol }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.sparkle_count') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.sparkle_count') }}">
                        @foreach ([4, 8, 12, 16] as $count)
                            <button type="button" class="appearance-choice min-w-9 px-2" data-sparkle-setting="count" data-sparkle-value="{{ $count }}">{{ $count }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <p class="text-sm font-medium text-dark">{{ __('ui.sparkle_color') }}</p>
                    <div class="flex gap-2" role="group" aria-label="{{ __('ui.sparkle_color') }}">
                        @foreach ([['pink', '#e5516b', __('ui.theme_pink')], ['yellow', '#eabf37', __('ui.yellow')], ['blue', '#3984d8', __('ui.theme_blue')], ['purple', '#9272dc', __('ui.theme_purple')], ['green', '#00a982', __('ui.theme_green')], ['red', '#e66e70', __('ui.theme_red')]] as [$value, $color, $label])
                            <button type="button" class="appearance-dot" data-sparkle-setting="color" data-sparkle-value="{{ $value }}" style="background:{{ $color }}" aria-label="{{ $label }}" title="{{ $label }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </details>

        <livewire:deleted-items />

        <div class="mt-8 space-y-8">
            @foreach ([['edit', __('ui.edit_profile'), 'bg-sky', 'edit'], ['security', __('ui.security'), 'bg-blue', 'security'], ['help', __('ui.help'), 'bg-sky', 'help']] as [$icon, $label, $color, $action])
                <button type="button" x-on:click="{{ $action }} = true" class="flex w-full items-center gap-4 text-left">
                    <span class="grid h-[53px] w-14 place-items-center rounded-[22px] text-mint {{ $color }}"><x-icon :name="$icon" class="size-6" /></span>
                    <span class="text-[15px] font-medium text-ink">{{ $label }}</span>
                </button>
            @endforeach

            <button x-on:click="open = true" class="flex items-center gap-4">
                <span class="grid h-[53px] w-14 place-items-center rounded-[22px] bg-blue text-mint"><x-icon name="logout" class="size-6" /></span>
                <span class="text-[15px] font-medium text-ink">{{ __('ui.logout') }}</span>
            </button>
        </div>
    </x-screen>

    <div x-show="edit" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="edit = false">
        <form wire:submit="updateProfile" x-on:click.outside="edit = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ __('ui.edit_profile') }}</h2>
            <label for="profile-name" class="label">{{ __('ui.name') }}</label><input id="profile-name" name="name" autocomplete="name" wire:model="name" class="field">
            @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="profile-email" class="label">{{ __('ui.email') }}</label><input id="profile-email" name="email" type="email" autocomplete="email" wire:model="email" class="field">
            @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn inline-flex items-center justify-center gap-2 w-full max-w-[218px]"><span wire:loading wire:target="updateProfile" class="app-spinner"></span>{{ __('ui.save') }}</button><button type="button" x-on:click="edit = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button></div>
        </form>
    </div>

    <div x-show="security" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="security = false">
        <form wire:submit="updatePassword" x-on:click.outside="security = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ __('ui.security') }}</h2>
            <label for="current-password" class="label">{{ __('ui.current_password') }}</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" wire:model="currentPassword" class="field">
            @error('currentPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="new-password" class="label">{{ __('ui.new_password') }}</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" wire:model="newPassword" class="field">
            @error('newPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="new-password-confirmation" class="label">{{ __('ui.repeat_password') }}</label><input id="new-password-confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" wire:model="newPassword_confirmation" class="field">
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn inline-flex items-center justify-center gap-2 w-full max-w-[218px]"><span wire:loading wire:target="updatePassword" class="app-spinner"></span>{{ __('ui.update') }}</button><button type="button" x-on:click="security = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button></div>
        </form>
    </div>

    <div x-show="help" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="help = false">
        <div x-on:click.outside="help = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white p-7 text-center">
            <h2 class="text-xl font-bold text-dark">{{ __('ui.help') }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-dark/75">{{ __('ui.help_text') }}</p>
            <button type="button" x-on:click="help = false" class="btn mt-6 w-full max-w-[218px]">{{ __('ui.understood') }}</button>
        </div>
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <div x-on:click.outside="open = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white px-8 py-10 text-center">
            <h2 class="text-xl font-bold capitalize text-dark">{{ __('ui.end_session') }}</h2>
            <p class="mt-3 font-spartan text-[17px] text-dark/75">{{ __('ui.logout_confirm') }}</p>
            <div class="mt-8 flex flex-col items-center gap-3">
                <button wire:click="logout" class="btn w-full max-w-[218px]">{{ __('ui.yes_logout') }}</button>
                <button x-on:click="open = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    </div>
</div>

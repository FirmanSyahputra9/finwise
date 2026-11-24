<div x-data="{ open: false, edit: false, security: false, help: false, appearance: { theme: 'light', accent: 'green', textColor: 'default', font: 'poppins', size: '16' }, appearanceSaved: false, init() { this.appearance = window.finwiseAppearance.read() }, saveAppearance() { this.appearance = window.finwiseAppearance.save(this.appearance); this.appearanceSaved = true; setTimeout(() => this.appearanceSaved = false, 2500) } }" x-on:profile-updated.window="edit = false" x-on:password-updated.window="security = false">
    <x-screen title="Profile">
        <x-slot:top>
            <div class="mt-6 flex justify-center">
                <div class="grid size-[117px] place-items-center rounded-full bg-sky text-5xl font-semibold text-mint ring-4 ring-light">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
            </div>
        </x-slot:top>

        <div class="text-center">
            <p class="text-xl font-bold capitalize text-dark">{{ $user->name }}</p>
            <p class="text-[13px] font-semibold text-ink">ID: {{ $user->profileId() }}</p>
        </div>

        <details class="group mt-6 rounded-[24px] bg-white/70 p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span><span class="block text-base font-semibold text-ink">Tampilan aplikasi</span><span class="mt-1 block text-xs text-dark/60">Preferensi tersimpan di perangkat ini</span></span>
                <span class="text-dark/60 transition-transform group-open:rotate-180">⌄</span>
            </summary>
            <div class="mt-4 space-y-3">
                <label class="block text-sm font-medium text-dark">Tema
                    <select x-model="appearance.theme" class="field mt-1">
                        <option value="light">Terang</option>
                        <option value="dark">Gelap</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-dark">Warna aksen
                    <select x-model="appearance.accent" class="field mt-1">
                        <option value="green">Hijau</option>
                        <option value="blue">Biru</option>
                        <option value="pink">Merah muda</option>
                        <option value="purple">Ungu</option>
                        <option value="orange">Oranye</option>
                        <option value="red">Merah</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-dark">Warna teks utama
                    <select x-model="appearance.textColor" class="field mt-1">
                        <option value="default">Otomatis mengikuti tema</option>
                        <option value="black">Hitam</option>
                        <option value="white">Putih</option>
                        <option value="blue">Biru</option>
                        <option value="pink">Merah muda</option>
                        <option value="green">Hijau</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-dark">Jenis font
                    <select x-model="appearance.font" class="field mt-1">
                        <option value="poppins">Poppins</option>
                        <option value="system">System</option>
                        <option value="arial">Arial</option>
                        <option value="georgia">Georgia</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-dark">Ukuran font
                    <select x-model="appearance.size" class="field mt-1">
                        <option value="14">14</option>
                        <option value="16">16</option>
                        <option value="18">18</option>
                        <option value="20">20</option>
                    </select>
                </label>
            </div>
            <div class="mt-4 flex items-center justify-between gap-2">
                <button type="button" x-on:click="saveAppearance()" class="btn px-6">Terapkan tampilan</button>
                <span x-show="appearanceSaved" x-cloak class="text-xs text-emerald-700">Diterapkan</span>
            </div>
        </details>

        <div class="mt-8 space-y-8">
            @foreach ([['edit', 'Edit Profile', 'bg-sky', 'edit'], ['security', 'Security', 'bg-blue', 'security'], ['help', 'Help', 'bg-sky', 'help']] as [$icon, $label, $color, $action])
                <button type="button" x-on:click="{{ $action }} = true" class="flex w-full items-center gap-4 text-left">
                    <span class="grid h-[53px] w-14 place-items-center rounded-[22px] text-mint {{ $color }}"><x-icon :name="$icon" class="size-6" /></span>
                    <span class="text-[15px] font-medium text-ink">{{ $label }}</span>
                </button>
            @endforeach

            <button x-on:click="open = true" class="flex items-center gap-4">
                <span class="grid h-[53px] w-14 place-items-center rounded-[22px] bg-blue text-mint"><x-icon name="logout" class="size-6" /></span>
                <span class="text-[15px] font-medium text-ink">Logout</span>
            </button>
        </div>
    </x-screen>

    <div x-show="edit" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="edit = false">
        <form wire:submit="updateProfile" x-on:click.outside="edit = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">Edit Profile</h2>
            <label for="profile-name" class="label">Nama</label><input id="profile-name" name="name" autocomplete="name" wire:model="name" class="field">
            @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="profile-email" class="label">Email</label><input id="profile-email" name="email" type="email" autocomplete="email" wire:model="email" class="field">
            @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn w-full max-w-[218px]">Simpan</button><button type="button" x-on:click="edit = false" class="btn-soft w-full max-w-[218px]">Batal</button></div>
        </form>
    </div>

    <div x-show="security" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="security = false">
        <form wire:submit="updatePassword" x-on:click.outside="security = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">Ganti Kata Sandi</h2>
            <label for="current-password" class="label">Kata sandi saat ini</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" wire:model="currentPassword" class="field">
            @error('currentPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="new-password" class="label">Kata sandi baru</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" wire:model="newPassword" class="field">
            @error('newPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="new-password-confirmation" class="label">Ulangi kata sandi baru</label><input id="new-password-confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" wire:model="newPassword_confirmation" class="field">
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn w-full max-w-[218px]">Perbarui</button><button type="button" x-on:click="security = false" class="btn-soft w-full max-w-[218px]">Batal</button></div>
        </form>
    </div>

    <div x-show="help" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="help = false">
        <div x-on:click.outside="help = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-7 text-center">
            <h2 class="text-xl font-bold text-dark">Bantuan</h2>
            <p class="mt-3 text-sm leading-relaxed text-dark/75">Catat pemasukan, pengeluaran, dan transfer di menu Transaksi. Atur kategori di menu Kategori. Notifikasi transaksi dan peringatan budget tersedia lewat ikon lonceng.</p>
            <button type="button" x-on:click="help = false" class="btn mt-6 w-full max-w-[218px]">Mengerti</button>
        </div>
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <div x-on:click.outside="open = false" class="w-full max-w-[339px] rounded-[20px] bg-white px-8 py-10 text-center">
            <h2 class="text-xl font-bold capitalize text-dark">End Session</h2>
            <p class="mt-3 font-spartan text-[17px] text-[#363130]">Are you sure you want to log out?</p>
            <div class="mt-8 flex flex-col items-center gap-3">
                <button wire:click="logout" class="btn w-full max-w-[218px]">Yes, End Session</button>
                <button x-on:click="open = false" class="btn-soft w-full max-w-[218px]">Cancel</button>
            </div>
        </div>
    </div>
</div>

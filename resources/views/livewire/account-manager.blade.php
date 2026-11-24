<section class="space-y-3" x-data="{ open: @entangle('open') }">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold">Akun Saya</h2>
        <button type="button" wire:click="create" class="rounded-full bg-main px-4 py-2 text-xs font-medium text-ink">+ Tambah akun</button>
    </div>
    @error('accountId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    <div class="space-y-2">
        @forelse ($accounts as $account)
            <article wire:key="account-{{ $account->id }}" class="flex items-center justify-between rounded-2xl bg-white/70 p-4">
                <div>
                    <p class="font-medium text-dark">{{ $account->name }}</p>
                    <p class="text-xs text-dark/60">{{ $account->type->label() }}</p>
                    <p class="mt-1 text-sm font-semibold text-dark">{{ \App\Support\Money::rp($account->balance) }}</p>
                </div>
                <div class="flex gap-3 text-xs"><button type="button" wire:click="edit({{ $account->id }})" class="text-blue">Ubah</button><button type="button" wire:click="delete({{ $account->id }})" wire:confirm="Hapus akun ini?" class="text-red-600">Hapus</button></div>
            </article>
        @empty
            <p class="text-sm text-dark/60">Belum ada akun.</p>
        @endforelse
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="save" x-on:click.outside="open = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ $accountId ? 'Ubah Akun' : 'Akun Baru' }}</h2>
            <label for="account-name" class="label">Nama akun</label><input id="account-name" name="name" autocomplete="off" wire:model="name" placeholder="Contoh: BCA" class="field">
            @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="account-type" class="label">Jenis akun</label>
            <select id="account-type" name="type" autocomplete="off" wire:model="type" class="field"><option value="cash">Tunai</option><option value="bank">Bank</option><option value="ewallet">E-Wallet</option></select>
            <label for="account-opening-balance" class="label">Saldo awal (Rp)</label><x-money-input id="account-opening-balance" model="openingBalance" :value="$openingBalance" name="opening_balance" placeholder="0" class="field" />
            @error('openingBalance') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn w-full max-w-[218px]">Simpan</button><button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">Batal</button></div>
        </form>
    </div>
</section>

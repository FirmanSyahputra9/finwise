<div x-data="{ open: false }" x-on:saved.window="open = false">
    <x-screen title="Categories">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div class="grid grid-cols-3 gap-x-4 gap-y-6">
            @foreach ($categories as $c)
                <div wire:key="c-{{ $c->id }}" class="group relative text-center">
                    <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Hapus kategori {{ $c->name }}? Transaksi terkait tetap tersimpan tanpa kategori." aria-label="Hapus kategori {{ $c->name }}" class="absolute -right-2 -top-2 z-10 grid size-7 place-items-center rounded-full bg-red-500 text-lg leading-none text-white shadow">&times;</button>
                    <div class="grid h-[98px] place-items-center rounded-[26px] text-4xl font-semibold text-mint {{ $loop->first ? 'bg-ocean' : 'bg-sky' }}">
                        {{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}
                    </div>
                    <p class="mt-2 truncate text-[15px] font-medium text-ink">{{ $c->name }}</p>
                </div>
            @endforeach

            <button type="button" x-on:click="open = true" class="text-center">
                <div class="grid h-[98px] place-items-center rounded-[26px] bg-sky text-mint"><x-icon name="plus" class="size-10" /></div>
                <p class="mt-2 text-[15px] font-medium text-ink">More</p>
            </button>
        </div>
    </x-screen>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="save" x-on:click.outside="open = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-8">
            <h2 class="text-center text-xl font-bold text-dark">New Category</h2>
            <input id="category-name" name="name" type="text" autocomplete="off" wire:model="name" placeholder="Tulis nama kategori..." class="field mt-6">
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3">
                <button type="submit" class="btn w-full max-w-[218px]">Save</button>
                <button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">Cancel</button>
            </div>
        </form>
    </div>
</div>

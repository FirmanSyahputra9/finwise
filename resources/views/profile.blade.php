<div x-data="{ open: false }">
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
            <p class="text-[13px] font-semibold text-ink">ID: {{ str_pad((string) $user->id, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="mt-8 space-y-8">
            @foreach ([['edit', 'Edit Profile', 'bg-sky'], ['security', 'Security', 'bg-blue'], ['setting', 'Setting', 'bg-ocean'], ['help', 'Help', 'bg-sky']] as [$icon, $label, $color])
                <a href="#" class="flex items-center gap-4">
                    <span class="grid h-[53px] w-14 place-items-center rounded-[22px] text-mint {{ $color }}"><x-icon :name="$icon" class="size-6" /></span>
                    <span class="text-[15px] font-medium text-ink">{{ $label }}</span>
                </a>
            @endforeach

            <button x-on:click="open = true" class="flex items-center gap-4">
                <span class="grid h-[53px] w-14 place-items-center rounded-[22px] bg-blue text-mint"><x-icon name="logout" class="size-6" /></span>
                <span class="text-[15px] font-medium text-ink">Logout</span>
            </button>
        </div>
    </x-screen>

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

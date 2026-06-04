<div
    x-data="{
        loadedPages: ['dashboard'],
        navigate(page) {
            if (!this.loadedPages.includes(page)) {
                this.loadedPages.push(page);
            }
            $wire.setPage(page);
        }
    }"
    @navigate-to.window="navigate($event.detail.page)"
>
    {{-- DASHBOARD — always loaded --}}
    <div x-show="$wire.currentPage === 'dashboard'">
        @livewire('pages.gl.gldashboard')
    </div>

    {{-- Loading spinner while fetching a new page --}}
    <div wire:loading wire:target="setPage" class="flex flex-col items-center justify-center py-24 gap-4">
        <svg class="animate-spin w-10 h-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <p class="text-gray-500 text-sm font-medium">Loading, please wait...</p>
    </div>

    {{-- Pages — only rendered when active --}}
    <div wire:loading.remove wire:target="setPage">
        @if($currentPage === 'enroll-operator')
            @livewire('glcomponents.enroll-operator-panel')
        @endif

        @if($currentPage === 'PR')
            @livewire('glcomponents.generate-process-record')
        @endif

        @if($currentPage === 'HF')
            @livewire('hfdashboard.hf-rework-encoding')
        @endif
    </div>
</div>
<x-layout>
   <div
    class="p-2"
    x-data="{ currentPage: 'dashboard' }"
    @navigate-to.window="currentPage = $event.detail.page"
>

    {{-- ========================== --}}
    {{-- DASHBOARD VIEW             --}}
    {{-- ========================== --}}
    {{-- x-show is fine here — dashboard is the default, Livewire owns this content --}}
    <template x-if="currentPage === 'dashboard'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <x-layout.dashboard-panel />
        </div>
    </template>

    {{-- ========================== --}}
    {{-- ENROLL OPERATOR VIEW       --}}
    {{-- ========================== --}}
    {{-- x-if completely removes this from the DOM when not active.            --}}
    {{-- The inner Livewire component only mounts when the user navigates here --}}
    {{-- and is fully destroyed when they leave — no ghost rendering.          --}}
    <template x-if="currentPage === 'enroll-operator'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <x-layout.enroll-operator-panel />
        </div>
    </template>

    {{-- ========================== --}}
    {{-- GENERATE PROCESS RECORD    --}}
    {{-- ========================== --}}
    <template x-if="currentPage === 'PR'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <x-layout.process-record-generator />
        </div>
    </template>

     <template x-if="currentPage === 'HF'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <x-layout.hfdashboard-panel/>
        </div>
    </template>

</div>
</x-layout>
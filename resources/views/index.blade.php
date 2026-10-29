<x-layouts.app>
   <div
    class="p-2"
    x-data="{ currentPage: 'dashboard' }"
    @navigate-to.window="currentPage = $event.detail.page"
>

    <template x-if="currentPage === 'dashboard'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <livewire:gl::gl-page />
        </div>
    </template>

     <template x-if="currentPage === 'enroll-operator'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <livewire:operator::operator-page />
        </div>
    </template>

       <template x-if="currentPage === 'encode-per-process'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <livewire:encode-per-process::per-process-page />
        </div>
    </template>

    <template x-if="currentPage === 'PR'">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <livewire:process-record::generate-pr />
        </div>
    </template>
</div>
</x-layouts.app>
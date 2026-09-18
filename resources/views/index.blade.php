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
</div>
</x-layouts.app>
<div class="p-4">
    <div id="buttons-action" class="flex flex-col sm:flex-row sm:flex-wrap justify-center gap-4 mb-6">
        <button type="button" id="Init-add"
            class="w-full sm:w-32 md:w-36 lg:w-40 px-4 py-2 text-white bg-green-700 hover:bg-green-800 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-black"
            wire:click.debounce.500ms="setAction('Add')" wire:loading.attr="disabled">
            Add
        </button>

        <button type="button" id="Init-update"
            class="w-full sm:w-32 md:w-36 lg:w-40 px-4 py-2 text-white bg-blue-700 hover:bg-blue-800 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-black"
            wire:click.debounce.500ms="setAction('Edit')" wire:loading.attr="disabled">
            Edit
        </button>

        <button type="button" id="Init-delete"
            class="w-full sm:w-32 md:w-36 lg:w-40 px-4 py-2 text-white bg-red-700 hover:bg-red-800 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-black"
            wire:click.debounce.500ms="setAction('Delete')" wire:loading.attr="disabled">
            Delete
        </button>

        <button type="button" id="Init-inquire"
            class="w-full sm:w-32 md:w-36 lg:w-40 px-4 py-2 text-white bg-yellow-700 hover:bg-yellow-800 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-black"
            wire:click.debounce.500ms="setAction('View')" wire:loading.attr="disabled">
            Inquire
        </button>
    </div>
    <livewire:templates.ppfdashboard>
    <div id="OuterPanel">
        <livewire:templates.checkppf :systemname="request()->input('systemname')" />
        <div class="flex flex-col sm:flex-row gap-6 mt-4 items-start justify-center w-full px-4">
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <livewire:glcomponents.defects />
            </div>
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <livewire:glcomponents.reworks />
            </div>
        </div>
        <livewire:templates.goodng />
        <livewire:templates.add />
    </div>
</div>
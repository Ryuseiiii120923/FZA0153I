<div class="bg-white shadow-md px-5 pt-4 pb-0 flex gap-0 border-b border-gray-200">
    <button @click="activeTab = 'worker'"
        :class="activeTab === 'worker' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
        class="px-5 py-3 text-sm transition-colors duration-150">
        Worker Inspection
    </button>
    <button @click="activeTab = 'auto'"
        :class="activeTab === 'auto' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
        class="px-5 py-3 text-sm transition-colors duration-150">
        Auto Inspection
    </button>
</div>

<div x-show="activeTab === 'worker'" class="px-5 py-4 flex gap-3 bg-white shadow-sm">
    <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">+ Add Worker</button>
    <button wire:click="addNewDoneRework" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0b9af3;">+ Add Worker For SRW</button>
    <button wire:click="addNewPL" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#02367B;">+ Add Worker For PL</button>
</div>

<div x-show="activeTab === 'auto'" class="px-5 py-4 flex flex-wrap gap-3 bg-white shadow-sm">
    <button wire:click="addNewAutoDimension" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#4338ca;">+ Auto Dimension Checking</button>
    <button wire:click="addNewAutoSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0369a1;">+ Auto SF Inspection</button>
    <button wire:click="addNewAutoPLSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0f766e;">+ Auto PL/SF Inspection</button>
    <button wire:click="addNewAutoNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#b45309;">+ Auto NG Checking</button>
    <button wire:click="addNewAutoDimNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#7c3aed;">+ Auto Dimension of VI Good from NG</button>
    <button wire:click="addNewAutoSFNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#be185d;">+ Auto SF Dimension of VI Good from NG</button>
</div>

<div x-show="showFixed" x-transition class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg px-5 py-4 flex flex-wrap gap-3">
    <template x-if="activeTab === 'worker'">
        <div class="flex gap-3 flex-wrap">
            <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">+ Add Worker</button>
            <button wire:click="addNewDoneRework" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0b9af3;">+ Add Worker For Rework</button>
            <button wire:click="addNewPL" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#02367B;">+ Add Worker For PL</button>
        </div>
    </template>
    <template x-if="activeTab === 'auto'">
        <div class="flex gap-3 flex-wrap">
            <button wire:click="addNewAutoDimension" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#4338ca;">+ Auto Dimension Checking</button>
            <button wire:click="addNewAutoSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0369a1;">+ Auto SF Inspection</button>
            <button wire:click="addNewAutoPLSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0f766e;">+ Auto PL/SF Inspection</button>
            <button wire:click="addNewAutoNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#b45309;">+ Auto NG Checking</button>
            <button wire:click="addNewAutoDimNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#7c3aed;">+ Auto Dimension of VI Good from NG</button>
            <button wire:click="addNewAutoSFNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#be185d;">+ Auto SF Dimension of VI Good from NG</button>
        </div>
    </template>
</div>
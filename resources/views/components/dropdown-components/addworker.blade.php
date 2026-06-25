<div {{ $attributes->merge(['class' => 'flex gap-3']) }}>
     <button
         @disabled($disabled)
        wire:click="addNew"
        class="bg-blue-600 text-white px-4 py-2 rounded-md">
        + Add Worker
    </button>

    <button
        @disabled($disabled)
        wire:click="addNewDoneRework"
        class="text-white px-4 py-2 rounded-md"
        style="background-color:#0b9af3;">
        + Add Worker For Rework
    </button>
</div>
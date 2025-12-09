<div>
    <div class="flex items-center gap-2 justify-center p-6" id="buttons-action" wire:ignore>
        <div class="flex flex-col sm:flex-row justify-center mx-4 mb-4 p-4 mt-5">
            <button type="button" id="Init-add" class=" no-blur w-40 rounded-lg px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-black-300 font-medium text-sm px-5 py-2.5 text-center me-2 mb-2"
                wire:click="setAction('Add')" >Add</button>
        </div>
        <div class="flex flex-col sm:flex-row justify-center mx-4 mb-4 p-4 mt-5">
            <button type="button" id="Init-update" class=" no-blur w-40 rounded-lg px-6 py-3.5 text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-black-300 font-medium text-sm px-5 py-2.5 text-center me-2 mb-2"
                wire:click="setAction('Edit')">Edit</button>
        </div>
        <div class="flex flex-col sm:flex-row justify-center mx-4 mb-4 p-4 mt-5">
            <button type="button" id="Init-delete" class=" no-blur w-40 rounded-lg px-6 py-3.5 text-white bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-black-300 font-medium text-sm px-5 py-2.5 text-center me-2 mb-2"
                wire:click="setAction('Delete')">Delete</button>
        </div>
        <div class="flex flex-col sm:flex-row justify-center mx-4 mb-4 p-4 mt-5">
            <button type="button" id="Init-inquire" class=" no-blur w-40 rounded-lg px-6 py-3.5 text-white bg-yellow-700 hover:bg-yellow-800 focus:outline-none focus:ring-4 focus:ring-black-300 font-medium text-sm px-5 py-2.5 text-center me-2 mb-2"
                wire:click="setAction('View')">Inquire</button>
        </div>
    </div>

    <div id="OuterPanel">
        <livewire:checkppf />
        <div class="flex flex-col sm:flex-row gap-6 mt-4 items-start justify-center w-full px-4">
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <livewire:defects />
            </div>
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <livewire:rework />
            </div>
        </div>
        <livewire:check-insp />
        <livewire:goodng />
        <livewire:add />
    </div>
    <!-- Include children -->
<!-- <script>
document.addEventListener("DOMContentLoaded", () => {
    const actionButtons = [
        "Init-add",
        "Init-update",
        "Init-delete",
        "Init-inquire"
    ];

    // Auto-click last clicked button after reload
    const lastClicked = localStorage.getItem("lastClicked");
    const alreadyClicked = localStorage.getItem("alreadyClicked");

    if (lastClicked && actionButtons.includes(lastClicked) && !alreadyClicked) {
        const btn = document.getElementById(lastClicked);
        if (btn) {
            // Prevent infinite loop
            localStorage.setItem("alreadyClicked", "true");

            // Trigger the original wire:click
            btn.dispatchEvent(new Event("click", { bubbles: true }));
        }
    } else {
        // Reset flags after page reload
        localStorage.removeItem("alreadyClicked");
    }

    // Add click listeners only to your 4 action buttons
    actionButtons.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener("click", () => {
                localStorage.setItem("lastClicked", id);
                localStorage.removeItem("alreadyClicked"); // reset flag
                location.reload();
            });
        }
    });
});
</script> -->


</div>
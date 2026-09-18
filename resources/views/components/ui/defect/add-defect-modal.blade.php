@props([
    'stagedDefects' => [],
    'modalSelectedLargeDefect' => null,
    'modalLargeQty' => '',
    'modalSmallDefects' => [],
    'largeDefects' => [],
    'readOnly' => false,
])

{{--
=====================================================================
UNIFIED ADD DEFECT MODAL
Step 1: User sees all large defects as clickable cards.
Step 2: After clicking a large defect, small defects appear below
        each with an individual qty input. Large defect qty is shown
        and can be edited. Confirm saves everything at once.
=====================================================================
--}}
<div
    x-show="openAddDefect"
    x-cloak
    x-trap.noscroll="openAddDefect"
    x-transition
    class="fixed inset-0 flex items-center justify-center z-50 p-4 ">

    <!-- Overlay -->
    <div x-show="openAddDefect" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>

    <!-- Modal Panel -->
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-5xl z-50 flex flex-col max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Add Defects</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if(count($stagedDefects) > 0)
                    <span class="text-blue-600 font-medium">{{ count($stagedDefects) }} defect{{ count($stagedDefects) > 1 ? 's' : '' }} staged</span> — add more or confirm
                    @elseif($modalSelectedLargeDefect)
                    <span class="text-blue-600 font-medium">{{ $modalSelectedLargeDefect }}</span> selected — fill in qty, then stage or confirm
                    @else
                    Select one or more defect types to get started
                    @endif
                </p>
            </div>
            <button
                type="button"
                wire:click="resetModalState"
                class="text-gray-400 hover:text-gray-700 rounded-lg w-8 h-8 flex justify-center items-center hover:bg-gray-100 transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>

        <!-- Body: Three-column layout -->
        <div class="flex flex-1">
            <x-ui.defect.staged-list :staged-defects="$stagedDefects" />

            <x-ui.defect.type-selector
                :large-defects="$largeDefects"
                :modal-selected-large-defect="$modalSelectedLargeDefect"
                :staged-defects="$stagedDefects"
                :readOnly="$readOnly" />

            <x-ui.defect.quantity-panel
                :modal-selected-large-defect="$modalSelectedLargeDefect"
                :modal-large-qty="$modalLargeQty"
                :modal-small-defects="$modalSmallDefects" />
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 bg-white z-10">
            <button
                type="button"
                wire:click="addDefectFromModal"
                @click="openAddDefect = false"
                @if($readOnly) disabled @endif
                class="px-5 py-2 rounded-lg bg-[#0F3C89] text-white text-sm font-medium hover:bg-blue-800 transition disabled:opacity-50
                    {{ (count($stagedDefects) === 0 && !$modalSelectedLargeDefect) ? 'opacity-50 cursor-not-allowed' : '' }}">
                Confirm &amp; Add{{ count($stagedDefects) > 0 ? ' (' . count($stagedDefects) . ')' : '' }}
            </button>
        </div>

    </div>
</div>
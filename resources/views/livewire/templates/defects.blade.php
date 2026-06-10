<div x-data="{ openAddDefect: false }" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">

    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold text-center text-white p-4">Defect</p>
    </div>

    <!-- ADD DEFECT BUTTON -->
    <div class="w-full flex justify-center mb-3 px-3 mt-5 @if($systemname === 'ProcessRecord') hidden @endif">
        <button
            @click="$wire.loadExistingDefectsToStage().then(() => { openAddDefect = true })"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            @if($locked) disabled @endif id="add-defect">
            Add Defect / Edit Staged Defects
        </button>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    @if($systemname == 'ProcessRecord')
                    <th class="px-4 py-2">PPFNo</th>
                    @endif
                    <th class="px-6 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    @if($systemname == 'ProcessRecord')
                    <th class="px-4 py-2">Endcode Date</th>
                    @endif
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($defects as $defect)
                <tr wire:key="defect-{{ $defect['type'] }}">
                    @if($systemname == 'ProcessRecord')
                    <td class="px-4 py-2">{{ $defect['ppfno'] }}</td>
                    @endif

                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                    <td class="px-4 py-2">{{ $defect['qty'] > 0 ? $defect['qty'] : '' }}</td>

                    @if($systemname == 'ProcessRecord')
                    <td class="px-4 py-2">{{ $defect['date'] }}</td>
                    @endif

                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openEdit: false }">
                            <div class="flex gap-3">
                                <!-- EDIT DEFECT -->
                                <button
                                    @click="openEdit = true"
                                    class="text-white bg-green-700 px-4 py-2 rounded"
                                    wire:click="startEdit('{{ $defect['type'] }}')"
                                    @if($locked) disabled @endif>
                                    Edit
                                </button>

                                <!-- DELETE DEFECT -->
                                <button
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="if (confirm('Are you sure you want to delete this record?')) $wire.deleteDefectArray(@js($defect['type']))"
                                    @if($locked) disabled @endif>
                                    Delete
                                </button>
                            </div>

                            <!-- EDIT DEFECT MODAL -->
                            <div x-show="openEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit Defect for <span class="font-bold text-red-600">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="number" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model.defer="newQuan">
                                            @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="updateDefectArray" @click="$nextTick(() => openEdit=false)" class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">Save</button>
                                    </div>
                                    <button @click="openEdit=false" class="absolute top-3 right-3 text-gray-500 hover:text-black">✕</button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- SMALL DEFECT ROWS -->
                @if(isset($smallDefects[$defect['type']]))
                @foreach($smallDefects[$defect['type']] as $sDefect)
                <tr class="bg-gray-500" wire:key="smalldefect-{{ $defect['type'] }}-{{ $sDefect['type'] ?? $sDefect['small_defect'] }}">
                    @if ($systemname == 'ProcessRecord')
                    <td class="px-8 py-1"></td>
                    <td class="px-4 py-1"></td>
                    @endif
                    <td class="px-8 py-1">↳ {{ $sDefect['type'] ?? $sDefect['small_defect'] }}</td>
                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmallEdit: false }">
                            <div class="flex gap-2">
                                <button
                                    @click="openSmallEdit=true"
                                    class="text-white bg-green-700 px-4 py-2 rounded"
                                    wire:click="startEditSmall('{{ $defect['type'] }}','{{ $sDefect['type'] ?? $sDefect['small_defect'] }}')"
                                    @if($locked) disabled @endif>Edit</button>
                                <button
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="
                                        if(confirm('Are you sure you want to delete this record?'))
                                            $wire.deleteDefectSmall(
                                                @js($defect['type']),
                                                @js($sDefect['type'] ?? $sDefect['small_defect'])
                                            )"
                                    @if($locked) disabled @endif>
                                    Delete
                                </button>
                            </div>
                            <div x-show="openSmallEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openSmallEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit <span class="font-bold text-red-600">{{ $sDefect['type'] ?? $sDefect['small_defect'] }}</span></h2>
                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="number" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model.defer="newSmallQuan">
                                            @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="updateDefectSmallArray" @click="$nextTick(() => openSmallEdit=false)" class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">Save</button>
                                    </div>
                                    <button @click="openSmallEdit=false" class="absolute top-3 right-3 text-gray-500 hover:text-black">✕</button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endif

                @empty
                <tr>
                    <td colspan="{{ $systemname == 'ProcessRecord' ? 5 : 3 }}" class="px-6 py-4 text-center">
                        No defects added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex-col w-11/12 sm:w-1/3 mt-3">
            <label for="TotalNG" class="block text-sm font-medium text-black">Total NG</label>
            <input type="text" id="TotalNG" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " required wire:model="TotalNg" readonly>
        </div>
    </div>

    <!-- ERROR MESSAGES -->
    @error('newDefect') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

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
                    @click="openAddDefect = false; $wire.resetModalState()"
                    class="text-gray-400 hover:text-gray-700 rounded-lg w-8 h-8 flex justify-center items-center hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>

            <!-- Body: Three-column layout -->
            <div class="flex flex-1">

                <!-- COLUMN 1: Staged Defects -->
                <div class="w-1/3 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest shrink-0">
                        Staged
                        @if(count($stagedDefects) > 0)
                        <span class="ml-1 text-blue-600">({{ count($stagedDefects) }})</span>
                        @endif
                    </p>

                    @if(count($stagedDefects) > 0)
                        @foreach($stagedDefects as $staged)
                        <div class="flex items-start justify-between gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-800">{{ $staged['type'] }}</span>
                                    <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full">qty: {{ $staged['qty'] }}</span>
                                </div>
                                @if(count($staged['smallDefects']) > 0)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($staged['smallDefects'] as $ss)
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">↳ {{ $ss['type'] }}: {{ $ss['qty'] }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1 shrink-0">
                                <button
                                    type="button"
                                    wire:click="selectLargeDefectInModal('{{ $staged['type'] }}')"
                                    class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-100 transition">
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    wire:click="removeStagedDefect('{{ $staged['type'] }}')"
                                    class="text-xs text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50 transition">
                                    ✕
                                </button>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="flex-1 flex items-center justify-center text-center text-gray-300 text-sm px-2">
                            <p>No defects staged yet.</p>
                        </div>
                    @endif
                </div>

                <!-- COLUMN 2: Large Defect Selection -->
                <div class="w-1/3 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest shrink-0">
                        {{ count($stagedDefects) > 0 ? 'Add Another Defect Type' : 'Defect Type' }}
                    </p>
                    <div class="flex flex-col gap-2">
                        @foreach ($Largedefects as $Ldefects)
                        @php
                            $isActive = $modalSelectedLargeDefect === $Ldefects->LargeDefect;
                            $isStaged = collect($stagedDefects)->contains('type', $Ldefects->LargeDefect);
                        @endphp
                        <button
                            type="button"
                            wire:click="selectLargeDefectInModal('{{ $Ldefects->LargeDefect }}')"
                            @if($locked) disabled @endif
                            class="w-full px-3 py-2 rounded-lg border text-sm font-medium text-left transition relative
                            @if($isActive)
                                border-blue-600 bg-blue-50 text-blue-700 ring-2 ring-blue-300
                            @elseif($isStaged)
                                border-green-500 bg-green-50 text-green-700
                            @else
                                border-gray-200 bg-gray-50 text-gray-700 hover:border-blue-400 hover:bg-blue-50
                            @endif">
                            {{ $Ldefects->LargeDefect }}
                            @if($isStaged && !$isActive)
                            <span class="absolute top-1 right-1 w-2 h-2 bg-green-500 rounded-full"></span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    @error('modalSelectedLargeDefect')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- COLUMN 3: Qty + Small Defects -->
                <div class="w-1/3 px-4 py-4 flex flex-col gap-3">
                    @if($modalSelectedLargeDefect)
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest shrink-0">
                            Configure: <span class="text-blue-600 normal-case font-bold">{{ $modalSelectedLargeDefect }}</span>
                        </p>

                        {{-- Large defect qty --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Quantity for <span class="text-blue-600">{{ $modalSelectedLargeDefect }}</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                wire:model="modalLargeQty"
                                wire:change="onModalLargeQtyChange"
                                class="block w-full border border-gray-300 rounded-md px-3 py-2 text-gray-900 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-white"
                                placeholder="Enter quantity">
                            @error('modalLargeQty')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Small defects --}}
                        @if($modalSmallDefects && count($modalSmallDefects) > 0)
                        <div class="flex flex-col gap-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                                Small Defects <span class="normal-case font-normal">(optional)</span>
                            </p>
                            @foreach($modalSmallDefects as $index => $small)
                            <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-3 py-2"
                                wire:key="modal-small-{{ $index }}">
                                <span class="flex-1 text-sm text-gray-700">{{ $small['type'] }}</span>
                                <input
                                    type="number"
                                    min="0"
                                    wire:model="modalSmallDefects.{{ $index }}.qty"
                                    class="w-16 border border-gray-300 rounded-md px-2 py-1 text-sm text-gray-900 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                                    placeholder="Qty">
                            </div>
                            @endforeach

                            {{-- Live small total indicator --}}
                            <div class="text-right text-xs text-gray-500">
                                Small total:
                                <span class="font-semibold {{ collect($modalSmallDefects)->sum(fn($s) => (int)($s['qty'] ?: 0)) > (int)($modalLargeQty ?: 0) ? 'text-red-500' : 'text-green-600' }}">
                                    {{ collect($modalSmallDefects)->sum(fn($s) => (int)($s['qty'] ?: 0)) }}
                                </span>
                                / {{ $modalLargeQty ?: '—' }}
                            </div>
                            @error('modalSmallDefects')
                            <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif

                        {{-- Stage this defect button --}}
                        <button
                            type="button"
                            wire:click="stageDefect"
                            class="w-full px-4 py-2 rounded-lg border-2 border-blue-500 text-blue-600 text-sm font-semibold hover:bg-blue-50 transition mt-auto">
                            + Stage This Defect
                        </button>

                    @else
                        <div class="flex-1 flex items-center justify-center text-center text-gray-300 text-sm px-2">
                            <p>Select a defect type in the middle to configure quantity and small defects.</p>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 bg-white z-10">
                <button
                    type="button"
                    @click="openAddDefect = false; $wire.resetModalState()"
                    class="px-5 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="addDefectFromModal"
                    @click="openAddDefect = false"
                    @if($locked) disabled @endif
                    class="px-5 py-2 rounded-lg bg-[#0F3C89] text-white text-sm font-medium hover:bg-blue-800 transition disabled:opacity-50
                        {{ (count($stagedDefects) === 0 && !$modalSelectedLargeDefect) ? 'opacity-50 cursor-not-allowed' : '' }}">
                    Confirm &amp; Add{{ count($stagedDefects) > 0 ? ' (' . count($stagedDefects) . ')' : '' }}
                </button>
            </div>

        </div>
    </div>

</div>
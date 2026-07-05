<div x-data="{ openAddRework: false }" @close-add-rework.window="openAddRework = false" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">
    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Rework</p>
    </div>
    <div class="w-full flex flex-col items-center mt-5">
        <button @click="$wire.loadExistingStagedRework().then(() => { openAddRework = true; $nextTick(() => $refs.firstInput?.focus()); })"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            type="button" id="add-rework" @if($locked) disabled @endif>
            Add Rework / Edit Staged Rework
        </button>
    </div>
    <div class="overflow-x-auto mt-3 mx-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">HFNo</th>
                    <th class="px-4 py-2">RWK Defect</th>
                    <th class="px-4 py-2">Qty</th>
                    <th class="px-4 py-2">Total Insp</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $reworkss as $reworks )
                <tr wire:key="rework-{{ $reworks['type'] }}">
                    <td class="px-4 py-2">{{ $reworks['hfno'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['quan'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmall: false }" class="flex justify-center">

                            <div class="flex gap-3">
                                <button
                                    @if($locked) disabled @endif
                                    @click="openSmall = true"
                                    wire:click="startEditRework('{{ $formId }}', '{{ $reworks['type'] }}', '{{ $reworks['hfno'] }}')"
                                    class="text-white bg-green-700 px-4 py-2 rounded">
                                    Edit
                                </button>

                                <!-- DELETE BUTTON -->
                                <button
                                    @if($locked) disabled @endif
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="
            if (confirm('Are you sure you want to delete this record?')) {
                 $wire.deleteRework(
            @js($reworks['hfno']),
            @js($reworks['type'])
        );
            }">
                                    Delete
                                </button>
                            </div>


                            <!-- BACKDROP -->
                            <div
                                x-show="openSmall"
                                x-transition.opacity
                                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                                style="display: none"></div>

                            <!-- MODAL WRAPPER -->
                            <div
                                x-show="openSmall"
                                x-transition
                                class="fixed top-0 right-0 left-0 z-50 justify-center items-center flex w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
                                style="display: none">
                                <div class="relative p-4 w-full max-w-2xl max-h-full">

                                    <!-- MODAL CONTENT -->
                                    <div class="relative bg-white rounded-lg shadow-sm">

                                        <!-- HEADER (copied style) -->
                                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                            <h3 class="text-xl font-semibold text-gray-900">
                                                Edit Rework for
                                                <span class="font-bold text-red-600 text-xl">{{ $reworks['type']}} (HFNO {{ $reworks['hfno'] }})</span>
                                            </h3>

                                            <button
                                                @if($locked) disabled @endif
                                                type="button"
                                                @click="openSmall = false"
                                                wire:click="$set('newQuan', ''); $set('totalInsp.{{ $formId }}', '')"
                                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 14 14">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- BODY -->
                                        <div class="flex flex-col justify-center gap-4 my-4 items-center">

                                            <!-- QUANTITY -->
                                            <div class="flex-col w-11/12 sm:w-1/3">
                                                <label class="block text-sm font-medium text-black">Quantity</label>

                                                <input
                                                    type="number"
                                                    class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                    wire:model.defer="newQuan">

                                                @error('newQuan')
                                                <p class="text-red-500 text-sm">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="flex-col w-11/12 sm:w-1/3">
                                                <label class="block text-sm font-medium text-black">Total Insp</label>

                                                <input
                                                    type="number"
                                                    class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                    wire:model.defer="totalInsp.{{ $formId }}">

                                                @error('totalInsp.' . $formId)
                                                <p class="text-red-500 text-sm">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="w-full flex flex-col items-center mb-5">
                                                <button
                                                    @if($locked) disabled @endif
                                                    wire:click="updateRework"
                                                    @click="$nextTick(() => openSmall = false)"
                                                    type="button"
                                                    class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm text-center">
                                                    Edit
                                                </button>
                                            </div>


                                        </div><!-- END BODY -->

                                    </div><!-- END CONTENT -->

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center">
                        No defects added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="HfNo" class="block text-sm font-medium text-black">Total NG Rework</label>
            <input type="text" id="TotalNgRework" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder="" required wire:model="totalngrework.{{ $formId }}" readonly>
        </div>
    </div>
    @error('hfno.' . $formId)
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('totalInsp.' . $formId)
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newRework')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newQuan')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    {{-- =====================================================================
         ADD REWORK MODAL — multi-staging: stage multiple defect types then
         confirm them all in one go.
         ===================================================================== --}}
    <div x-show="openAddRework" x-cloak x-trap.noscroll="openAddRework" x-transition
        class="fixed inset-0 flex items-center justify-center z-50 p-4">

        <!-- Overlay -->
       <div x-show="openAddDefect" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>

        <!-- Modal Panel -->
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl z-50 flex flex-col max-h-[90vh] overflow-y-auto">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Add Rework</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        @if(count($stagedReworks) > 0)
                        <span class="text-blue-600 font-medium">{{ count($stagedReworks) }} entr{{ count($stagedReworks) > 1 ? 'ies' : 'y' }} staged</span> — add more or confirm
                        @else
                        Select a defect type and quantity, then stage or confirm
                        @endif
                    </p>
                </div>
                <button type="button"
                    wire:click="resetReworkModal"
                    class="text-gray-400 hover:text-gray-700 rounded-lg w-8 h-8 flex justify-center items-center hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex flex-1 justify-center gap-6 px-6 py-4">
                <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                    <!-- HF No. + Total Insp (readonly, pre-filled) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">HF No.</label>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly x-ref="firstInput"
                                    class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                                    wire:blur="CheckHf"
                                    wire:model.lazy="hfno.{{ $formId }}">
                                @if(!empty($hfno[$formId]))
                                <span class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $hfname[$formId] ?? '' }}</span>
                                @endif
                            </div>
                            @error('hfno.' . $formId) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">Total Insp Qty.</label>
                            <input type="number" readonly
                                class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                                wire:model="totalInsp.{{ $formId }}">
                            @error('totalInsp.' . $formId) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Staged entries list -->
                    @if(count($stagedReworks) > 0)
                    <div class="border border-blue-200 bg-blue-50 rounded-lg px-4 py-3 space-y-2">
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-widest mb-1">Staged (ready to confirm)</p>
                        @foreach($stagedReworks as $si => $staged)
                        <div class="flex items-center justify-between gap-2 bg-white border border-blue-100 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <span class="text-sm font-semibold text-gray-800 truncate">{{ $staged['type'] }}</span>
                                <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full shrink-0">qty: {{ $staged['quan'] }}</span>
                            </div>
                            <button type="button"
                                wire:click="removeStagedRework({{ $si }})"
                                class="text-red-500 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition shrink-0">
                                ✕
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                        {{ count($stagedReworks) > 0 ? 'Add Another Entry' : 'Rework Entry' }}
                    </p>

                    <!-- Defect type selector -->
                    <div>
                        <label class="block text-sm font-medium text-black mb-1">Rework Defect</label>
                        <select wire:model="newRework"
                            class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            <option value="">-- Select Rework --</option>
                            @foreach ($rework as $r)
                            @php
                            $alreadyStaged = collect($stagedReworks)->contains('type', $r->DefectType);
                            $alreadyCommitted = collect($reworkss)->contains(fn($rw) => ($rw['hfno'] ?? '') === ($hfno[$formId] ?? '') && ($rw['type'] ?? '') === $r->DefectType);
                            @endphp
                            <option value="{{ $r->DefectType }}"
                                @if($alreadyStaged || $alreadyCommitted) disabled @endif>
                                {{ $r->DefectType }}{{ ($alreadyStaged || $alreadyCommitted) ? ' (added)' : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('newRework') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-black mb-1">Quantity</label>
                        <input type="number" min="1"
                            class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            wire:model="newQuan">
                        @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Stage button -->
                    <button type="button"
                        wire:click="stageRework"
                        class="w-full px-4 py-2 rounded-lg border-2 border-blue-500 text-blue-600 text-sm font-semibold hover:bg-blue-50 transition">
                        + Stage This Entry
                    </button>
                </div>

                <!-- Divider label -->


            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button"
                    wire:click="addRework"
                    @click="openAddRework = false"
                    @if($locked) disabled @endif
                    class="px-5 py-2 rounded-lg bg-[#0F3C89] text-white text-sm font-medium hover:bg-blue-800 transition disabled:opacity-50">
                    Confirm &amp; Add{{ count($stagedReworks) > 0 ? ' (' . count($stagedReworks) . ')' : '' }}
                </button>
            </div>

        </div>
    </div>

</div>
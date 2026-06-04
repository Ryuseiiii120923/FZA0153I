<div x-data="{ open: false }"> <!-- sync Alpine with Livewire -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-white">Hand Finishing Rework Encoding</h2>
        <p class="text-sm text-gray-500 mt-1">Record and encode hand finishing rework data</p>
    </div>
    @if (session()->has('success'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
            <p class="text-gray-700 mb-4">{{ session('success') }}</p>

            <button
                @click="open = false;
            location.reload();
            "
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif

    @if($errorMessage)
    <p>{{ $errorMessage }}</p>
    @endif
    <!-- HEADER -->
    <div class="bg-gray-700 w-full ">
        <p class="text-4xl font-extrabold text-center text-white p-4 ">For Rework</p>
    </div>

    <div class="flex items-center mt-4 gap-2 w-full sm:w-auto sm:flex-1">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z" />
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search PPF number..."
                class="w-full min-w-0 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        </div>
        @if($search)
        <button
            wire:click="clearSearch"
            class="text-sm text-white border border-gray-300 rounded-lg hover:text-gray-700 px-2 py-1 hover:bg-gray-100 transition whitespace-nowrap">
            Clear
        </button>
        @endif
    </div>
    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2  text-center">PPFNO</th>
                    <th class="px-4 py-2  text-center">Rework No</th>
                    <th class="px-4 py-2  text-center">Total Rework</th>
                    <th class="px-4 py-2 text-center">Action</th>
                    <th class="px-4 py-2  text-center">Status</th>
                </tr>
            </thead>

            <tbody class="bg-gray-700">
                @foreach ($filteredReworks as $data )
                <tr>
                    <td class="px-4 py-2  text-center">{{ (int) $data['ppfno'] ?? '' }}</td>
                    <td class="px-4 py-2  text-center">{{ (int) $data['rework_no'] ?? 0 }}</td>
                    <td class="px-4 py-2 text-center">{{ $data['total_rework'] ?? '' }}</td>
                    <td class=" py-2 flex justify-center gap-2">

                        <button
                            class="text-white bg-green-700 px-4 py-2 rounded  @if (($data['status'] ?? '') == 'Confirmed') opacity-50  @endif"
                            @if (($data['status'] ?? '' )=='Confirmed' ) disabled @endif
                            @click="open = true; $wire.confirm_ppf('{{ $data['ppfno'] }}', '{{ $data['rework_no'] }}')">
                            Confirm
                        </button>
                        <button
                            class="text-white bg-blue-700 px-4 py-2 rounded"
                            @click="open = true; $wire.editPPFFromChild('{{ $data['ppfno'] }}', '{{ $data['rework_no'] }}')">
                            Edit
                        </button>
                        <button
                            wire:click="confirmDelete('{{ $data['ppfno'] }}', '{{ $data['rework_no'] }}')"
                            class="bg-red-500 text-white px-3 py-1 rounded">
                            Delete
                        </button>

                        @if($confirmingDelete)
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                            <div class="bg-white p-6 rounded shadow">

                                <p class="text-black">Are you sure you want to delete this PPF?</p>

                                <div class="flex justify-center gap-2 mt-4">
                                    <button
                                        class="bg-red-600 text-white px-4 py-2"
                                        wire:click="delete_ppf">
                                        Yes, Delete
                                    </button>

                                    <button
                                        class="bg-blue-400 text-white px-4 py-2"
                                        wire:click="$set('confirmingDelete', false)">
                                        Cancel
                                    </button>
                                </div>

                            </div>
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">{{ $data['status'] ?? '' }}</td>
                </tr>
                @endforeach
                <div class="flex items-center gap-2 text-sm">
                    <label for="perPage" class="text-white">Rows per page:</label>
                    <select
                        id="perPage"
                        wire:model.live="perPage"
                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                @if($pendingRework!== null)

                @endif
            </tbody>
        </table>
    </div>

    <!-- MODAL -->

    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
        class="fixed inset-0 flex justify-center bg-black/50 z-50 py-6">

        <!-- MODAL -->
        <div class="bg-white w-full max-w-6xl h-full max-h-[90vh] flex flex-col rounded-lg shadow-lg overflow-hidden">

            <!-- HEADER -->
            <div class="bg-gray-700 shrink-0">
                <p class="text-4xl font-extrabold text-center text-white p-4">
                    HF Rework
                </p>
            </div>

            <!-- BUTTON -->
            <div class="shrink-0 px-5 py-4 flex gap-3 bg-white shadow-md">
                <button wire:click="addNew"
                    class="bg-green-600 text-white px-4 py-2 rounded-md">
                    + Add Worker
                </button>
                <button
                    @click="open = false"
                    wire:click="removeSelectedPPF"
                    class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <button
                    @click="open = false"
                    wire:click="saveHFRework()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Save
                </button>
            </div>


            <!-- 🔥 SCROLL AREA -->
            <div class="overflow-y-auto px-5 py-4 space-y-4">

                @foreach($forms as $formId => $form)
                @if(($form['ppfno'] ?? null) == $selectedPPF)
                <div wire:key="worker-form-{{ $formId }}"
                    class="border rounded shadow p-3"
                    x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

                    <!-- HEADER -->
                    <div class="flex justify-between items-center cursor-pointer px-4 py-2 bg-gray-100"
                        @click="open = !open; $wire.toggle('{{ $formId }}')">

                        <span>PPF #: {{ $form['ppfno'] ?? 'New' }}</span>
                        <span>Status: {{ $form['status'] ?? 'Pending' }}</span>

                        <div class="flex justify-end gap-2">
                            <button
                                @click.stop
                                wire:click="editHF('{{ $formId }}')"
                                class="rounded px-3 py-1 bg-blue-600 text-white text-sm">
                                Edit
                            </button>

                            <button @click.stop wire:click="remove('{{ $formId }}')"
                                class="bg-red-600 text-white px-3 py-1 rounded">
                                Remove
                            </button>
                        </div>


                    </div>

                    <!-- BODY -->
                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 max-h-0"
                        x-transition:enter-end="opacity-100 max-h-screen"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 max-h-screen"
                        x-transition:leave-end="opacity-0 max-h-0"
                        class="overflow-hidden">

                        <div class="flex flex-col gap-4 p-4 bg-gray-50 rounded">

                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="w-full sm:w-1/2">
                                    <label class="block text-sm font-medium">HF ID</label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" wire:model="forms.{{ $formId }}.hf_id" class="w-full border bg-gray-500 p-2 rounded" readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}">
                                        @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                                    </div>
                                </div>
                                @error('forms.' . $formId . '.hf_id')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                                @enderror
                                <div class="w-full sm:w-1/2">
                                    <label class="block text-sm font-medium">Total Inspect</label>
                                    <input type="number" readonly wire:model="forms.{{ $formId }}.total_inspect" class="w-full border bg-gray-500 p-2 rounded" placeholder="Enter Total Inspect">
                                </div>
                            </div>

                            <div
                                x-data="{ open: @entangle('modalOpen.' . $formId) }"
                                x-show="open"
                                x-cloak
                                class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
                                @keydown.escape.window="open = false">
                                <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <label class="block text-sm font-medium">HF ID</label>
                                            @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                                            <input type="number"
                                                wire:model.lazy="forms.{{ $formId }}.hf_id"
                                                wire:blur="CheckHf('{{ $formId }}')"
                                                class="w-full border p-2 rounded"
                                                placeholder="Enter HF ID"
                                                maxlength="4" pattern="\d{4}">

                                            @error('forms.' . $formId . '.hf_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Total Inspect</label>
                                            <input type="number"
                                                wire:model="forms.{{ $formId }}.total_inspect"
                                                class="w-full border p-2 rounded"
                                                placeholder="Enter Total Inspect">
                                            @error('forms.' . $formId . '.total_inspect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>

                                        <button wire:click="saveHF('{{ $formId }}')" @if(!empty($hasErrorForm[$formId])) disabled @endif class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                            Save
                                        </button>

                                        <button
                                            wire:click="CloseModal('{{ $formId }}')"
                                            class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                            Exit
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-6">
                                <livewire:templates.defects
                                    :formId="$formId"
                                    :loadedDefects="$form['defects']"
                                    :loadedSmallDefects="$form['smallDefects']"
                                    :dispatchPrefix="'operator'"
                                    :key="'defects-'.$formId" />
                            </div>
                        </div>

                    </div>

                </div>
                @endif
                @endforeach

            </div>

        </div>
    </div>
</div>
<div x-data="{ open: false }"> <!-- sync Alpine with Livewire -->

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

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2  text-center">PPFNO</th>
                    <th class="px-4 py-2  text-center">Total Rework</th>
                    <th class="px-4 py-2 text-center">Action</th>
                    <th class="px-4 py-2  text-center">Status</th>
                </tr>
            </thead>

            <tbody class="bg-gray-700">
                @foreach ($pendingRework as $data )
                <tr>
                    <td class="px-4 py-2  text-center">{{ (int) $data['ppfno'] ?? '' }}</td>
                    <td class="px-4 py-2 text-center">{{ $data['total_rework'] ?? '' }}</td>
                    <td class=" py-2 flex justify-center gap-2">

                        <button
                            class="text-white bg-green-700 px-4 py-2 rounded  @if (($status[$data['ppfno']] ?? '') == 'Confirmed') opacity-50  @endif"
                            @if (($status[$data['ppfno']] ?? '' )=='Confirmed' ) disabled @endif
                            @click="open = true; $wire.confirm_ppf('{{ $data['ppfno'] }}')">
                            Confirm
                        </button>
                        <button
                            class="text-white bg-blue-700 px-4 py-2 rounded"
                            @click="open = true; $wire.editPPFFromChild('{{ $data['ppfno'] }}')">
                            Edit
                        </button>
                        <button
                            wire:click="confirmDelete('{{ $data['ppfno'] }}')"
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
                    <td class="px-4 py-2 text-center">{{ $status[$data['ppfno']] ?? '' }}</td>
                </tr>
                @endforeach
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
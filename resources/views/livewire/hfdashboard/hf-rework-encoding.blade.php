<div x-data="{ open: @entangle('open') }"> <!-- sync Alpine with Livewire -->

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
    <!-- HEADER -->
    <div class="bg-gray-700 w-full ">
        <p class="text-4xl font-extrabold text-center text-white p-4 ">For Rework</p>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">PPFNO</th>
                    <th class="px-4 py-2">Total Rework</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>

            <tbody class="bg-gray-700">
                @foreach ($pendingRework as $data )
                <tr>
                    <td class="px-4 py-2">{{ (int) $data['ppfno'] ?? '' }}</td>
                    <td class="px-4 py-2">{{ $data['total_rework'] ?? '' }}</td>
                    <td class="px-4 py-2 flex justify-start">
                        <button
                            class="text-white bg-green-700 px-4 py-2 rounded"
                            @click="open = true; $wire.confirm_ppf('{{ $data['ppfno'] }}')">
                            Confirm
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- MODAL -->
    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="open = false"
        class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
        <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
            @if($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
            @endif

            @if($errorMessage)
            <div class="alert alert-danger">{{ $errorMessage }}</div>
            @endif
            <h2 class="text-lg font-bold mb-4">Confirm Rework</h2>

            <div class="flex flex-row gap-6 mx-auto">
                <div class="w-full">
                    <div class="flex-col w-full ">
                        <label for="HfNo" class="block text-sm font-medium text-black">HF No.</label>
                        <div class="flex sm:flex-row flex col gap-3">
                            <input type="text" id="HfNos" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                placeholder=" " required wire:blur="CheckHf" wire:model.lazy="hf_id">
                            @if(!empty($hf_id))
                            <p class="text-sm font-medium text-black mt-3">{{ $hfname }}</p>
                            @endif
                        </div>

                        @error('hf_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                    </div>
                </div >
                <div class="w-full">
                    <div class="flex-col w-full">
                        <label for="totalInspct" class="block text-sm font-medium text-black">Total Inspct Qty.</label>
                        <input type="text" id="totalInspct" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                            placeholder=" " required wire:model="total_inspect" readonly>
                        @error('totalInsp') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>
                </div>


            </div>

            <!-- CHILD COMPONENTS -->
            <div class="flex flex-col sm:flex-row gap-6 mt-4">
                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:hfdashboard.defects :wire:key="'defects-modal'" />
                </div>

                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:hfdashboard.reworks :wire:key="'rework-modal'" />
                </div>
            </div>

            <!-- SAVE -->
            <button
                wire:click="saveHF('{{ $selectedPPF }}')"
                @if(!empty($error)) disabled @endif
                class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                Save
            </button>

            <!-- EXIT -->
            <button
                @click="open = false; $wire.CloseModal()
                document.querySelectorAll('[modal-backdrop]').forEach(el => el.remove());"

                class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                Exit
            </button>

        </div>
    </div>

</div>
<?php

use App\Models\Forms\HF;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public array $inspections = [];
    public bool $readOnly = false;

    public function FetchTotalInspection(int $ppf)
    {

        $this->inspections = HF::with('worker.employeeName')
            ->select('updated_at', 'inspId')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->where('ppfno', $ppf)
            ->groupBy('inspId', 'updated_at')
            ->get()
            ->toArray();
    }

    #[On('checked-ppf')]
    public function checkPPf(int $ppf)
    {
        $this->FetchTotalInspection($ppf);
    }

    public function locked($data)
    {
        $this->readOnly = $data;
    }
};
?>

<div>
    <div class=" bg-white shadow-lg px-3 py-4 @if($readOnly) opacity-50 cursor-not-allowed @endif">
        <div class="bg-gray-700 w-full ">
            <p class="text-4xl font-extrabold  text-center text-white p-4 ">Total Inspection</p>
        </div>
        <div class="overflow-x-auto mt-3">
            <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-900 text-white text-left">
                    <tr>
                        <th class="px-4 py-2">Inspector Id</th>
                        <th class="px-4 py-2">Inspector Name</th>
                        <th class="px-4 py-2">Total Inspection</th>
                        <th class="px-4 py-2">Date Encode</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-700">
                    @forelse ($inspections ?? [] as $inspection)
                    <tr>
                        <td class="px-4 py-2">
                            {{ $inspection['inspId'] ?? '-' }}
                        </td>

                        <td class="px-4 py-2">
                           {{ $inspection['worker']['employee_name']['名前'] ?? '-' }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $inspection['total_inspect'] ?? '-' }}
                        </td>

                        <td class="px-4 py-2">
                            {{  \Carbon\Carbon::parse($inspection['updated_at'])->format('Y/m/d') ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center">
                            No inspections found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
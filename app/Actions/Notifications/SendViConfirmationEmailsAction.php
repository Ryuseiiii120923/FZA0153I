<?php

namespace App\Actions\Notifications;

use App\Mail\PlainNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendViConfirmationEmailsAction
{
    /**
     * @param array{
     *   pro: string, ppf: string, part: string, lot: string, material: string,
     *   hf: float, good: float, ng: float, excess: float, lacking: float,
     *   rework: float, sample: float, confirmedByCode: string,
     *   blnAdvance: bool, confirmUserCode: string, confirmRemark: string
     * } $data
     */
    public function execute(array $data): void
    {
        $remarks = $this->quantityRemarks($data);

        if ($remarks !== null) {
            $this->sendQuantityDiscrepancy($data, $remarks);
        }

        // if ($data['blnAdvance']) {
        //     $this->sendFifoProblem($data);
        // }

        // if ($data['pro'] === '0') {
        //     $this->sendProductDimensionIfNeeded($data);
        // }
    }

    private function quantityRemarks(array $data): ?string
    {
        if (!in_array($data['pro'], ['0', '1'], true)) {
            return null;
        }

        $hf = (float) $data['hf'];
        $excess = (float) $data['excess'];
        $lacking = (float) $data['lacking'];

        if ($excess > 0) {
            return ($excess + $hf > $hf * 1.01)
                ? round(($excess / $hf) * 100, 2) . '% excess'
                : null;
        }

        if ($lacking > 0) {
            return ($hf - $lacking < $hf * 0.99)
                ? round(($lacking / $hf) * 100, 2) . '% lacking'
                : null;
        }

        return null;
    }

    private function sendQuantityDiscrepancy(array $data, string $remarks): void
    {
        $body = "\n     " . $this->employeeName($data['confirmedByCode'])
            . " confirmed quantity discrepancy. Please see information below:\n\n"
            . $this->formatLine('PPFNo', $data['ppf'])
            . $this->formatLine('PartNo', $data['part'])
            . $this->formatLine('LotNo', $data['lot'])
            . $this->formatLine('Expected', number_format($data['hf']))
            . $this->formatLine('Good Quantity', number_format($data['good']))
            . $this->formatLine('NG Qty', number_format($data['ng']))
            . $this->formatLine('Excess Qty', number_format($data['excess']))
            . $this->formatLine('Lacking Qty', number_format($data['lacking']))
            . $this->formatLine('Rework Qty', number_format($data['rework']))
            . $this->formatLine('QC Sample Qty', number_format($data['sample']))
            . "\n     REMARKS : {$remarks}\n";

        $this->send('Quantity Discrepancy', $body, 'FZA0153I2');
    }

    private function sendFifoProblem(array $data): void
    {
        $body = "\n     " . $this->employeeName($data['confirmUserCode'])
            . " confirmed FIFO Abnormality on VI Result encoding. Please see information below:\n\n"
            . $this->formatLine('PPFNo', $data['ppf'])
            . $this->formatLine('PartNo', $data['part'])
            . $this->formatLine('LotNo', $data['lot'])
            . $this->formatLine('Expected', number_format($data['hf']))
            . $this->formatLine('Good Quantity', number_format($data['good']))
            . $this->formatLine('NG Qty', number_format($data['ng']))
            . $this->formatLine('Excess Qty', number_format($data['excess']))
            . $this->formatLine('Lacking Qty', number_format($data['lacking']))
            . $this->formatLine('Rework Qty', number_format($data['rework']))
            . $this->formatLine('QC Sample Qty', number_format($data['sample']))
            . "\n     REMARKS : {$data['confirmRemark']}\n";

        $this->send('FIFO Problem', $body, 'FZA0153I3');
    }

    private function sendProductDimensionIfNeeded(array $data): void
    {
        $body = "\n     Please see information below:\n\n";
        $needsEmail = false;

        $hasProductProperty = DB::table('ProductProperty')
            ->where('PPFNO', $data['ppf'])
            ->exists();

        if (!$hasProductProperty) {
            $body .= $this->formatLine('PPFNo', $data['ppf']);
            $body .= $this->formatLine('PartNo', $data['part']);
            $needsEmail = true;
        }

        $record = DB::table('成形実績 as A')
            ->join('LOTNO', 'A.流動NO', '=', 'LOTNO.流動NO')
            ->leftJoin('材料物性', function ($join) {
                $join->on('材料名', '=', '材料品番')
                     ->on('LOTNO.混練LOTNO', '=', '材料物性.混練LOTNO');
            })
            ->where('A.流動NO', $data['ppf'])
            ->select('材料物性.判定結果', 'LOTNO.混練LOTNO')
            ->first();

        $body .= "\n";

        if ($record) {
            $judgement = trim((string) $record->判定結果);

            if ($judgement === '' || $judgement === 'NG') {
                $body .= $this->formatLine('MatNo', $data['material']);
                $body .= $this->formatLine('LotNo', (string) $record->混練LOTNO);
                if ($judgement === 'NG') {
                    $body .= $this->formatLine('Judgement', $judgement);
                }
                $needsEmail = true;
            }
        }

        if ($needsEmail) {
            $this->send('No Product Dimension', $body, 'FZA0153I4');
        }
    }

    private function send(string $subject, string $body, string $moduleId): void
    {
        $recipients = $this->resolveRecipients($moduleId);
 

        if (empty($recipients)) {
            Log::warning("No recipients configured for module {$moduleId}");
            return;
        }

        try {
            Mail::to($recipients)->send(new PlainNotificationMail($subject, $body));
        } catch (\Throwable $e) {
            Log::error("Failed to send \"{$subject}\" email for module {$moduleId}", [
                'ex ception' => $e->getMessage(),
            ]);
        }
    }

    private function formatLine(string $label, string $value): string
    {
        return sprintf("          %-13s :   %s\n", $label, $value);
    }

    private function employeeName(string $employeeCode): string
    {
        return DB::table('社員')->where('社員CD', $employeeCode)->value('名前') ?? $employeeCode;
    }

    private function resolveRecipients(string $moduleId): array
{
    try {
        return DB::table('EmailDist')
            ->join('EmailList', 'EmailDist.UserID', '=', 'EmailList.UserID')
            ->where('EmailDist.ProgramID', $moduleId)
            ->whereNotNull('EmailList.EmailAddress')
            ->where('EmailList.EmailAddress', '!=', '')
            ->distinct()
            ->pluck('EmailList.EmailAddress')
            ->toArray();
    } catch (\Throwable $e) {
        Log::error("Failed to resolve recipients for module {$moduleId}", [
            'exception' => $e->getMessage(),
        ]);
        return [];
    }
}
}
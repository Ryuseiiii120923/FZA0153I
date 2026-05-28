<?php

namespace App\Http\Controllers;

use App\Actions\BuildGeneralProcessRecordAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Thin HTTP controller — only responsible for:
 *   1. Delegating to the action class
 *   2. Loading the view and streaming the PDF
 *
 * All business logic lives in BuildGeneralProcessRecordAction and its
 * collaborating services.
 */
class PdfController extends Controller
{
    public function __construct(
        private readonly BuildGeneralProcessRecordAction $buildAction,
    ) {}

    public function generate(string $ppf = '')
    {
        $data = $this->buildAction->execute($ppf);

        $pdf = Pdf::loadView('pdf.general-process-record', ['data' => $data])
            ->setPaper('a4', 'landscape');

        return $pdf->stream('general-process-record.pdf');
    }
}

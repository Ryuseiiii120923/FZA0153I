<?php

namespace App\Http\Controllers;

use App\Services\API\ProcessRecordApiService;
use Illuminate\Routing\Controller;  

class SomeController extends Controller
{
    public function download(string $ppf, ProcessRecordApiService $api)
    {
        try {
            $pdfBytes = $api->fetchPdf($ppf);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['ppf' => $e->getMessage()]);
        }

        return response($pdfBytes, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"process-record-{$ppf}.pdf\"");
    }
}
<?php

namespace App\Http\Controllers\Susdat;

use App\Http\Controllers\Controller;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelWriter;

class BatchConversionController extends Controller
{
    /**
     * Show the batch conversion form
     */
    public function index()
    {
        return view('susdat.batch.index');
    }

    /**
     * Process the batch conversion
     */
    public function convert(Request $request)
    {
        $request->validate([
            'identifiers' => 'required|string|max:10000',
            'input_type' => 'required|in:cas_no,substance_name,std_inchikey,susdat_id',
            'exact_match' => 'boolean',
        ]);

        $identifiers = array_filter(
            array_map('trim', explode("\n", $request->identifiers)),
            function ($value) {
                return ! empty($value);
            }
        );

        $inputType = $request->input_type;
        $exactMatch = $request->boolean('exact_match');
        $results = [];

        foreach ($identifiers as $identifier) {
            $substances = $this->findSubstance($identifier, $inputType, $exactMatch);
            if ($substances && $substances->count() > 0) {
                foreach ($substances as $substance) {
                    $results[] = [
                        'input' => $identifier,
                        'susdat_id' => $substance->code,
                        'substance_id' => $substance->id,
                        'substance_name' => $substance->name,
                        'cas_no' => $substance->cas_number,
                        'std_inchikey' => $substance->stdinchikey,
                        'found' => true,
                    ];
                }
            } else {
                $results[] = [
                    'input' => $identifier,
                    'susdat_id' => null,
                    'substance_name' => null,
                    'cas_no' => null,
                    'std_inchikey' => null,
                    'found' => false,
                ];
            }
        }

        // Store form data in session for update functionality
        session([
            'batch_conversion_data' => [
                'identifiers' => $request->identifiers,
                'input_type' => $inputType,
                'exact_match' => $exactMatch,
            ],
            'batch_conversion_results' => $results,
            'batch_conversion_input_type' => $inputType,
            'batch_conversion_exact_match' => $exactMatch,
        ]);

        // Sort results by code (susdat_id)
        usort($results, function ($a, $b) {
            // Handle null values (not found substances)
            if ($a['susdat_id'] === null && $b['susdat_id'] === null) {
                return 0;
            }
            if ($a['susdat_id'] === null) {
                return 1;
            }
            if ($b['susdat_id'] === null) {
                return -1;
            }

            return strcmp($a['susdat_id'], $b['susdat_id']);
        });

        return view('susdat.batch.results', compact('results', 'inputType', 'exactMatch'));
    }

    /**
     * Show the batch conversion form with pre-filled data from previous conversion
     */
    public function update()
    {
        $formData = session('batch_conversion_data', []);

        return view('susdat.batch.index', compact('formData'));
    }

    /**
     * Download results as CSV file
     */
    public function downloadCsv(Request $request)
    {
        $results = session('batch_conversion_results', []);

        if (empty($results)) {
            return redirect()->back()->with('error', 'No results available for download.');
        }

        $filename = 'batch_conversion_results_'.date('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($results) {
            $output = fopen('php://output', 'w');

            // Add header row with input identifier as first column
            fputcsv($output, [
                'Input Identifier',
                'SUSDAT ID',
                'Substance Name',
                'CAS No.',
                'StdInChIKey',
            ]);

            // Add data rows with input identifier as first column
            foreach ($results as $result) {
                if ($result['found']) {
                    fputcsv($output, [
                        $result['input'],
                        'NS'.$result['susdat_id'],
                        $result['substance_name'] ?? '-',
                        $result['cas_no'] ?? '-',
                        $result['std_inchikey'] ?? '-',
                    ]);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download results as XLSX file
     */
    public function downloadXlsx(Request $request)
    {
        $results = session('batch_conversion_results', []);

        if (empty($results)) {
            return redirect()->back()->with('error', 'No results available for download.');
        }

        $filename = 'batch_conversion_results_'.date('Y-m-d_H-i-s').'.xlsx';
        $tempPath = storage_path('app/temp/'.$filename);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        // Prepare data for export
        $exportData = [];
        foreach ($results as $result) {
            if ($result['found']) {
                $exportData[] = [
                    'Input Identifier' => $result['input'],
                    'SUSDAT ID' => 'NS'.$result['susdat_id'],
                    'Substance Name' => $result['substance_name'] ?? '-',
                    'CAS No.' => $result['cas_no'] ?? '-',
                    'StdInChIKey' => $result['std_inchikey'] ?? '-',
                ];
            }
        }

        // Create XLSX file
        $writer = SimpleExcelWriter::create($tempPath);
        $writer->addRows($exportData);
        $writer->close();

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Find substances based on input type and identifier
     */
    private function findSubstance($identifier, $inputType, $exactMatch = false)
    {
        switch ($inputType) {
            case 'cas_no':
                if ($exactMatch) {
                    return Substance::where('cas_number', 'ILIKE', $identifier)->get();
                } else {
                    return Substance::where('cas_number', 'ILIKE', '%'.$identifier.'%')->get();
                }

            case 'substance_name':
                if ($exactMatch) {
                    return Substance::where('name', 'ILIKE', $identifier)
                        ->orWhere('name_dashboard', 'ILIKE', $identifier)
                        ->orWhere('name_chemspider', 'ILIKE', $identifier)
                        ->orWhere('name_iupac', 'ILIKE', $identifier)
                        ->get();
                } else {
                    return Substance::where('name', 'ILIKE', '%'.$identifier.'%')
                        ->orWhere('name_dashboard', 'ILIKE', '%'.$identifier.'%')
                        ->orWhere('name_chemspider', 'ILIKE', '%'.$identifier.'%')
                        ->orWhere('name_iupac', 'ILIKE', '%'.$identifier.'%')
                        ->get();
                }

            case 'std_inchikey':
                if ($exactMatch) {
                    return Substance::where('stdinchikey', 'ILIKE', $identifier)->get();
                } else {
                    return Substance::where('stdinchikey', 'ILIKE', '%'.$identifier.'%')->get();
                }

            case 'susdat_id':
                // Remove NS prefix if present and search in code column
                $cleanIdentifier = ltrim($identifier, 'NS');
                if ($exactMatch) {
                    return Substance::where('code', 'ILIKE', $cleanIdentifier)->get();
                } else {
                    return Substance::where('code', 'ILIKE', '%'.$cleanIdentifier.'%')->get();
                }

            default:
                return null;
        }
    }
}

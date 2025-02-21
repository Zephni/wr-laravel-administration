<?php

namespace WebRegulate\LaravelAdministration\Classes;

use \Symfony\Component\HttpFoundation\StreamedResponse;

class CSVHelper
{
    /**
     * Array should bjust be a natural indexed array of data
     *
     * @param string $filename
     * @param array|null $columnHeadings
     * @param array $data
     * @return StreamedResponse
     */
    public static function build(string $filename, ?array $columnHeadings, array $data): StreamedResponse
    {
        // Build headers
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . str_replace(' ', '_', $filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        // Return the CSV
        return response()->stream(function () use ($columnHeadings, $data) {
            $csv = fopen('php://output', 'w');

            // Write the column headings
            if($columnHeadings !== null && count($columnHeadings) > 0) {
                fputcsv($csv, $columnHeadings);
            }

            // Write the data
            foreach ($data as $row) {
                try {
                    fputcsv($csv, (array) $row);
                } catch(\Exception $e) {
                    // Skip this row on failure
                }
            }

            // Close CSV file handle
            fclose($csv);
        }, 200, $headers);
    }
}

<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CSVHelper
{
    /**
     * Export a collection of models as a CSV streamed response.
     *
     * @param  string  $manageableModelClass  The manageable model class.
     * @param  Collection  $models  The current data set to export.
     * @param  ?string  $manageableModelStaticExportMethod  The static export method to use in place of the standard export. Method name must begin with 'export', takes a collection of models and a file name reference, and returns a collection of associative arrays (keys used as headings).
     */
    public static function exportManageableModels(
        string $manageableModelClass,
        Collection $models,
        ?string $manageableModelStaticExportMethod = null
    ): StreamedResponse {
        // File name
        $fileName = $manageableModelClass::getDisplayName(true).' '.date('Y-m-d H:i').'.csv';

        // If a static export method is provided, use that
        if ($manageableModelStaticExportMethod !== null) {
            // If the method does not exist, or does not start with 'export', dd
            if (! str($manageableModelStaticExportMethod)->startsWith('export')) {
                dd("Export method name must begin with 'export', $manageableModelStaticExportMethod provided.");
            }
            if (! method_exists($manageableModelClass, $manageableModelStaticExportMethod)) {
                dd("Export method $manageableModelStaticExportMethod does not exist on {$manageableModelClass}.");
            }

            $models = $manageableModelClass::$manageableModelStaticExportMethod($models, $fileName);

            $headings = array_keys($models->first() ?? []);
        } else {
            // Get all headings (array of all column names)
            $headings = $manageableModelClass::getTableColumns();
        }

        // Sort data
        if (! is_array($models->first()) && isset($models->first()['id'])) {
            $rowData = $models->sortBy('id');
        }

        // Get all values (array of all model values)
        $rowData = $models->values()->toArray();

        // Use build to stream CSV
        return self::build(
            $fileName,
            $headings,
            $rowData
        );
    }

    /**
     * Array should just be a natural indexed array of data
     */
    public static function build(string $filename, ?array $columnHeadings, array $data): StreamedResponse
    {
        // Build headers
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.str_replace(' ', '_', $filename).'"; filename*=UTF-8\'\''.rawurlencode($filename),
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        // Return the CSV
        return response()->stream(function () use ($columnHeadings, $data): void {
            $csv = fopen('php://output', 'w');

            // Write the column headings
            if ($columnHeadings !== null && count($columnHeadings) > 0) {
                fputcsv($csv, $columnHeadings);
            }

            // Write the data
            foreach ($data as $row) {
                try {
                    $row = array_map(function ($value) {
                        if ($value instanceof DateTimeInterface) {
                            return $value->format('Y-m-d H:i:s');
                        }

                        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $value)) {
                            try {
                                return Carbon::parse($value)->format('Y-m-d H:i:s');
                            } catch (Exception) {
                                return $value;
                            }
                        }

                        return is_array($value) ? json_encode($value) : $value;
                    }, (array) $row);
                    fputcsv($csv, $row);
                } catch (Exception) {
                    // Skip this row on failure
                }
            }

            // Close CSV file handle
            fclose($csv);
        }, 200, $headers);
    }

    /**
     * Array from csv file
     */
    public static function arrayFromCSVFile(string $csvFile, bool $hasHeader = true): array
    {
        $rows = [];
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $header = [];
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($hasHeader && empty($header)) {
                    $header = $data;
                    continue;
                }
                if ($hasHeader) {
                    if (count($header) === count($data)) {
                        $rows[] = array_combine($header, $data);
                    }
                } else {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }

        return $rows;
    }
}

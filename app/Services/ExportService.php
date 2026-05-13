<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function exportToCsv(Collection $data, string $filename = 'report'): StreamedResponse
    {
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if ($data->isEmpty()) {
                fclose($file);
                return;
            }

            $headers = array_keys((array) $data->first());
            fputcsv($file, $headers);

            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, "{$filename}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportToJson(Collection $data, string $filename = 'report'): StreamedResponse
    {
        $callback = function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        };

        return response()->streamDownload($callback, "{$filename}.json", [
            'Content-Type' => 'application/json',
        ]);
    }

    public function formatDataForExport(Collection $data, array $columns = null): Collection
    {
        if (!$columns) {
            return $data;
        }

        return $data->map(function ($row) use ($columns) {
            $filtered = [];
            foreach ($columns as $column) {
                $filtered[$column] = $row[$column] ?? null;
            }
            return $filtered;
        });
    }

    public function flattenForExport(Collection $data): Collection
    {
        return $data->map(function ($row) {
            return $this->flattenArray((array) $row, '');
        });
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenArray($value, $newKey));
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $flattened = array_merge($flattened, $this->flattenArray($value->toArray(), $newKey));
            } else {
                $flattened[$newKey] = $value;
            }
        }

        return $flattened;
    }
}

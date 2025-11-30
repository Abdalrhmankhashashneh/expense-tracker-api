<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Export expenses as CSV.
     *
     * @OA\Get(
     *     path="/api/export/csv",
     *     summary="Export expenses as CSV",
     *     description="Export expenses to CSV file with optional filtering",
     *     tags={"Export"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function csv(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $query = $request->user()->expenses()->with('category');

        if ($request->date_from && $request->date_to) {
            $query->inDateRange($request->date_from, $request->date_to);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        // Generate CSV content
        $csvData = [];
        $csvData[] = ['Date', 'Category', 'Amount', 'Note'];

        foreach ($expenses as $expense) {
            $csvData[] = [
                $expense->date,
                $expense->category->name,
                $expense->amount,
                $expense->note ?? '',
            ];
        }

        $filename = 'expenses_' . now()->format('Y-m-d_His') . '.csv';

        // Save export history
        $request->user()->exportHistory()->create([
            'export_format' => 'csv',
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'category_id' => $request->category_id,
            'record_count' => $expenses->count(),
            'file_size' => 0,
        ]);

        // Convert to CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export expenses as PDF (placeholder).
     *
     * @OA\Get(
     *     path="/api/export/pdf",
     *     summary="Export expenses as PDF",
     *     description="Export expenses to PDF file (not yet implemented)",
     *     tags={"Export"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function pdf(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'PDF export will be implemented with barryvdh/laravel-dompdf',
        ], 501);
    }

    /**
     * Export expenses as Excel (placeholder).
     *
     * @OA\Get(
     *     path="/api/export/excel",
     *     summary="Export expenses as Excel",
     *     description="Export expenses to Excel file (not yet implemented)",
     *     tags={"Export"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function excel(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Excel export will be implemented with maatwebsite/excel',
        ], 501);
    }

    /**
     * Get export history.
     *
     * @OA\Get(
     *     path="/api/export/history",
     *     summary="Get export history",
     *     description="Retrieve user's export history with pagination",
     *     tags={"Export"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="export_format", type="string", example="csv"),
     *                 @OA\Property(property="date_from", type="string", format="date"),
     *                 @OA\Property(property="date_to", type="string", format="date"),
     *                 @OA\Property(property="record_count", type="integer", example=50),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function history(Request $request)
    {
        $history = $request->user()
            ->exportHistory()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'total_pages' => $history->lastPage(),
                'total' => $history->total(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }
}

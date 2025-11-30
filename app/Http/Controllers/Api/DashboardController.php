<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview for a specific month.
     *
     * @OA\Get(
     *     path="/api/dashboard/overview",
     *     summary="Get dashboard overview",
     *     description="Get comprehensive dashboard overview including income, expenses, balance, and category breakdown",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         @OA\Schema(type="string", format="Y-m", example="2025-12")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="month", type="string", example="2025-12"),
     *                 @OA\Property(property="monthly_income", type="string", example="5000.00"),
     *                 @OA\Property(property="total_expenses", type="string", example="1500.00"),
     *                 @OA\Property(property="remaining_balance", type="string", example="3500.00"),
     *                 @OA\Property(property="spending_percentage", type="number", example=30),
     *                 @OA\Property(property="top_category", type="object"),
     *                 @OA\Property(property="expense_by_category", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="daily_expenses", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function overview(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $request->get('month', now()->format('Y-m'));
        $user = $request->user();

        // Get expenses for the month
        $expenses = $user->expenses()
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->with('category')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Get current income
        $currentIncome = $user->currentIncome;
        $monthlyIncome = $currentIncome ? $currentIncome->monthly_amount : 0;

        $remainingBalance = $monthlyIncome - $totalExpenses;
        $spendingPercentage = $monthlyIncome > 0 ? ($totalExpenses / $monthlyIncome) * 100 : 0;

        // Group by category
        $expenseByCategory = $expenses->groupBy('category_id')->map(function ($items, $categoryId) use ($totalExpenses) {
            $category = $items->first()->category;
            $categoryTotal = $items->sum('amount');

            return [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'total_amount' => number_format($categoryTotal, 2, '.', ''),
                'percentage' => $totalExpenses > 0 ? round(($categoryTotal / $totalExpenses) * 100, 2) : 0,
                'expense_count' => $items->count(),
            ];
        })->sortByDesc('total_amount')->values();

        // Top category
        $topCategory = $expenseByCategory->first();

        // Daily expenses
        $dailyExpenses = $expenses->groupBy(function ($expense) {
            return $expense->date;
        })->map(function ($items, $date) {
            return [
                'date' => $date,
                'amount' => number_format($items->sum('amount'), 2, '.', ''),
            ];
        })->sortBy('date')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'monthly_income' => number_format($monthlyIncome, 2, '.', ''),
                'total_expenses' => number_format($totalExpenses, 2, '.', ''),
                'remaining_balance' => number_format($remainingBalance, 2, '.', ''),
                'spending_percentage' => round($spendingPercentage, 2),
                'top_category' => $topCategory ? [
                    'category' => $topCategory['category_name'],
                    'amount' => $topCategory['total_amount'],
                    'percentage' => $topCategory['percentage'],
                ] : null,
                'expense_by_category' => $expenseByCategory,
                'daily_expenses' => $dailyExpenses,
            ],
        ]);
    }

    /**
     * Get spending trends.
     *
     * @OA\Get(
     *     path="/api/dashboard/trends",
     *     summary="Get spending trends",
     *     description="Get spending trends over time (monthly, weekly, or yearly)",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         @OA\Schema(type="string", enum={"monthly", "weekly", "yearly"}, default="monthly")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", minimum=1, maximum=12, default=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period", type="string", example="monthly"),
     *                 @OA\Property(property="trends", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="period", type="string", example="2025-12"),
     *                     @OA\Property(property="total_expenses", type="string", example="1500.00"),
     *                     @OA\Property(property="total_income", type="string", example="5000.00"),
     *                     @OA\Property(property="savings", type="string", example="3500.00")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function trends(Request $request)
    {
        $request->validate([
            'period' => ['nullable', 'in:monthly,weekly,yearly'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $period = $request->get('period', 'monthly');
        $limit = $request->get('limit', 6);
        $user = $request->user();

        $trends = [];

        if ($period === 'monthly') {
            for ($i = $limit - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthStr = $date->format('Y-m');

                $expenses = $user->expenses()
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->sum('amount');

                // Get income for that month
                $income = $user->incomes()
                    ->where('effective_from', '<=', $date->endOfMonth())
                    ->latest('effective_from')
                    ->first();

                $monthlyIncome = $income ? $income->monthly_amount : 0;
                $savings = $monthlyIncome - $expenses;

                $trends[] = [
                    'period' => $monthStr,
                    'total_expenses' => number_format($expenses, 2, '.', ''),
                    'total_income' => number_format($monthlyIncome, 2, '.', ''),
                    'savings' => number_format($savings, 2, '.', ''),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'trends' => $trends,
            ],
        ]);
    }

    /**
     * Get category breakdown.
     *
     * @OA\Get(
     *     path="/api/dashboard/category-breakdown",
     *     summary="Get category breakdown",
     *     description="Get expenses breakdown by category for a specific month",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         @OA\Schema(type="string", format="Y-m", example="2025-12")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="name", type="object", example={"en": "Food", "ar": "طعام"}),
     *                     @OA\Property(property="amount", type="string", example="500.00"),
     *                     @OA\Property(property="percentage", type="number", example=33.33),
     *                     @OA\Property(property="color", type="string", example="#907B60")
     *                 )),
     *                 @OA\Property(property="total", type="string", example="1500.00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function categoryBreakdown(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $request->get('month', now()->format('Y-m'));
        $user = $request->user();

        $expenses = $user->expenses()
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->with('category')
            ->get();

        $total = $expenses->sum('amount');

        $categories = $expenses->groupBy('category_id')->map(function ($items) use ($total) {
            $category = $items->first()->category;
            $categoryTotal = $items->sum('amount');

            return [
                'name' => $category->name,
                'amount' => number_format($categoryTotal, 2, '.', ''),
                'percentage' => $total > 0 ? round(($categoryTotal / $total) * 100, 2) : 0,
                'color' => $category->color,
            ];
        })->sortByDesc('amount')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'total' => number_format($total, 2, '.', ''),
            ],
        ]);
    }
}

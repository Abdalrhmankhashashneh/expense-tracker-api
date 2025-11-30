<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Expense Tracker API",
 *     description="RESTful API for managing personal expenses, tracking spending by categories, and monitoring monthly budgets.",
 *     @OA\Contact(
 *         email="support@expensetracker.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://api.expensetracker.com/api",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 *
 * @OA\Tag(
 *     name="Income",
 *     description="API Endpoints for managing monthly income"
 * )
 *
 * @OA\Tag(
 *     name="Expenses",
 *     description="API Endpoints for managing expenses"
 * )
 *
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints for managing expense categories"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for dashboard statistics and analytics"
 * )
 *
 * @OA\Tag(
 *     name="Export",
 *     description="API Endpoints for exporting data in various formats"
 * )
 *
 * @OA\Tag(
 *     name="Settings",
 *     description="API Endpoints for user settings and preferences"
 * )
 */
abstract class Controller
{
    //
}

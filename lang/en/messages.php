<?php

return [
    // Success messages
    'success' => [
        'created' => ':resource created successfully',
        'updated' => ':resource updated successfully',
        'deleted' => ':resource deleted successfully',
        'restored' => ':resource restored successfully',
    ],

    // Error messages
    'error' => [
        'not_found' => ':resource not found',
        'unauthorized' => 'Unauthorized access',
        'validation_failed' => 'Validation failed',
        'server_error' => 'Internal server error',
        'delete_failed' => 'Cannot delete :resource',
    ],

    // Resources
    'resources' => [
        'expense' => 'Expense',
        'income' => 'Income',
        'category' => 'Category',
        'user' => 'User',
        'export' => 'Export',
    ],

    // Validation messages
    'validation' => [
        'amount_positive' => 'Amount must be positive',
        'amount_required' => 'Amount is required',
        'amount_numeric' => 'Amount must be a number',
        'amount_min' => 'Amount must be greater than 0',
        'date_past' => 'Date cannot be in the future',
        'date_required' => 'Date is required',
        'date_invalid' => 'Invalid date format',
        'category_exists' => 'Category does not exist',
        'category_has_expenses' => 'Cannot delete category with existing expenses',
        'category_required' => 'Category is required',
        'category_invalid' => 'Invalid category',
        'income_required' => 'Please set your monthly income first',
        'name_required' => 'Name is required',
        'email_required' => 'Email is required',
        'email_invalid' => 'Invalid email format',
        'email_exists' => 'Email already exists',
        'password_required' => 'Password is required',
        'password_min' => 'Password must be at least 8 characters',
        'password_confirmed' => 'Password confirmation does not match',
        'icon_required' => 'Icon is required',
        'color_required' => 'Color is required',
        'color_invalid' => 'Invalid color format. Use hex format (e.g., #FF5733)',
    ],

    // Authentication
    'auth' => [
        'login_success' => 'Login successful',
        'logout_success' => 'Logout successful',
        'register_success' => 'Registration successful',
        'invalid_credentials' => 'Invalid email or password',
        'invalid_current_password' => 'Current password is incorrect',
        'token_expired' => 'Token has expired',
    ],

    // Dashboard
    'dashboard' => [
        'monthly_income' => 'Monthly Income',
        'total_expenses' => 'Total Expenses',
        'remaining_balance' => 'Remaining Balance',
        'spending_percentage' => 'Spending Percentage',
        'top_category' => 'Top Spending Category',
    ],

    // Export
    'export' => [
        'success' => 'Data exported successfully',
        'no_data' => 'No data available for export',
        'invalid_format' => 'Invalid export format',
    ],

    // Income
    'income' => [
        'created' => 'Monthly income set successfully',
        'updated' => 'Monthly income updated successfully',
        'deleted' => 'Income record deleted successfully',
        'no_income' => 'No income set yet',
    ],

    // Expense
    'expense' => [
        'created' => 'Expense created successfully',
        'updated' => 'Expense updated successfully',
        'deleted' => 'Expense deleted successfully',
    ],

    // Category
    'category' => [
        'created' => 'Category created successfully',
        'updated' => 'Category updated successfully',
        'deleted' => 'Category deleted successfully',
        'cannot_update_default' => 'Cannot update default categories',
        'cannot_delete_default' => 'Cannot delete default categories',
        'has_expenses' => 'Cannot delete category with existing expenses',
    ],

    // Settings
    'settings' => [
        'profile_updated' => 'Profile updated successfully',
        'password_changed' => 'Password changed successfully. Please login again.',
    ],

    // Currency
    'currency' => [
        'updated' => 'Currency preference updated successfully',
        'inactive' => 'Selected currency is not available',
    ],
];

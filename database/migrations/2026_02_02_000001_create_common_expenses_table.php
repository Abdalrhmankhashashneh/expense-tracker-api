<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('category_id');
            $table->index(['user_id', 'category_id']);
        });

        // Add permissions for common expenses
        $permissions = [
            'view common expenses',
            'create common expense',
            'update common expense',
            'delete common expense',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Assign to existing roles
        $userRole = Role::where('name', 'user')->where('guard_name', 'sanctum')->first();
        if ($userRole) {
            $userRole->givePermissionTo($permissions);
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $guestRole = Role::where('name', 'guest')->where('guard_name', 'sanctum')->first();
        if ($guestRole) {
            $guestRole->givePermissionTo(['view common expenses']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('common_expenses');
    }
};

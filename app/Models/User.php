<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="currency_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The guard name for Spatie Permission package.
     */
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'currency_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all income records for the user.
     */
    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Get the user's preferred currency.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the current active income for the user.
     */
    public function currentIncome()
    {
        return $this->hasOne(Income::class)
                    ->where('effective_from', '<=', now())
                    ->latest('effective_from');
    }

    /**
     * Get all expenses for the user.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get custom categories created by the user.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get export history for the user.
     */
    public function exportHistory()
    {
        return $this->hasMany(ExportHistory::class);
    }

    /**
     * Get the user's balance.
     */
    public function balance()
    {
        return $this->hasOne(Balance::class);
    }

    /**
     * Get the user's balance transactions.
     */
    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    /**
     * Get all debts for the user.
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get all debt payments for the user.
     */
    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Get or create the user's balance.
     */
    public function getOrCreateBalance(): Balance
    {
        return $this->balance ?? Balance::create([
            'user_id' => $this->id,
            'current_balance' => 0,
        ]);
    }

    /**
     * Get all categories accessible by the user (default + custom).
     */
    public function allAccessibleCategories()
    {
        return Category::where('is_default', true)
                       ->orWhere('user_id', $this->id)
                       ->get();
    }
}

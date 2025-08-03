<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'locked'
    ];

    protected $casts = [
        'balance' => 'float',
        'locked' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id')
            ->where('currency', $this->currency);
    }

    // Get available balance (total minus locked)
    public function getAvailableAttribute()
    {
        return $this->balance - $this->locked;
    }

    /**
     * Credit wallet (increase balance)
     * 
     * @param float $amount Amount to add
     * @param bool $lock Whether to lock the funds
     * @return bool Success
     */
    public function credit($amount, $lock = false)
    {
        if ($amount <= 0) {
            return false;
        }

        $this->balance += $amount;

        if ($lock) {
            $this->locked += $amount;
        }

        return $this->save();
    }

    /**
     * Debit wallet (decrease balance)
     * 
     * @param float $amount Amount to subtract
     * @param bool $fromLocked Whether to also unlock funds
     * @return bool Success
     */
    public function debit($amount, $fromLocked = false)
    {
        if ($amount <= 0) {
            return false;
        }

        // Check if enough available balance
        if ($this->balance < $amount) {
            return false;
        }

        $this->balance -= $amount;

        if ($fromLocked) {
            $this->locked = max(0, $this->locked - $amount);
        }

        return $this->save();
    }

    /**
     * Lock funds (for pending orders/trades)
     * 
     * @param float $amount Amount to lock
     * @return bool Success
     */
    public function lockFunds($amount)
    {
        if ($amount <= 0) {
            return false;
        }

        // Check if enough available balance
        if ($this->getAvailableAttribute() < $amount) {
            return false;
        }

        $this->locked += $amount;
        return $this->save();
    }

    /**
     * Unlock funds (when orders are cancelled)
     * 
     * @param float $amount Amount to unlock
     * @return bool Success
     */
    public function unlockFunds($amount)
    {
        if ($amount <= 0) {
            return false;
        }

        $this->locked = max(0, $this->locked - $amount);
        return $this->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'status',
        'details',
        'reference'
    ];

    protected $casts = [
        'amount' => 'float',
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new transaction and update wallet balance
     *
     * @param int $userId User ID
     * @param string $type Transaction type (deposit, withdrawal, trade)
     * @param float $amount Amount
     * @param string $currency Currency code
     * @param string $status Status (pending, completed, failed)
     * @param array $details Additional details
     * @param string|null $reference External reference
     * @return self
     */
    public static function createTransaction($userId, $type, $amount, $currency, $status, $details = [], $reference = null)
    {
        $transaction = self::create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'details' => $details,
            'reference' => $reference,
        ]);

        // Only update wallet for completed transactions
        if ($status === 'completed') {
            // Get or create wallet
            $wallet = UserWallet::firstOrCreate(
                ['user_id' => $userId, 'currency' => $currency],
                ['balance' => 0, 'locked' => 0]
            );

            // Update balance based on transaction type
            if (in_array($type, ['deposit', 'credit', 'trade_profit'])) {
                $wallet->credit($amount);
            } elseif (in_array($type, ['withdrawal', 'debit', 'trade_loss'])) {
                $wallet->debit($amount);
            }
        }

        return $transaction;
    }

    /**
     * Complete a pending transaction
     *
     * @param string $status New status (completed, failed, cancelled)
     * @param array $details Additional details to update
     * @return bool
     */
    public function complete($status, $details = [])
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = $status;

        if (!empty($details)) {
            $this->details = array_merge($this->details ?? [], $details);
        }

        $updated = $this->save();

        // If completing successfully, update wallet balance
        if ($updated && $status === 'completed') {
            $wallet = UserWallet::firstOrCreate(
                ['user_id' => $this->user_id, 'currency' => $this->currency],
                ['balance' => 0, 'locked' => 0]
            );

            if (in_array($this->type, ['deposit', 'credit', 'trade_profit'])) {
                $wallet->credit($this->amount);
            } elseif (in_array($this->type, ['withdrawal', 'debit', 'trade_loss'])) {
                $wallet->debit($this->amount);
            }
        }

        return $updated;
    }
}

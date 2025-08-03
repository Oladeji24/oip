<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveBot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'market',
        'symbol',
        'status',
        'started_at',
        'stopped_at',
        'last_trade'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_trade' => 'array',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get active bots
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Get bot trades
    public function trades()
    {
        return $this->hasMany(BotTrade::class, 'user_id', 'user_id')
            ->where('market', $this->market)
            ->where('symbol', $this->symbol);
    }

    // Get open bot trade
    public function openTrade()
    {
        return $this->hasOne(BotTrade::class, 'user_id', 'user_id')
            ->where('market', $this->market)
            ->where('symbol', $this->symbol)
            ->whereNull('closed_at');
    }

    // Check if bot has open position
    public function hasOpenPosition()
    {
        return $this->openTrade()->exists();
    }

    // Update last trade information
    public function updateLastTrade($tradeData)
    {
        $this->last_trade = $tradeData;
        return $this->save();
    }

    // Stop the bot
    public function stopBot()
    {
        $this->status = 'stopped';
        $this->stopped_at = now();
        return $this->save();
    }

    // Pause the bot (temporarily)
    public function pauseBot()
    {
        $this->status = 'paused';
        return $this->save();
    }

    // Resume the bot
    public function resumeBot()
    {
        $this->status = 'active';
        $this->stopped_at = null;
        return $this->save();
    }
}

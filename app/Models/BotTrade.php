<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'market',
        'symbol',
        'side',
        'entry',
        'exit',
        'size',
        'profit',
        'opened_at',
        'closed_at',
        'entry_order',
        'exit_order'
    ];

    protected $casts = [
        'entry' => 'float',
        'exit' => 'float',
        'size' => 'float',
        'profit' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'entry_order' => 'array',
        'exit_order' => 'array',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes for common queries
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }

    public function scopeProfitable($query)
    {
        return $query->where('profit', '>', 0);
    }

    public function scopeUnprofitable($query)
    {
        return $query->where('profit', '<', 0);
    }

    // Calculate holding time in seconds
    public function getHoldingTimeAttribute()
    {
        if (!$this->closed_at) {
            return null;
        }
        return $this->closed_at->diffInSeconds($this->opened_at);
    }
}

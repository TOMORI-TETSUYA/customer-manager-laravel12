<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag');
    }
}

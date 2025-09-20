<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Profile extends Model
{
    use Searchable, HasFactory;

    protected $fillable = [
        'username', 'name', 'bio'
    ];

    protected $casts = [
        'metadata' => 'array',
        'sources' => 'array',
        'likes' => 'integer',
    ];

    // Scout searchable array
    public function toSearchableArray(): array
    {
        return [
            'username' => $this->username,
            'name' => $this->name,
            'bio' => $this->bio,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Profile extends Model
{
    use Searchable;

    protected $fillable = [
        'username', 'name', 'bio', 'metadata', 'sources', 'likes'
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

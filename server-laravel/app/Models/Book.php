<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $table = 'books';

    // La tabla sólo tiene created_at (no updated_at)
    const UPDATED_AT = null;

    protected $fillable = [
        'glpi_id',
        'title',
        'author',
        'isbn',
        'edition',
        'genre',
        'publisher',
        'genre_id',
        'publisher_id',
        'status',
        'synopsis',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class, 'book_id');
    }

    public function latestReport(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Report::class, 'book_id')->latestOfMany();
    }
}

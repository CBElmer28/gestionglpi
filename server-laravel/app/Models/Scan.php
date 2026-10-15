<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $table = 'scans';

    protected $fillable = [
        'isbn', 'book_id', 'user_id', 'title', 'author',
        'publisher', 'cover_url', 'result', 'source',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}
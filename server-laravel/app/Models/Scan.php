<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $table = 'scans';

    protected $fillable = [
        'isbn', 'book_id', 'user_id', 'title', 'author',
        'publisher', 'cover_url', 'photo_path', 'result', 'source', 'device_info',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}
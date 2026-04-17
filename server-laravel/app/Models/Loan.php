<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loans';

    // La tabla loans no tiene timestamps (sólo loan_date y return_date manejados manualmente)
    public $timestamps = false;

    protected $fillable = [
        'book_id',
        'user_id',
        'user_name',
        'loan_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'loan_date'   => 'date',
        'return_date' => 'date',
    ];

    public function book(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

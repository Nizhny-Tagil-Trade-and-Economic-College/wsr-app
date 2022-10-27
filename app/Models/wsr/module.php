<?php

namespace App\Models\wsr;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class module extends Model
{
    use AsSource;

    protected $table = 'modules';

    protected $fillable = [
        'name',
        'is_active',
        'counter'
    ];
}

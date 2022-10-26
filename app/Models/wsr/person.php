<?php

namespace App\Models\wsr;

use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class person extends Model
{
    use AsSource, Filterable;

    protected $table = 'persons';

    protected $fillable = [
        'lastname',
        'firstname',
        'patronymic',
        'home',
        'login',
        'raw_password'
    ];

    protected $allowedSorts = [
        'lastname',
        'firstname',
        'patronymic'
    ];

    public function getFullnameAttribute() {
        return "{$this -> lastname} {$this -> firstname} {$this -> patronymic}";
    }
}

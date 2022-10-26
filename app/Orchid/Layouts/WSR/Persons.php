<?php

namespace App\Orchid\Layouts\WSR;

use App\Models\wsr\person;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class Persons extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'persons';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('fullname', 'ФИО'),
            TD::make('login', 'Логин'),
            TD::make('raw_password', 'Пароль'),
            TD::make('actions', 'Действия')
                -> render(function(person $person) {
                    return Button::make('Удалить')
                        -> method('deletePerson', [$person -> id])
                        -> class('btn btn-link btn-danger');
                }),
        ];
    }
}

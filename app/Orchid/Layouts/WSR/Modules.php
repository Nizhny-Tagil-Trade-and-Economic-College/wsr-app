<?php

namespace App\Orchid\Layouts\WSR;

use App\Models\wsr\module;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class Modules extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'modules';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('name', 'Имя модуля'),
            TD::make('counter', 'Номер модуля'),
            TD::make('is_active', 'Активный модуль')
                -> render(function(module $module) {
                    return CheckBox::make("mod[{$module -> id}]")
                        -> value($module -> is_active)
                        -> sendTrueOrFalse();
                }),
            TD::make('actions', 'Действия')
                -> render(function(module $module) {
                    return ModalToggle::make('Удалить')
                        -> modal('confirmDeleteModal')
                        -> method('deleteModule', [$module -> id])
                        -> class('btn btn-link btn-danger');
                }),
        ];
    }
}

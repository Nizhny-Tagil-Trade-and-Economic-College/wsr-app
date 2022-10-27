<?php

namespace App\Orchid\Screens\WSR;

use App\Models\wsr\module;
use App\Models\wsr\person;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class Check extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(person $persons, module $modules): iterable
    {
        return [
            'persons' => $persons,
            'modules' => $modules,
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Проверка модулей';
    }

    public function description(): ?string
    {
        return 'На данной странице вы можете проверить модули, которые сделали участники. Если нужно проверить модуль с доступом в БД и записью файлов, то нужно включить проверяемый модуль (в данном случае, проверяйте вне конкурсного времени).';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('wsr.check.cards'),
        ];
    }
}

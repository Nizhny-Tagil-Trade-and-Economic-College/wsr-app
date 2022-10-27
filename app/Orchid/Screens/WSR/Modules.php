<?php

namespace App\Orchid\Screens\WSR;

use App\Models\wsr\module;
use App\Orchid\Layouts\WSR\Modules as WSRModules;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class Modules extends Screen
{
    public $modules;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'modules' => module::paginate(),
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Модули WSR';
    }

    public function description(): ?string
    {
        return 'Модули WSR для участников. При создании и редактировани модулей меняется все остальное, так что осторожнее!';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить модуль')
                -> icon('note')
                -> modal('newModuleModal')
                -> method('newModule'),
            Button::make('Сохранить состояния')
                -> icon('save')
                -> method('save'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::modal('confirmDeleteModal', [
                Layout::view('wsr.modals.deleteModule'),
            ])
                -> title('Подтвердите действие')
                -> applyButton('Да')
                -> closeButton('Нет'),
            Layout::modal('newModuleModal', [
                Layout::rows([
                    Input::make('module.name')
                        -> title('Имя модуля')
                        -> required()
                ]),
            ])
                -> title('Добавить новый модуль')
                -> applyButton('Создать')
                -> withoutCloseButton(),
            WSRModules::class
        ];
    }

    public function save(Request $request, module $module) {
        foreach ($request -> get('mod') as $key => $value) {
            $module::where('id', $key)
                -> update(['is_active' => boolval($value)]);
        }

        Toast::success('Статусы модулей успешно изменены!');
    }

    public function deleteModule($module, module $mod) {

        $mod::find($module)
            -> delete();
        Toast::success('Выбранный модуль успешно удален!');
    }

    public function newModule(Request $request, module $module) {
        $count = $module::max('counter');
        if (is_null($count))
            $count = 1;
        else
            $count++;
        $module -> fill(array_merge($request -> get('module'), ['counter' => $count]))
            -> save();

        Toast::success('Модуль успешно добавлен!');
    }
}

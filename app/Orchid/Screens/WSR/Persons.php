<?php

namespace App\Orchid\Screens\WSR;

use App\Models\wsr\person;
use App\Orchid\Layouts\WSR\Persons as WSRPersons;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Str;

class Persons extends Screen
{

    public $persons;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'persons' => person::filters()
                -> defaultSort('id')
                -> paginate(),
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Участники WSR';
    }

    public function description(): ?string
    {
        return 'Список всех участников WSR.';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить участника')
                -> icon('user-follow')
                -> modal('addPerson')
                -> method('addPersonAction'),
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
            Layout::modal('addPerson', [
                Layout::rows([
                    Input::make('person.lastname')
                        -> title('Фамилия')
                        -> required(),
                    Input::make('person.firstname')
                        -> title('Имя')
                        -> required(),
                    Input::make('person.patronymic')
                        -> title('Отчество'),
                ]),
            ])
                -> title('Добавить участника')
                -> applyButton('Добавить')
                -> withoutCloseButton(),
            WSRPersons::class
        ];
    }

    public function addPersonAction(Request $request, person $person) {
        $user = $request -> get('person');
        $user['login'] = strtolower(Str::random(10));
        $user['raw_password'] = strtolower(Str::random(6));
        $user['home'] = "/applications/workdir/{$user['login']}";

        $person -> fill($user)
            -> save();

        Toast::success('Пользователь успешно добавлен!');
    }

    public function deletePerson($id, person $person) {
        $person -> where('id', $id)
            -> delete();

        Toast::warning('Участник удален!');
    }
}

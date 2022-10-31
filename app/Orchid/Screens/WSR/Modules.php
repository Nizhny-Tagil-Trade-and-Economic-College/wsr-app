<?php

namespace App\Orchid\Screens\WSR;

use App\Models\wsr\module;
use App\Models\wsr\person;
use App\Orchid\Layouts\WSR\Modules as WSRModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        foreach ($module::all() as $m) {
            
        }

        Toast::success('Статусы модулей успешно изменены!');
    }

    public function deleteModule($id, module $modules, person $persons) {
        $m = $modules::find($id);

        foreach ($persons::all() as $person) {
            DB::statement("DROP DATABASE {$person -> login}_m{$m -> counter}");
            exec("/usr/bin/sudo /usr/bin/rm -rf {$person -> home}/m{$m -> counter} /etc/apache2/sites-enabled/{$person -> login}-m{$m -> counter}.conf");
        }
        exec('/usr/bin/sudo /usr/sbin/service apache2 reload');
        
        $modules::find($id)
            -> delete();

        $host_ip = getenv('HOST_IP');
        $bind_conf = "\\\$TTL  604800\\n@ IN  SOA ns.wsr.ru. admin.wsr.ru. (\\n3         ; Serial\\n604800    ; Refresh\\n86400     ; Retry\\n2419200   ; Expire\\n604800 )  ; Negative Cache TTL\\n; DNS Servers\\n@  IN  NS  ns.wsr.ru.\\n@  IN  A   {$host_ip}\\nns IN  A   {$host_ip}\\npma IN  A   {$host_ip}\\n; A list for users\\n";
        foreach($person -> all() as $p)
            foreach($modules -> all() as $m)
                $bind_conf .= "{$p -> login}-m{$m -> counter} IN A {$host_ip}\\n";
        exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"{$bind_conf}\" > /server/config/bind/db.wsr.ru'");

        Toast::success('Выбранный модуль успешно удален!');
    }

    public function newModule(Request $request, module $module, person $persons) {
        $count = $module::max('counter');
        if (is_null($count))
            $count = 1;
        else
            $count++;
        $module -> fill(array_merge($request -> get('module'), ['counter' => $count]))
            -> save();

        foreach ($persons::all() as $person) {
            DB::statement("CREATE DATABASE {$person -> login}_m{$count};");
            DB::statement("GRANT ALL PRIVILEGES ON {$person -> login}_m{$count}.* TO '{$person -> login}'@'%';");
            exec("/usr/bin/sudo /usr/bin/mkdir {$person -> home}/m{$count};");
            exec("/usr/bin/sudo /usr/bin/chown -R {$person -> login}:{$person -> login} {$person -> home}; /usr/bin/sudo /usr/bin/chmod -R 777 {$person -> home}");
            exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"<VirtualHost *:80>\n\tServerName {$person -> login}-m{$count}.wsr.ru\n\tDocumentRoot {$person -> home}/m{$count}\n\t# AssignUserId {$person -> login} www-data\n\t<Directory {$person -> home}/m{$count}>\n\t\tOptions +Indexes +FollowSymLinks +MultiViews +ExecCGI\n\t\tOrder allow,deny\n\t\tAllow from all\n\t\tRequire all granted\n\t\tAllowOverride All\n\t</Directory>\n\tAddHandler cgi-script .py\n\tErrorLog {$person -> home}/m{$count}/error.log\n\tCustomLog {$person -> home}/m{$count}/access.log combined\n</VirtualHost>\" > /etc/apache2/sites-enabled/{$person -> login}-m{$count}.conf'");
        }
        DB::statement('FLUSH PRIVILEGES;');
        exec('/usr/bin/sudo /usr/sbin/service apache2 reload');

        $host_ip = getenv('HOST_IP');
        $bind_conf = "\\\$TTL  604800\\n@ IN  SOA ns.wsr.ru. admin.wsr.ru. (\\n3         ; Serial\\n604800    ; Refresh\\n86400     ; Retry\\n2419200   ; Expire\\n604800 )  ; Negative Cache TTL\\n; DNS Servers\\n@  IN  NS  ns.wsr.ru.\\n@  IN  A   {$host_ip}\\nns IN  A   {$host_ip}\\npma IN  A   {$host_ip}\\n; A list for users\\n";
        foreach($person -> all() as $p)
            foreach($module -> all() as $m)
                $bind_conf .= "{$p -> login}-m{$m -> counter} IN A {$host_ip}\\n";
        exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"{$bind_conf}\" > /server/config/bind/db.wsr.ru'");

        Toast::success('Модуль успешно добавлен!');
    }
}

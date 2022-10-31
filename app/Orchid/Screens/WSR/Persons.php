<?php

namespace App\Orchid\Screens\WSR;

use App\Models\wsr\module;
use App\Models\wsr\person;
use App\Orchid\Layouts\WSR\Persons as WSRPersons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Str;
use Orchid\Support\Facades\Alert;

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

    public function addPersonAction(Request $request, person $person, module $modules) {
        $user = $request -> get('person');
        if (count($person::where('lastname', $user['lastname'])
            -> where('firstname', $user['firstname'])
            -> where('patronymic', $user['patronymic'])
            -> get()) == 0)
        {
            $user['login'] = strtolower(Str::random(10));
            $user['raw_password'] = strtolower(Str::random(6));
            $user['home'] = "/server/workdir/{$user['login']}";
            
            $person -> fill($user)
                -> save();
            
            exec("/usr/bin/sudo /usr/sbin/useradd -m -d {$user['home']} -s /bin/bash -U {$user['login']} -p `/usr/bin/openssl passwd {$user['raw_password']}`; /usr/bin/sudo /usr/sbin/usermod -aG {$user['login']}");
            DB::statement("CREATE USER IF NOT EXISTS '{$user['login']}'@'%' IDENTIFIED BY '{$user['raw_password']}';");
            foreach ($modules -> all() as $module) {
                exec("/usr/bin/sudo /usr/bin/mkdir {$user['home']}/m{$module -> counter}; /usr/bin/sudo /usr/bin/chown -R www-data:www-data {$user['home']}/m{$module -> counter}; /usr/bin/sudo /usr/bin/chmod -R 777 {$user['home']}/m{$module -> counter};");
                exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"<VirtualHost *:80>\n\tServerName {$user['login']}-m{$module -> counter}.wsr.ru\n\tDocumentRoot {$user['home']}/m{$module -> counter}\n\t# AssignUserId {$user['login']} www-data\n\t<Directory {$user['home']}/m{$module -> counter}>\n\t\tOptions +Indexes +FollowSymLinks +MultiViews +ExecCGI\n\t\tOrder allow,deny\n\t\tAllow from all\n\t\tRequire all granted\n\t\tAllowOverride All\n\t</Directory>\n\tAddHandler cgi-script .py\n\tErrorLog {$user['home']}/m{$module -> counter}/error.log\n\tCustomLog {$user['home']}/m{$module -> counter}/access.log combined\n</VirtualHost>\" > /etc/apache2/sites-enabled/{$user['login']}-m{$module -> counter}.conf'");
                DB::statement("CREATE DATABASE {$user['login']}_m{$module -> counter};");
                DB::statement("GRANT ALL PRIVILEGES ON {$user['login']}_m{$module -> counter}.* TO '{$user['login']}'@'%';");
            }
            DB::statement('FLUSH PRIVILEGES;');
            exec("/usr/bin/sudo /usr/bin/chown -R {$user['login']}:{$user['login']} {$user['home']}; /usr/bin/sudo /usr/bin/chmod -R 777 {$user['home']}");
            exec("/usr/bin/sudo /bin/bash -c '/usr/bin/ln -s /server/workdir/public {$user['home']}/public';");
            exec('/usr/bin/sudo /usr/sbin/service apache2 reload');

            $host_ip = getenv('HOST_IP');
            $bind_conf = "\\\$TTL  604800\\n@ IN  SOA ns.wsr.ru. admin.wsr.ru. (\\n3         ; Serial\\n604800    ; Refresh\\n86400     ; Retry\\n2419200   ; Expire\\n604800 )  ; Negative Cache TTL\\n; DNS Servers\\n@  IN  NS  ns.wsr.ru.\\n@  IN  A   {$host_ip}\\nns IN  A   {$host_ip}\\npma IN  A   {$host_ip}\\n; A list for users\\n";
            foreach($person -> all() as $p)
                foreach($modules -> all() as $m)
                    $bind_conf .= "{$p -> login}-m{$m -> counter} IN A {$host_ip}\\n";
            exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"{$bind_conf}\" > /server/config/bind/db.wsr.ru'");

            Toast::success('Пользователь успешно добавлен!');
        } else Toast::warning('Вы не можете добавить уже имеющегося участника!');
    }

    public function deletePerson($id, person $person, module $modules) {
        $p = $person::find($id);

        exec("/usr/bin/sudo /usr/sbin/userdel {$p -> login}; /usr/bin/sudo /usr/bin/rm -rf {$p -> home} /etc/apache2/sites-enabled/{$p -> login}*; /usr/bin/sudo /usr/sbin/service apache2 reload");
        foreach ($modules -> all() as $m)
            DB::statement("DROP DATABASE {$p -> login}_m{$m -> counter};");
        DB::statement("DROP USER '{$p -> login}'@'%';");

        $person::where('id', $id)
        -> delete();
        
        $host_ip = getenv('HOST_IP');
        $bind_conf = "\\\$TTL  604800\\n@ IN  SOA ns.wsr.ru. admin.wsr.ru. (\\n3         ; Serial\\n604800    ; Refresh\\n86400     ; Retry\\n2419200   ; Expire\\n604800 )  ; Negative Cache TTL\\n; DNS Servers\\n@  IN  NS  ns.wsr.ru.\\n@  IN  A   {$host_ip}\\nns IN  A   {$host_ip}\\npma IN  A   {$host_ip}\\n; A list for users\\n";
        foreach($person -> all() as $p)
            foreach($modules -> all() as $m) {
                $bind_conf .= "{$p -> login}-m{$m -> counter} IN A {$host_ip}\\n";
            }
        exec("/usr/bin/sudo /bin/bash -c '/usr/bin/echo -e \"{$bind_conf}\" > /server/config/bind/db.wsr.ru'", $o, $r);

        Toast::warning('Участник удален!');
    }
}

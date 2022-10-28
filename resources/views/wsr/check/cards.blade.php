<div class="row">
    @foreach ($persons -> all() as $person)
        <fieldset class="col-lg-4 mb-3" data-async="">
            <div class="col p-0 px-3">
                <legend class="text-black">
                    {{ $person -> fullname }}
                </legend>
            </div>
            <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column">
                <div class="list-group">
                    @foreach ($modules -> all() as $module)
                        <a class="list-group-item list-group-item-action
                            @if ($module -> is_active)
                                list-group-item-info
                            @endif
                        " href="http://{{ $person -> login }}-m{{ $module -> counter }}.wsr.ru" target="_blank">
                            <span>{{ $module -> name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </fieldset>
    @endforeach
</div>
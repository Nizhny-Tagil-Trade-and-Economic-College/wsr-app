<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function(Blueprint $table) {
            $table -> id();
            $table -> char('lastname', 100);
            $table -> char('firstname', 100);
            $table -> char('patronymic', 100)
                -> nullable();
            $table -> char('home', 255)
                -> unique();
            $table -> char('login', 10)
                -> unique();
            $table -> char('raw_password', 6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('persons');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatLongToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->decimal('latitude', 50, 4)->nullable();
            $table->decimal('longitude', 50, 4)->nullable();
            $table->text('image_path')->nullable();
            $table->text('device_token')->nullable();
            $table->text('message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_number');
            $table->dropColumn('phone_verified');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('image_path');
            $table->dropColumn('device_token');
            $table->dropColumn('message');
        });
    }
}

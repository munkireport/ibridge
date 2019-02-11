<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class iBridgeAddVersionColumn extends Migration
{
    private $tableName = 'ibridge';

    public function up()
    {
        $capsule = new Capsule();
        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            $table->string('ibridge_version')->default('');
            $table->index('ibridge_version');
        });
        
        # Force reload local admin data
        $capsule::unprepared("UPDATE hash SET hash = 'x' WHERE name = '$this->tableName'");

    }
    
    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            $table->dropColumn('ibridge_version');
        });
    }
}

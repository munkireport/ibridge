<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class iBridgeMoreData extends Migration
{
    private $tableName = 'ibridge';

    public function up()
    {
        $capsule = new Capsule();
        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            $table->boolean('apple_internal')->nullable();
            $table->string('hardware_model',128)->nullable();
            $table->string('region_info',128)->nullable();
            $table->string('os_version',128)->nullable();
            $table->string('board_id',128)->nullable();
            $table->string('model_number',128)->nullable();
            
            $table->index('apple_internal');
            $table->index('hardware_model');
            $table->index('region_info');
            $table->index('os_version');
            $table->index('board_id');
            $table->index('model_number');
        });
        
        # Force reload ibride data
        $capsule::unprepared("UPDATE hash SET hash = 'x' WHERE name = '$this->tableName'");
    }
    
    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->table($this->tableName, function (Blueprint $table) {
            $table->dropColumn('apple_internal');
            $table->dropColumn('hardware_model');
            $table->dropColumn('region_info');
            $table->dropColumn('os_version');
            $table->dropColumn('board_id');
            $table->dropColumn('model_number');
        });
    }
}

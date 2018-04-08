<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAreaCodesTable extends Migration{
	protected $connection = 'jn_core';
	protected $table = "area_codes";
	
	protected function schema(){
		return Schema::connection($this->connection);
	}
	
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		if (!$this->schema()->hasTable($this->table)) {
			$this->schema()->create($this->table, function (Blueprint $table) {
				$table->string('code', 10);
				$table->string('name');
				$table->string('parent_code', 10);
				$table->timestamps();
				$table->primary(['code']);
			});
		}
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		//don't drop this table. too much data
        //$this->schema()->dropIfExists($this->table);
    }
}
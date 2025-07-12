<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    public function up()
    {
        $driver = Config::get('database.default');
        
        if ($driver === 'mysql') {
            DB::unprepared("
                DROP PROCEDURE IF EXISTS check_and_update_token_expiration;

                CREATE PROCEDURE check_and_update_token_expiration()
                BEGIN
                    DECLARE current_unix_timestamp INT;
                    SET current_unix_timestamp = UNIX_TIMESTAMP();

                    UPDATE apy_credentials
                    SET istoken = false
                    WHERE expires_on IS NOT NULL
                    AND expires_on < current_unix_timestamp;
                END
            ");
        }
    }

    public function down()
    {
        $driver = Config::get('database.default');
        
        if ($driver === 'mysql') {
            DB::unprepared('DROP PROCEDURE IF EXISTS check_and_update_token_expiration');
        }
    }
};
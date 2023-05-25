<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        /*
         * ID Foreign Key Helper.
         */
        Blueprint::macro('foreignKeyColumn', function (string $column, string $referencesTable, string $referencedColumn = 'id', bool $nullable = false) {
            $this->unsignedInteger($column)->nullable($nullable);
            $this->foreign($column)->references($referencedColumn)->on($referencesTable);
        });

        /*
         * ID Nullable Foreign Key Helper.
         */
        Blueprint::macro('nullableForeignKeyColumn', function (string $column, string $referencesTable, string $referencedColumn = 'id') {
            $this->foreignKeyColumn($column, $referencesTable, $referencedColumn, true);
        });

        /*
         * UUID Foreign Key Helper.
         */
        Blueprint::macro('foreignUuidKeyColumn', function (string $column, string $referencesTable, string $referencedColumn = 'id', bool $nullable = false) {
            $this->uuid($column)->nullable($nullable);
            $this->foreign($column)->references($referencedColumn)->on($referencesTable);
        });

        /*
         * UUID Nullable Foreign Key Helper.
         */
        Blueprint::macro('nullableForeignUuidKeyColumn', function (string $column, string $referencesTable, string $referencedColumn = 'id') {
            $this->foreignUuidKeyColumn($column, $referencesTable, $referencedColumn, true);
        });
    }

    /**
     * Register services.
     */
    public function register()
    {
        //
    }
}

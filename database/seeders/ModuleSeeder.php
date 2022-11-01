<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctypeSeeder extends Seeder
{
    protected $module_field = [
        [
            'parent'        => 'Module',
            'label'         => 'Namespace',
            'field_name'    => 'namespace',
            'type'          => 'Data'
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table(config('erp.table.module'))->insert([
            [
                'name'          => 'Core',
                'namespace'     => 'Erp\Http\Core',
                "created_at"    => Carbon::now(),
                "updated_at"    => Carbon::now()
            ]
        ]);
    }
}

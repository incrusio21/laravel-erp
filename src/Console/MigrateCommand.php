<?php

namespace Erp\Console;

use Erp\ErpForm;
use Erp\Models\DocType;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Termwind\terminal;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate ERP App to Database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $doctype = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $foreign = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $removeForeign = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Preparing ERP App Migrate.');

        // ambil daftar document erp pada file form.json
        ErpForm::doctpye_form(function ($docType, $form) {
            // baca meta modul
            $cont   = json_decode(\File::get($form));
            // simpan daftar document dengan tipe DocType
            if($cont->type == 'DocType'){
                array_push($this->doctype, $cont);
            }

            $this->components->TwoColumnDetail($docType, '<fg=yellow;options=bold>RUNNING</>');
            $this->components->task($docType, function () use($cont) {
                if (!Schema::hasTable('tab_'.$cont->name)) {
                    // jika table tidak ada
                    Schema::create('tab_'.$cont->name, function (Blueprint $table) use($cont) {
                        // tambah column
                        $table->string('name')->primary();
                        $this->addColumn($table, $cont);

                        // cek jika merupakan child table
                        if (property_exists($cont, 'is_child') && $cont->is_child == 1){
                            $table->string('parent_name');
                            $table->string('parent_doctype');
                            if(property_exists($cont, 'parent_name') && $cont->parent_name){
                                $this->addForeign($cont->name, ['foreign' => 'parent_name', 'reference' => 'name', 'parent' => $cont->parent_name]);
                            }
                        }
                        $table->timestamps();
                    });
                }else{
                    // jika table ada
                    Schema::table('tab_'.$cont->name, function (Blueprint $table) use($cont){
                        // tambah column
                        $this->addColumn($table, $cont);

                        // cek jika merupakan child table dan bisa memiliki lbih dari satu parent
                        if (property_exists($cont, 'is_child') && $cont->is_child == 1 ){
                            $foreignKeys = $this->listTableForeignKeys('tab_'.$cont->name);
                            $is_exsist = in_array('tab_'.strtolower($cont->name).'_parent_name_foreign', $foreignKeys);

                            if(property_exists($cont, 'parent_name') && $cont->parent_name && !$is_exsist){
                                $this->addForeign($cont->name, ['foreign' => 'parent_name', 'reference' => 'name', 'parent' => $cont->parent_name]);
                            }

                            if(!property_exists($cont, 'parent_name') && $is_exsist){
                                $this->removeForeign($cont->name, ['foreign' => 'tab_'.strtolower($cont->name).'_parent_name_foreign']);
                            }
                        }
                    });
                }

                // print_r($cont);
                // \File::put($form, json_encode($cont, JSON_PRETTY_PRINT));
            });   
        });

        $this->newLine();

        // update table foreign key pada table 
        if(!empty($this->foreign) || !empty($this->removeForeign)){
            $this->components->info('Update Table Foreign Key.');
            // foreign key baru
            foreach ($this->foreign as $doctype => $foreign) {
                $this->components->TwoColumnDetail($doctype, '<fg=blue;options=bold>ADD FOREIGN</>');
                $this->components->task($doctype, function () use($doctype, $foreign) {
                    Schema::table('tab_'.$doctype, function (Blueprint $table) use($foreign){
                        foreach ($foreign as $value) {
                            $table->foreign($value['foreign'])->references($value['reference'])->on('tab_'.$value['parent']);
                        }
                    });
                });
            }

            // foreign key di hapus
            foreach ($this->removeForeign as $doctype => $foreign) {
                $this->components->TwoColumnDetail($doctype, '<fg=red;options=bold>REMOVING FOREIGN</>');
                $this->components->task($doctype, function () use($doctype, $foreign) {
                    Schema::table('tab_'.$doctype, function (Blueprint $table) use($foreign){
                        foreach ($foreign as $value) {
                            $table->dropForeign($value['foreign']);
                            $table->dropIndex($value['foreign']);
                        }
                    });
                });
            }

            $this->newLine();
        }

        // update doctype terdaftar pada table doctype
        if(!empty($this->doctype)){
            $this->components->info('Update Registered DocType.');
            foreach ($this->doctype as $doc) {
                $this->components->TwoColumnDetail($doc->name, '<fg=blue;options=bold>CHECKING IN DB</>');
                $this->components->task($doc->name, function () use($doc) {
                    DocType::updateOrCreate(
                        ['name' => $doc->name],
                        ['module' => $doc->module]
                    );
                });
            }

            $this->newLine();
        }

        // $this->components->info("Server running on [http://{$this->host()}:{$this->port()}].");
        // $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');
    }

    /**
     * Tambah List Foreign Key yang ingin di tambah
     * 
     * @param string $table
     * @param object $value
     */
    protected function addForeign($table, $value)
    {
        if(!in_array($table, $this->foreign)) $this->foreign += [$table => []];

        array_push($this->foreign[$table], $value);
    }

    /**
     * Tambah List Foreign Key yang ingin di hapus
     * 
     * @param string $table
     * @param object $value
     */
    protected function removeForeign($table, $value)
    {
        if(!in_array($table, $this->removeForeign)) $this->removeForeign += [$table => []];

        array_push($this->removeForeign[$table], $value);
    }

    /**
     * Tambah kolom pada table
     * 
     * @param \Illuminate\Database\Schema\Blueprint $table
     * @param object $cont
     */
    protected function addColumn($table, $cont)
    {
        foreach ($cont->fields as $value) {
            if (!Schema::hasColumn('tab_'.$cont->name, $value->fieldname)) {
                if(in_array($value->fieldtype, ['Data', 'Select'])){
                    $table->string($value->fieldname);
                }
                if(in_array($value->fieldtype, ['Link'])){
                    $table->string($value->fieldname);
                    
                    $this->addForeign($cont->name, ['foreign' => $value->fieldname, 'reference' => 'name', 'parent' => $value->options]);
                }
            }
        }
    }

    public function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));
    }
}

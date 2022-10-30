<?php

namespace Erp\Commands;

use Erp\ErpForm;
use Illuminate\Console\Command;
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Preparing ERP App Migrate.');

        ErpForm::doctpye_form(function ($docType, $form) {
            // baca meta modul
            $this->components->TwoColumnDetail($docType, '<fg=yellow;options=bold>RUNNING</>');
            $this->components->task($docType, function () use($form) {
                $cont   = json_decode(\File::get($form));
                \File::put($form, json_encode($cont, JSON_PRETTY_PRINT));
            });   
        });

        $this->newLine();

        // $this->components->info("Server running on [http://{$this->host()}:{$this->port()}].");
        // $this->comment('  <fg=yellow;options=bold>Press Ctrl+C to stop the server</>');
    }
}

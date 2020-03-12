<?php

namespace Riclep\ServerpilotDeployer\Commands;

use Illuminate\Console\Command;
use Riclep\ServerpilotDeployer\CreateHosting;

class BwiHosting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bwi:hosting {server_name} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets up ServerPilot hosting';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $createHosting = new CreateHosting($this->argument('server_name'), $this->argument('domain'));
        $createHosting->setupApp();
    }
}

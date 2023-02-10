<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Cache;
use App\Services\TelegramBotService;
use App\Jobs\TelegramBotGetUpdate;
class TelegramBot extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TelegramBot:getUpDates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TelegramBot getUpDates';

    private $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new TelegramBotService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $bots = $this->service->bots();
        foreach ($bots as $bot) {
            $bot['startAt'] = date('Y-m-d H:i:s');
            $bot['done'] = 0;
            Cache::forget('worker:'.$bot['username']);
            dispatch(new TelegramBotGetUpdate($bot));
        }
    }
}

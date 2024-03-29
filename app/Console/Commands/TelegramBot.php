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
        $run = true;
        while ($run === true) {
            $bots = $this->service->bots();
            foreach ($bots as $bot) {
                $worker = Cache::get('worker:'.$bot['username']);
                if (empty($worker)) {
                    $bot['start_at'] = date('Y-m-d H:i:s');
                    dispatch(new TelegramBotGetUpdate($bot));
                    $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ADD bot : ' . var_export($bot, true), true);
                }
            }
            sleep(300);
        }
    }
}

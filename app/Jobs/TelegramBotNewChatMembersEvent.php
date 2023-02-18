<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TelegramBotService;
class TelegramBotNewChatMembersEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $member = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($new_chat_member) {
        $this->service = new TelegramBotService;
        if ( ! empty($new_chat_member)) {
            $this->member = $new_chat_member;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->service->logInfo(__METHOD__, 'LINE : '.__LINE__.' '.$this->service->logHead.' member '.var_export($this->member, true));
        if ( ! empty($this->member['bot_name'])) {
            $this->service->getToken($this->member['bot_name']);
        }
        $this->service->newChatMembersEvent($this->member);
    }
}

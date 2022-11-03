<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\TelegramBotService;

class TelegramBotCallbackQueryEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $callback = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($callback)
    {
        $this->service = new TelegramBotService;
        if ( ! empty($callback)) {
            $this->callback = $callback;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->callback)
            || empty($this->callback['chat_id'])
            || empty($this->callback['member_id'])
            || empty($this->callback['message_id'])
            || empty($this->callback['text'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR callback : ' . json_encode($this->callback), true);
            return;
        }

        $this->service->logInfo(__METHOD__, 'callback : ' . json_encode($this->callback), true);

        $BotName = 'CharlieLiu_bot';
        $this->service->getToken($BotName);

        if ($this->callback['text'] === 'unban_me') {
            $member = [
                'id'        => $this->callback['member_id'],
                'chat_id'   => $this->callback['chat_id'],
            ];
            $this->service->restrictChatMember($member, true);

            $this->service->logInfo(__METHOD__, 'member ' . json_encode($member));

            $this->service->deleteMessage([
                'chat_id'   => $this->callback['chat_id'],
                'message_id'=> $this->callback['message_id'],
            ]);
        }

        $this->service->logInfo(__METHOD__, 'END');
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\TelegramBotService;

class TelegramBotNewChatMembersEvent implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $service;
    private $member = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($new_chat_member)
    {
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
    public function handle()
    {
        $this->service->logInfo(__METHOD__, 'START');
        if (empty($this->member) || empty($this->member['id']) || empty($this->member['chat_id'])) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' ERROR member : ' . json_encode($this->member));
            return;
        }

        $this->service->logInfo(__METHOD__, 'member : ' . json_encode($this->member), true);

        $BotName = 'CharlieLiu_bot';
        $this->service->getToken($BotName);

        // set all permissions false
        $this->service->restrictChatMember($this->member, false);

        $text = '';
        if ( ! empty($this->member['first_name'])) {
            $text .= $this->member['first_name'].' ';
        } else if ( ! empty($this->member['username'])) {
            $text .= '@'.$this->member['username'].' ';
        }
        $text .= '歡迎到 本社群，請維持禮貌和群友討論，謝謝！\n';
        $text .= '進到群組請先觀看我們的群組導航，裡面可以解決你大多數的問題\n';
        $text .= '\n\n';
        $text .= '新進來的朋友記得點一下 “👉🏻解禁我👈🏻”\n';
        $text .= '來不及點到的、無法發言的，請退群重加';

        $data = [
            'chat_id' => $this->member['chat_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text'=>'👉🏻解禁我👈🏻','callback_data'=>'unban_me']]],
            ])
        ];
        $sendResult = $this->service->sendMessage($data);
        if ($sendResult->isOk()) {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Message sent succesfully to: ' . $this->member['chat_id']);
        } else {
            $this->service->logInfo(__METHOD__, 'LINE '.__LINE__.' Sorry message not sent to: ' . $this->member['chat_id']);
        }
        $this->service->logInfo(__METHOD__, 'END');
    }
}

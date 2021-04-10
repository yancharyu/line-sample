<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use Exception;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\SignatureValidator;

class LineController extends Controller
{
    protected $channel_secret;
    protected $access_token;

    public function __construct()
    {
        $this->channel_secret = env('LINE_CHANNEL_SECRET');
        $this->access_token = env('LINE_ACCESS_TOKEN');
    }

    public function webhook(Request $request)
    {
        $signature = $request->headers->get(HTTPHeader::LINE_SIGNATURE);
        if (!SignatureValidator::validateSignature($request->getContent(), $this->channel_secret,
            $signature)) {
            // TODO 不正アクセス
            Log::info('不正アクセス');
            abort(404);
        }

        $httpClient = new CurlHTTPClient(($this->access_token));
        $lineBot = new LINEBot($httpClient, ['channelSecret' => $this->channel_secret]);

        try {
            $events = $lineBot->parseEventRequest($request->getContent(), $signature);

            Log::debug($events);
            foreach ($events as $event) {
                if (!$event instanceof TextMessage || !$event instanceof MessageEvent ) {
                    Log::debug('不正なメッセージタイプ');
                    continue;
                }

                $text = $event->getText();
                $reply_token = $event->getReplyToken();

                if($text == 'おはよう') {
                    $reply_message = 'おはよう！今日も1日頑張ろう！';
                } elseif ($text == 'こんにちは') {
                    $reply_message = 'こんにちは！調子はどう？';
                } elseif($text == 'こんばんは') {
                    $reply_message = 'こんばんは！今日もお疲れ様！';
                } else {
                    $reply_message = 'その返信はまだできない！';
                }

                $text_message = new TextMessageBuilder($reply_message);
                $lineBot->replyMessage($reply_token, $text_message);
            }
        } catch (Exception $e) {
            Log::error('error');
            return;
        }
    }
}

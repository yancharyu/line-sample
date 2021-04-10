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
                $replyToken = $event->getReplyToken();

                $textMessage = new TextMessageBuilder($this->getReplyMessage($text));
                $lineBot->replyMessage($replyToken, $textMessage);
            }
        } catch (Exception $e) {
            Log::error('error');
            return;
        }
    }

    /**
     * リプライメッセージの中身お好きなように
     * @param string $text
     * @return string
     */
    public function getReplyMessage(string $text)
    {
        if($text == 'おはよう') {
            $replyMessage = 'おはよう！今日も1日頑張ろう！';
        } elseif ($text == 'こんにちは') {
            $replyMessage = 'こんにちは！調子はどう？';
        } elseif($text == 'こんばんは') {
            $replyMessage = 'こんばんは！今日もお疲れ様！';
        } else {
            $replyMessage = 'その返信はまだできないよー！';
        }

        return $replyMessage;
    }
}

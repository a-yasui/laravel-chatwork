<?php

declare(strict_types=1);

namespace ATYasu\Chatwork;

use ATYasu\Chatwork\Exception\AccessTokenInsufficientScopeException;
use ATYasu\Chatwork\Exception\ChatworkException;
use ATYasu\Chatwork\Exception\ChatworkTokenLimitException;
use ATYasu\Chatwork\Exception\HttpException;
use ATYasu\Chatwork\Exception\InvalidAPITokenException;
use ATYasu\Chatwork\Exception\RoomIdEmptyException;
use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class ChatworkChannel
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    protected function requestPost(string|int $roomId, ChatworkMessage $chatworkMessage)
    {
        return $this->client->post('https://api.chatwork.com/v2/rooms/' . $roomId . '/messages', [
            'headers' => [
                'X-ChatWorkToken' => Config::get('chatwork.token'),
                'accept' => 'application/json',
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'body' => $chatworkMessage->message(),
                'self_unread' => $chatworkMessage->selfUnreadStatus,
            ],
            'http_errors' => false
        ]);
    }

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, "toChatwork") === false) {
            // 何もしない
            return;
        }

        /** @var ChatworkMessage|mixed $chatworkMessage */
        $chatworkMessage = $notification->toChatwork($notifiable);
        if (!($chatworkMessage instanceof ChatworkMessage)) {
            throw new ChatworkException(
                "toChatwork Response is not ChatworkMessage Class.",
                500,
                (new \Exception)->getPrevious()
            );
        }

        $roomId = $notifiable->routeNotificationFor('chatwork');
        if (empty($roomId)) {
            throw new RoomIdEmptyException();
        }

        $retry_counter = 3;

        while (true) {
            $response = $this->requestPost($roomId, $chatworkMessage);
            $status = $response->getStatusCode();
            if (200 <= $status && $status <= 299) {
                break;
            }

            if (401 === $status) {
                throw new InvalidAPITokenException($response);
            }

            if (403 === $status) {
                throw new AccessTokenInsufficientScopeException($response);
            }

            if (429 === $status) {
                throw new ChatworkTokenLimitException($response);
            }

            if (400 === $status || 500 <= $status && $status <= 599) {
                throw new HttpException($response);
            }

            throw new HttpException($response);
        }
    }
}

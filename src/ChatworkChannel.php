<?php
declare(strict_types=1);

namespace ATYasu\Chatwork;

use ATYasu\Chatwork\Exception\ChatworkException;
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

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, "toChatwork") === false) {
            // 何もしない
            return ;
        }

        $chatworkMessage = $notification->toChatwork($notifiable);
        $roomId = $notifiable->routeNotificationFor('chatwork');
        if(empty($roomId)){
            throw new RoomIdEmptyException();
        }

        try{
            $this->client->post('https://api.chatwork.com/v2/rooms/' . $roomId . '/messages', [
                'headers' => [
                    'X-ChatWorkToken' => Config::get('chatwork.token'),
                ],
                'form_params' => [
                    'body' => $chatworkMessage->message(),
                    'self_unread' => $chatworkMessage->selfUnreadStatus,
                ],
            ]);
        } catch (\Throwable $e){
            throw new ChatworkException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
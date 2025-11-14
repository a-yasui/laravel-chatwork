<?php

declare(strict_types=1);

namespace ATYasu\Chatwork\Test;

use ATYasu\Chatwork\Exception\AccessTokenInsufficientScopeException;
use ATYasu\Chatwork\Exception\ChatworkTokenLimitException;
use ATYasu\Chatwork\Exception\HttpException;
use ATYasu\Chatwork\Exception\InvalidAPITokenException;
use ATYasu\Chatwork\Exception\RoomIdEmptyException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use ATYasu\Chatwork\ChatworkChannel;
use ATYasu\Chatwork\ChatworkMessage;
use ATYasu\Chatwork\ChatworkNotification;
use ATYasu\Chatwork\Exception\ChatworkException;

class ChatworkChannelTest extends TestCase
{
    private ChatworkChannel $target;
    private MockInterface $clientMock;

    /**
     * @var object
     */
    private $mockNotifiable;

    /**
     * @var ChatworkNotification
     */
    private $mockNotification;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('chatwork.token', 'test_token');

        $this->clientMock = \Mockery::mock(Client::class)->makePartial();
        $this->target = new ChatworkChannel($this->clientMock);

        $this->mockNotifiable = new class(){
            public function routeNotificationFor($channel)
            {
                return '999999';
            }
        };

        $this->mockNotification = new class() extends ChatworkNotification{
            public function toChatwork($notifiable): ChatworkMessage
            {
                return (new ChatworkMessage)
                    ->text('test message')
                    ->selfUnread(true);
            }
        };
    }

    /**
     * it should send the request
     */
    public function testSend()
    {
        $this->clientMock
            ->shouldReceive('post')
            ->once()
            ->andReturnUsing(function ($url, $params){
                $this->assertEquals('https://api.chatwork.com/v2/rooms/999999/messages', $url);
                $this->assertEquals('test_token', $params['headers']['X-ChatWorkToken']);
                $this->assertEquals('test message', $params['form_params']['body']);
                $this->assertEquals(1, $params['form_params']['self_unread']);

                return new Response(
                    200,
                    [
                        'content-type' => 'application/json'
                    ],
                    \json_encode(['message_id' => 999999])
                );
            });

        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }

    /**
     * it should throw ChatworkException if the request fails
     */
    public function testSendWithError()
    {
        $this->clientMock
            ->shouldReceive('post')
            ->once()
            ->andReturnUsing(function ($url, $params){
                $this->assertEquals('https://api.chatwork.com/v2/rooms/999999/messages', $url);
                $this->assertEquals('test_token', $params['headers']['X-ChatWorkToken']);
                $this->assertEquals('test message', $params['form_params']['body']);
                $this->assertEquals(1, $params['form_params']['self_unread']);

                return new Response(
                    400,
                    [
                        'content-type' => 'application/json'
                    ],
                    \json_encode(["errors" => ["Invalid Request"]])
                );
            });

        $this->expectException(HttpException::class);
        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }

    public function testRoomEmptyError()
    {
        $this->mockNotifiable = new class(){
            public function routeNotificationFor($channel)
            {
                return '';
            }
        };

        $this->expectException(RoomIdEmptyException::class);
        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }

    public function testInvalidAPIToken()
    {
        Carbon::setTestNow('2025-11-01 12:23:34');

        $this->clientMock
            ->shouldReceive('post')
            ->once()
            ->andReturnUsing(function ($url, $params){
                $this->assertEquals('https://api.chatwork.com/v2/rooms/999999/messages', $url);
                $this->assertEquals('test_token', $params['headers']['X-ChatWorkToken']);
                $this->assertEquals('test message', $params['form_params']['body']);
                $this->assertEquals(1, $params['form_params']['self_unread']);

                return new Response(
                    401,
                    [
                        'content-type' => 'application/json',
                        'x-ratelimit-reset' => now()->format('U'),
                        'x-ratelimit-remaining' => '0',
                        'x-ratelimit-limit' => '1'
                    ],
                    \json_encode(["errors" => ["Invalid API token"]])
                );
            });

        $this->expectException(InvalidAPITokenException::class);
        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }

    public function testAccessTokenInsufficient()
    {
        Carbon::setTestNow('2025-11-01 12:23:34');

        $this->clientMock
            ->shouldReceive('post')
            ->once()
            ->andReturnUsing(function ($url, $params){
                $this->assertEquals('https://api.chatwork.com/v2/rooms/999999/messages', $url);
                $this->assertEquals('test_token', $params['headers']['X-ChatWorkToken']);
                $this->assertEquals('test message', $params['form_params']['body']);
                $this->assertEquals(1, $params['form_params']['self_unread']);

                return new Response(
                    403,
                    [
                        'content-type' => 'application/json',
                        'x-ratelimit-reset' => now()->format('U'),
                        'x-ratelimit-remaining' => '0',
                        'x-ratelimit-limit' => '1'
                    ],
                    \json_encode(["errors" => ["Access token has insufficient scope"]])
                );
            });

        $this->expectException(AccessTokenInsufficientScopeException::class);
        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }

    public function testSendRateLimitError()
    {
        Carbon::setTestNow('2025-11-01 12:23:34');

        $this->clientMock
            ->shouldReceive('post')
            ->once()
            ->andReturnUsing(function ($url, $params){
                $this->assertEquals('https://api.chatwork.com/v2/rooms/999999/messages', $url);
                $this->assertEquals('test_token', $params['headers']['X-ChatWorkToken']);
                $this->assertEquals('test message', $params['form_params']['body']);
                $this->assertEquals(1, $params['form_params']['self_unread']);

                return new Response(
                    429,
                    [
                        'content-type' => 'application/json',
                        'x-ratelimit-reset' => now()->addSeconds(61)->format('U'),
                        'x-ratelimit-remaining' => '0',
                        'x-ratelimit-limit' => '1'
                    ],
                    \json_encode(["errors" => ["Rate limit exceeded"]])
                );
            });

        $this->expectException(ChatworkTokenLimitException::class);
        $this->target->send($this->mockNotifiable, $this->mockNotification);
    }
}

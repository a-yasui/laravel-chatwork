<?php
declare(strict_types=1);

namespace ATYasu\Chatwork\Test;

use ATYasu\Chatwork\ChatworkChannel;
use ATYasu\Chatwork\ChatworkMessage;
use ATYasu\Chatwork\ChatworkNotification;

class ChatworkNotificationTest extends TestCase
{

    public function testVia()
    {
        $target = new class() extends ChatworkNotification
        {
            public function toChatwork($notifiable): ChatworkMessage
            {
                return new ChatworkMessage();
            }
        };

        $via = $target->via(null);
        $this->assertEquals(1, count($via));
        $this->assertEquals(ChatworkChannel::class, $via[0]);
    }
}
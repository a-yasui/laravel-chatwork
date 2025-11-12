<?php
declare(strict_types=1);

namespace ATYasu\Chatwork\Test\Message;

use ATYasu\Chatwork\Message\Info;
use ATYasu\Chatwork\Test\TestCase;

class InfoTest extends TestCase
{

    public function testWithoutTitle() {
        $info = new Info('message');
        $this->assertEquals('[info]message[/info]', $info->message());
    }

    public function testWithTitle() {
        $info = new Info('message', 'title');
        $this->assertEquals('[info][title]title[/title]message[/info]', $info->message());
    }

}
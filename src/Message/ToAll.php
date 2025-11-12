<?php
namespace ATYasu\Chatwork\Message;

use ATYasu\Chatwork\Message;

class ToAll implements Message
{
    public function message(): string
    {
        return '[toall]';
    }
}
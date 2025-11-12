<?php
namespace ATYasu\Chatwork\Message;

use ATYasu\Chatwork\Message;

class Text implements Message
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function message(): string
    {
        return $this->message;
    }
}

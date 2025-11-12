<?php
namespace ATYasu\Chatwork\Message;

use ATYasu\Chatwork\Message;

class To implements Message
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function message(): string
    {
        return '[To:' . $this->id . ']';
    }
}

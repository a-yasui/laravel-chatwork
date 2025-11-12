<?php

namespace ATYasu\Chatwork\Message;

use ATYasu\Chatwork\Message;

class Info implements Message
{
    /**
     * Information body
     */
    private string $body;

    /**
     * Information title
     */
    private ?string $title;

    public function __construct(string $body, ?string $title = null)
    {
        $this->body = $body;
        $this->title = $title;
    }

    public function message(): string
    {
        $title = $this->title ? '[title]' . $this->title . '[/title]' : '';
        return '[info]' . $title . $this->body . '[/info]';
    }
}
<?php
declare(strict_types=1);

namespace ATYasu\Chatwork\Exception;

use Psr\Http\Message\ResponseInterface;

class HttpException extends Exception
{
    public function __construct(ResponseInterface $response)
    {
        parent::__construct(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            (new \Exception)->getPrevious()
        );
    }
}

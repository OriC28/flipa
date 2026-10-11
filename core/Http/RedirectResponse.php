<?php

declare(strict_types=1);

namespace Core\Http;

use Override;

class RedirectResponse extends Response
{
    public function __construct(
        protected string $url,
        int $code = 302,
        string|array $body = "",
        array $headers = [],
        array $cookies = [],
    ) {
        parent::__construct($code, $body, $headers, $cookies);
    }

    #[Override]
    public function send(): void
    {
        http_response_code($this->code);
        header("Location: $this->url");
        echo $this->body;
    }
}

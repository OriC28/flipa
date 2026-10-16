<?php

namespace Core\Database\Exceptions;

class QueryException extends DatabaseException
{

    public function __construct(
        string $message,
        public string $sql,
        public array $bindings,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->sql = $sql;
        $this->bindings = $bindings;

        $formattedMessage = "{$message} | SQL Query: {$sql} | Bindings: " . implode(', ', $bindings);

        parent::__construct($formattedMessage, $code, $previous);
    }
}

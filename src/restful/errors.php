<?php

declare(strict_types=1);

namespace SubStore\Restful;

/**
 * 错误类定义
 * 对应原版的 errors/index.js
 */

class BaseError extends \Exception
{
    protected string $errorType;

    public function __construct(string $errorType, string $message, ?string $details = null, int $code = 0)
    {
        $this->errorType = $errorType;
        $message = $details ? "{$message}: {$details}" : $message;
        parent::__construct($message, $code);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }
}

class InternalServerError extends BaseError
{
    public function __construct(string $errorType, string $message, ?string $details = null)
    {
        parent::__construct($errorType, $message, $details, 500);
    }
}

class ResourceNotFoundError extends BaseError
{
    public function __construct(string $errorType, string $message, ?string $details = null)
    {
        parent::__construct($errorType, $message, $details, 404);
    }
}

class RequestInvalidError extends BaseError
{
    public function __construct(string $errorType, string $message, ?string $details = null)
    {
        parent::__construct($errorType, $message, $details, 400);
    }
}

class NetworkError extends BaseError
{
    public function __construct(string $errorType, string $message, ?string $details = null)
    {
        parent::__construct($errorType, $message, $details, 503);
    }
}

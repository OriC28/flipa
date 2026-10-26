<?php

declare(strict_types=1);

namespace Core\Log;

use Core\Log\Contracts\LoggerInterface;

class Logger implements LoggerInterface
{
    public function __construct(protected string $logPath, protected string $logFile) {}
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!\is_array($val) && (!\is_object($val) || method_exists($val, '__toString'))) {
                $replace["{{$key}}"] = $val;
            }
        }
        return strtr($message, $replace);
    }

    private function formatLine(LogLevel|string $level, string $message, array $context = []): string
    {
        $date = date('Y-m-d H:i:s');
        $env = env('APP_ENV', 'development');
        $levelString = $level instanceof LogLevel ? strtoupper($level->value) : strtoupper((string) $level);

        if (!empty($context)) {
            $extra = json_encode($context, JSON_UNESCAPED_UNICODE);
            return "[$date] $env.$levelString: $message $extra" . PHP_EOL;
        }
        return "[$date] $env.$levelString: $message" . PHP_EOL;
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0775, true);
        }
    }

    private function writeToFile(string $line): void
    {
        $filename = rtrim($this->logPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->logFile;
        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }

    public function log(LogLevel|string $level, string|\Stringable $message, array $context = []): void
    {
        $interpolatedMessage =  $this->interpolate($message, $context);
        $formatedMessage = $this->formatLine($level, $interpolatedMessage, $context);
        $this->ensureDirectoryExists();
        $this->writeToFile($formatedMessage);
    }
}

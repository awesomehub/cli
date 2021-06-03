<?php

declare(strict_types=1);

namespace Hub\Exception\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Handles exceptions through a psr-3 complaint logger.
 */
class LoggerExceptionHandler implements ExceptionHandlerInterface
{
    private static array $errorSeverityMap = [
        \E_DEPRECATED => LogLevel::INFO,
        \E_USER_DEPRECATED => LogLevel::INFO,
        \E_NOTICE => LogLevel::WARNING,
        \E_USER_NOTICE => LogLevel::WARNING,
        \E_STRICT => LogLevel::WARNING,
        \E_WARNING => LogLevel::WARNING,
        \E_USER_WARNING => LogLevel::WARNING,
        \E_COMPILE_WARNING => LogLevel::WARNING,
        \E_CORE_WARNING => LogLevel::WARNING,
        \E_USER_ERROR => LogLevel::CRITICAL,
        \E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
        \E_COMPILE_ERROR => LogLevel::CRITICAL,
        \E_PARSE => LogLevel::CRITICAL,
        \E_ERROR => LogLevel::CRITICAL,
        \E_CORE_ERROR => LogLevel::CRITICAL,
    ];

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(\Throwable $e): void
    {
        $logLevel = LogLevel::CRITICAL;
        if ($e instanceof \ErrorException) {
            $errorSeverity = $e->getSeverity();
            if (\array_key_exists($errorSeverity, $this::$errorSeverityMap)) {
                $logLevel = $this::$errorSeverityMap[$errorSeverity];
            }
        }

        // log the main error
        $this->logger->log($logLevel, sprintf('[%s] %s (%s:%s)', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

        // generate stack trace
        $this->logger->log($logLevel, 'Stack trace:', ['console.level' => LogLevel::DEBUG]);

        // exception related properties
        $trace = $e->getTrace();
        array_unshift($trace, [
            'function' => '',
            'file' => $e->getFile() ?? 'n/a',
            'line' => $e->getLine() ?? 'n/a',
            'args' => [],
        ]);

        foreach ($trace as $record) {
            $class = $record['class'] ?? '';
            $type = $record['type'] ?? '';
            $function = $record['function'];
            $file = $record['file'] ?? 'n/a';
            $line = $record['line'] ?? 'n/a';

            $this->logger->log($logLevel, sprintf('- %s%s%s() at %s:%s', $class, $type, $function, $file, $line), ['console.level' => LogLevel::DEBUG]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(\Throwable $e): bool
    {
        return true;
    }
}

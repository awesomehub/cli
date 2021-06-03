<?php

declare(strict_types=1);

namespace Hub\Exception\Handler;

/**
 * Handles exceptions thrown at startup (i.e. before output and logger instances are created).
 */
class StartupExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(\Throwable $e): void
    {
        echo sprintf('[%s] %s (%s:%s)', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()).\PHP_EOL;
        echo 'Stack trace:'.\PHP_EOL;
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

            echo sprintf(' - %s%s%s() at %s:%s', $class, $type, $function, $file, $line).\PHP_EOL;
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

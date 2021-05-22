<?php

namespace Hub\Exception\Handler;

/**
 * Handles exceptions thrown at startup (i.e. before output and logger instances are created).
 */
class StartupExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(\Exception $e)
    {
        echo sprintf('[%s] %s (%s:%s)', \get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()).\PHP_EOL;
        echo 'Stack trace:'.\PHP_EOL;
        // exception related properties
        $trace = $e->getTrace();
        array_unshift($trace, [
            'function' => '',
            'file' => null !== $e->getFile() ? $e->getFile() : 'n/a',
            'line' => null !== $e->getLine() ? $e->getLine() : 'n/a',
            'args' => [],
        ]);

        for ($i = 0, $count = \count($trace); $i < $count; ++$i) {
            $class = $trace[$i]['class'] ?? '';
            $type = $trace[$i]['type'] ?? '';
            $function = $trace[$i]['function'];
            $file = $trace[$i]['file'] ?? 'n/a';
            $line = $trace[$i]['line'] ?? 'n/a';

            echo sprintf(' - %s%s%s() at %s:%s', $class, $type, $function, $file, $line).\PHP_EOL;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(\Exception $e)
    {
        return true;
    }
}

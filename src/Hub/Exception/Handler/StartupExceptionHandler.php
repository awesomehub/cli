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
        echo sprintf('[%s] %s (%s:%s)', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()).PHP_EOL;
        echo 'Stack trace:'.PHP_EOL;
        // exception related properties
        $trace = $e->getTrace();
        array_unshift($trace, [
            'function' => '',
            'file'     => $e->getFile() !== null ? $e->getFile() : 'n/a',
            'line'     => $e->getLine() !== null ? $e->getLine() : 'n/a',
            'args'     => [],
        ]);

        for ($i = 0, $count = count($trace); $i < $count; ++$i) {
            $class    = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
            $type     = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
            $function = $trace[$i]['function'];
            $file     = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
            $line     = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

            echo sprintf(' - %s%s%s() at %s:%s', $class, $type, $function, $file, $line).PHP_EOL;
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

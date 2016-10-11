<?php
namespace Hub\Exception\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Handles exceptions through a psr-3 complaint logger.
 *
 * @package AwesomeHub
 */
class LoggerExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    private static $errorSeverityMap = array(
        E_DEPRECATED => LogLevel::INFO,
        E_USER_DEPRECATED => LogLevel::INFO,
        E_NOTICE => LogLevel::WARNING,
        E_USER_NOTICE => LogLevel::WARNING,
        E_STRICT => LogLevel::WARNING,
        E_WARNING => LogLevel::WARNING,
        E_USER_WARNING => LogLevel::WARNING,
        E_COMPILE_WARNING => LogLevel::WARNING,
        E_CORE_WARNING => LogLevel::WARNING,
        E_USER_ERROR => LogLevel::CRITICAL,
        E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
        E_COMPILE_ERROR => LogLevel::CRITICAL,
        E_PARSE => LogLevel::CRITICAL,
        E_ERROR => LogLevel::CRITICAL,
        E_CORE_ERROR => LogLevel::CRITICAL,
    );

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(\Exception $e)
    {
        $logLevel = LogLevel::CRITICAL;
        if($e instanceof \ErrorException){
            $errorSeverity = $e->getSeverity();
            if(array_key_exists($errorSeverity, $this::$errorSeverityMap)){
                $logLevel = $this::$errorSeverityMap[$errorSeverity];
            }
        }

        // log the main error
        $this->logger->log($logLevel, sprintf("[%s] %s (%s:%s)", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

        // generate stack trace
        $this->logger->log($logLevel, 'Stack trace:', ['console.level' => LogLevel::DEBUG]);

        // exception related properties
        $trace = $e->getTrace();
        array_unshift($trace, array(
            'function' => '',
            'file' => $e->getFile() !== null ? $e->getFile() : 'n/a',
            'line' => $e->getLine() !== null ? $e->getLine() : 'n/a',
            'args' => array(),
        ));

        for ($i = 0, $count = count($trace); $i < $count; ++$i) {
            $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
            $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
            $function = $trace[$i]['function'];
            $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
            $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

            $this->logger->log($logLevel, sprintf('- %s%s%s() at %s:%s', $class, $type, $function, $file, $line), ['console.level' => LogLevel::DEBUG]);
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

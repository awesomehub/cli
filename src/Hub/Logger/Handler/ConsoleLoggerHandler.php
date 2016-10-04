<?php
namespace Hub\Logger\Handler;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ConsoleLoggerHandler implements LoggerHandlerInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
    ];

    /**
     * @var array
     */
    private $formatLevelMap = [
        LogLevel::EMERGENCY => 'danger',
        LogLevel::ALERT => 'danger',
        LogLevel::CRITICAL => 'danger',
        LogLevel::ERROR => 'error',
        LogLevel::WARNING => 'warning',
        LogLevel::NOTICE => 'NOTICE',
        LogLevel::INFO => 'info',
        LogLevel::DEBUG => 'debug',
    ];

    /**
     * Handle Constructor.
     *
     * @param OutputInterface $output
     * @param array           $verbosityLevelMap
     * @param array           $formatLevelMap
     */
    public function __construct(OutputInterface $output, array $verbosityLevelMap = array(), array $formatLevelMap = array())
    {
        $this->output = $output;
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * {@inheritDoc}
     */
    public function handle($record)
    {
        // Write to the error output if necessary and available
        if ($this->output instanceof ConsoleOutputInterface && $this->isErrorLevel($record['level'])) {
            $output = $this->output->getErrorOutput();
        } else {
            $output = $this->output;
        }

        $output->writeln(sprintf('<%1$s>[%2$s] %3$s</%1$s>', $this->formatLevelMap[$record['level']], ucfirst($record['level']), $record['message']));
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling($level)
    {
        return $this->output->getVerbosity() >= $this->verbosityLevelMap[$level];
    }

    /**
     * Checks if a log level is an error.
     *
     * @param string $level
     * @return bool
     */
    protected function isErrorLevel($level)
    {
        return in_array($level, [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR
        ]);
    }
}

<?php

namespace Hub\Logger\Handler;

use Hub\IO\Output\OverwritableOutputInterface;
use Hub\Logger\Record\LoggerRecordInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLoggerHandler implements LoggerHandlerInterface
{
    private array $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
    ];
    private array $formatLevelMap = [
        LogLevel::EMERGENCY => 'danger',
        LogLevel::ALERT => 'danger',
        LogLevel::CRITICAL => 'danger',
        LogLevel::ERROR => 'error',
        LogLevel::WARNING => 'warning',
        LogLevel::NOTICE => 'notice',
        LogLevel::INFO => 'info',
        LogLevel::DEBUG => 'debug',
    ];

    public function __construct(private OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(LoggerRecordInterface $record): void
    {
        // Check if an explicit level is defined for the console
        $level = $this->getExplicitLevel($record);
        $isError = $this->isErrorLevel($level);

        // Write to the error output if necessary and available
        if ($this->output instanceof ConsoleOutputInterface && $isError) {
            $output = $this->output->getErrorOutput();
            // Overwrite the previous message in stdout if current output is overwritable
            if ($this->output instanceof OverwritableOutputInterface && $this->output->isOverwritable()) {
                $this->output->write('');
            }
        } else {
            $output = $this->output;
        }

        // Set the styling tags
        $tag = $this->formatLevelMap[$level];
        $label_tag = $tag.'_label';

        $output->writeln(sprintf(
            ' <%1$s>[%3$s]</%1$s>%5$s<%2$s>%4$s</%2$s>',
            $label_tag,
            $tag,
            strtoupper($level),
            $record->getMessage(),
            $isError ? "\n" : ' '
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(LoggerRecordInterface $record): bool
    {
        return $this->output->getVerbosity() >= $this->verbosityLevelMap[$this->getExplicitLevel($record)];
    }

    /**
     * Checks if a log level is an error.
     */
    protected function isErrorLevel(string $level): bool
    {
        return \in_array($level, [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
        ], true);
    }

    /**
     * Checks if a record explicitly defined a log level for the console,
     *  otherwise gets the original level.
     */
    protected function getExplicitLevel(LoggerRecordInterface $record): string
    {
        return $record->getContext('console.level') ?? $record->getLevel();
    }
}

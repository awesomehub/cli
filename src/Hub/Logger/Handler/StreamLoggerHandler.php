<?php

declare(strict_types=1);

namespace Hub\Logger\Handler;

use Hub\Logger\Record\LoggerRecordInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Stores to any stream resource.
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 */
class StreamLoggerHandler implements LoggerHandlerInterface
{
    protected const MAX_CHUNK_SIZE = 2147483647;

    /**
     * Log levels severities.
     */
    public static array $severityLevelMap = [
        LogLevel::EMERGENCY => 600,
        LogLevel::ALERT => 550,
        LogLevel::CRITICAL => 500,
        LogLevel::ERROR => 400,
        LogLevel::WARNING => 300,
        LogLevel::NOTICE => 250,
        LogLevel::INFO => 200,
        LogLevel::DEBUG => 100,
    ];

    protected string $level;
    protected int $severity;

    /** @var null|resource */
    protected $stream;
    protected ?string $url = null;
    private ?string $errorMessage = null;
    private bool $dirCreated = false;

    /**
     * Constructor.
     *
     * @param resource|string $stream
     * @param string          $level          The minimum logging level at which this handler will be triggered
     * @param null|int        $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param bool            $useLocking     Try to lock log file before doing any writes
     *
     * @throws \InvalidArgumentException If stream is not a resource or string
     */
    public function __construct(mixed $stream, string $level = LogLevel::DEBUG, protected ?int $filePermission = null, protected bool $useLocking = false)
    {
        if (\is_resource($stream)) {
            $this->stream = $stream;
            stream_set_chunk_size($this->stream, self::MAX_CHUNK_SIZE);
        } elseif (\is_string($stream)) {
            $this->url = $stream;
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        if (!\array_key_exists($level, static::$severityLevelMap)) {
            throw new InvalidArgumentException(\sprintf('The log level "%s" does not exist.', $level));
        }

        $this->level = $level;
        $this->severity = static::$severityLevelMap[$level];
    }

    public function __destruct()
    {
        try {
            if (\is_resource($this->stream)) {
                fclose($this->stream);
            }

            $this->stream = null;
        } catch (\Exception $e) {
            // do nothing
        }
    }

    public function isHandling(LoggerRecordInterface $record): bool
    {
        return static::$severityLevelMap[$record->getLevel()] >= $this->severity;
    }

    public function handle(LoggerRecordInterface $record): void
    {
        // check if we have a valid stream
        if (!\is_resource($this->stream)) {
            // check if we have a valid stream url
            if (empty($this->url)) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }

            // create the enclosing directory if not exist
            $this->createDir();

            // catch error message
            $this->errorMessage = null;
            set_error_handler([$this, '_customErrorHandler']);

            // open the file for writing
            $this->stream = fopen($this->url, 'a');
            if (null !== $this->filePermission) {
                @chmod($this->url, $this->filePermission);
            }

            // reset error handler
            restore_error_handler();

            // throw exception if stream couldn't be opened
            if (!\is_resource($this->stream)) {
                $this->stream = null;

                throw new \UnexpectedValueException(\sprintf('The stream or file "%s" could not be opened in append mode: %s', $this->url, $this->errorMessage));
            }
            stream_set_chunk_size($this->stream, self::MAX_CHUNK_SIZE);
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($this->stream, \LOCK_EX);
        }

        // write the message to the stream resource
        fwrite($this->stream, \sprintf("[%1\$s] [%2\$s] %3\$s\n", date('Y-m-d H:i:s', $record->getTimestamp()), ucfirst($record->getLevel()), $record->getMessage()));

        if ($this->useLocking) {
            flock($this->stream, \LOCK_UN);
        }
    }

    /**
     * Attempts to create the log directory if not exist.
     *
     * @throws \UnexpectedValueException If a missing directory is not buildable
     */
    private function createDir(): void
    {
        // Do not try to create dir if it has already been tried.
        if (!empty($this->dirCreated)) {
            return;
        }

        $dir = $this->getDirFromStream($this->url);
        if (null !== $dir && !is_dir($dir)) {
            // catch error message
            $this->errorMessage = null;
            set_error_handler([$this, '_customErrorHandler']);

            // Attempt to creat the directory
            $status = mkdir($dir, 0777, true);

            // restore the error handler
            restore_error_handler();

            // throw exception if directory couldn't be created
            if (!$status && !is_dir($dir)) {
                throw new \UnexpectedValueException(\sprintf('There is no existing directory at "%s" and it could not be created: %s', $dir, $this->errorMessage));
            }
        }

        $this->dirCreated = true;
    }

    /**
     * Gets the directory name from the stream.
     */
    private function getDirFromStream(string $stream): ?string
    {
        $pos = strpos($stream, '://');
        if (false === $pos) {
            return \dirname($stream);
        }

        if (str_starts_with($stream, 'file://')) {
            return \dirname(substr($stream, 7));
        }

        return null;
    }

    /**
     * Custom error handler used for catching filesystem errors.
     */
    private function _customErrorHandler(int $level, string $message): void
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $message);
    }
}

<?php
namespace Hub\Logger\Handler;

use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * Stores to any stream resource
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 *
 * @package AwesomeHub
 */
class StreamLoggerHandler implements LoggerHandlerInterface
{
    /**
     * Log levels severities.
     *
     * @var array
     */
    public static $severityLevelMap = [
        LogLevel::EMERGENCY => 600,
        LogLevel::ALERT     => 550,
        LogLevel::CRITICAL  => 500,
        LogLevel::ERROR     => 400,
        LogLevel::WARNING   => 300,
        LogLevel::NOTICE    => 250,
        LogLevel::INFO      => 200,
        LogLevel::DEBUG     => 100,
    ];

    protected $level;
    protected $severity;
    protected $stream;
    protected $url;
    protected $filePermission;
    protected $useLocking;

    private $errorMessage;
    private $dirCreated;

    /**
     * Handle Constructor.
     *
     * @param resource|string $stream
     * @param string $level The minimum logging level at which this handler will be triggered
     * @param int|null $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param boolean $useLocking Try to lock log file before doing any writes
     * @throws \InvalidArgumentException If stream is not a resource or string
     */
    public function __construct($stream, $level = LogLevel::DEBUG, $filePermission = null, $useLocking = false)
    {
        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = $stream;
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        if(!array_key_exists($level, static::$severityLevelMap)){
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $this->level = $level;
        $this->severity = static::$severityLevelMap[$level];
        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    public function __destruct()
    {
        try {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }

            $this->stream = null;
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling($record)
    {
        return  static::$severityLevelMap[$record->getLevel()] >= $this->severity;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($record)
    {
        // check if we have a valid stream
        if (!is_resource($this->stream)) {
            // check if we have a valid stream url
            if (!$this->url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }

            // create the enclosing directory if not exist
            $this->createDir();

            // catch error message
            $this->errorMessage = null;
            set_error_handler(array($this, '_customErrorHandler'));

            // open the file for writing
            $this->stream = fopen($this->url, 'a');
            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }

            // reset error handler
            restore_error_handler();

            // throw exception if stream couldn't be opened
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $this->url));
            }
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($this->stream, LOCK_EX);
        }

        // write thre message to the stream resource
        fwrite($this->stream, sprintf("[%1\$s] [%2\$s] %3\$s\n", date('Y-m-d H:i:s', $record->getTimestamp()), ucfirst($record->getLevel()), $record->getMessage()));

        if ($this->useLocking) {
            flock($this->stream, LOCK_UN);
        }
    }

    /**
     * Gets the directory name from the stream.
     *
     * @param string $stream
     * @return null|string
     */
    private function getDirFromStream($stream)
    {
        $pos = strpos($stream, '://');
        if ($pos === false) {
            return dirname($stream);
        }

        if ('file://' === substr($stream, 0, 7)) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    /**
     * Attemps to creat the log directory if not exist.
     *
     * @param void
     * @return void
     * @throws \UnexpectedValueException If a missing directory is not buildable
     */
    private function createDir()
    {
        // Do not try to create dir if it has already been tried.
        if ($this->dirCreated) {
            return;
        }

        $dir = $this->getDirFromStream($this->url);
        if (null !== $dir && !is_dir($dir)) {
            // catch error message
            $this->errorMessage = null;
            set_error_handler(array($this, '_customErrorHandler'));

            // Attemp to creat the directory
            $status = mkdir($dir, 0777, true);

            // restore the error handler
            restore_error_handler();

            // throw exception if directory couldn't be created
            if (false === $status) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s" and its not buildable: ' . $this->errorMessage, $dir));
            }
        }

        $this->dirCreated = true;
    }

    /**
     * Custom error handler used for catching filesystem errors.
     *
     * @param boolean $errno
     * @param string $errstr
     * @return void
     */
    private function _customErrorHandler($errno, $errstr)
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $errstr);
    }
}

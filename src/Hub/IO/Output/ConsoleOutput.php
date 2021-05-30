<?php

namespace Hub\IO\Output;

use Symfony\Component\Console;

/**
 * Extends Symfony's ConsoleOutput with helpful features.
 */
class ConsoleOutput extends Console\Output\ConsoleOutput implements OverwritableOutputInterface
{
    public const MAX_LINE_LENGTH = 120;

    protected bool $overwrite = false;
    protected array $options;
    protected int $lineLength;
    protected int $startTime;
    protected int $spinnerCurrent = 0;
    protected string | array $lastMessage = '';
    protected bool $lastMessageNl = false;

    /**
     * {@inheritdoc}
     */
    public function startOverwrite(array $options = []): void
    {
        $this->overwrite = true;
        $this->startTime = time();
        $this->options = array_merge([
            'spinnerValues' => ['-', '\\', '|', '/'],
            'fallbackNewline' => true,
        ], $options);
        // Windows cmd wraps lines as soon as the terminal width is reached, whether there are following chars or not.
        $this->lineLength = min($this->getTerminalWidth() - (int) (\DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
    }

    /**
     * {@inheritdoc}
     */
    public function endOverwrite(): void
    {
        $this->overwrite = false;
    }

    /**
     * {@inheritdoc}
     */
    public function isOverwritable(): bool
    {
        return $this->overwrite;
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL): void
    {
        while ($this->isOverwritable()) {
            ++$this->spinnerCurrent;
            $messages = implode($newline ? "\n" : '', (array) $messages);
            if (!$newline) {
                $messages = substr($messages, 0, $this->lineLength);
            }
            $messages = $this->processPlaceholders($messages);

            if (!$this->isDecorated()) {
                if ($this->options['fallbackNewline']) {
                    $newline = true;
                }

                break;
            }

            if ($this->lastMessageNl) {
                break;
            }

            $size = Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $this->lastMessage);
            if (!$size) {
                break;
            }

            // "\x1B [ 1 F" can move the cursor to the previous line
            // ...let's fill its length with backspaces
            parent::write(str_repeat("\x08", $size), false, $type);

            // write the new message
            parent::write($messages, false, $type);

            $fill = $size - Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $messages);
            if ($fill > 0) {
                // whitespace whatever has left
                parent::write(str_repeat("\x20", $fill), false, $type);
                // move the cursor back
                parent::write(str_repeat("\x08", $fill), false, $type);
            }

            if ($newline) {
                parent::write('', true, $type);
            }

            $this->lastMessage = $messages;
            $this->lastMessageNl = $newline;

            return;
        }

        parent::write($messages, $newline, $type);

        $this->lastMessage = $messages;
        $this->lastMessageNl = $newline;
    }

    /**
     * Replaces message placeholders with their values.
     */
    protected function processPlaceholders(string | array $message): string | array
    {
        $formatters = $this->getPlaceholderFormatters();

        return preg_replace_callback('{%([a-z\\-_]+)%}i', static function ($matches) use ($formatters) {
            return isset($formatters[$matches[1]])
                ? \call_user_func($formatters[$matches[1]])
                : $matches[0];
        }, $message);
    }

    /**
     * Gets the available message placeholders and their callbacks.
     */
    protected function getPlaceholderFormatters(): array
    {
        static $formatters;

        if (!$formatters) {
            $formatters = [
                'spinner' => function () {
                    $values = $this->options['spinnerValues'];

                    return $values[$this->spinnerCurrent % \count($values)];
                },
                'elapsed' => function () {
                    return Console\Helper\Helper::formatTime(time() - $this->startTime);
                },
                'memory' => function () {
                    return Console\Helper\Helper::formatMemory(memory_get_usage(true));
                },
            ];
        }

        return $formatters;
    }

    private function getTerminalWidth(): int
    {
        $application = new Console\Application();
        $dimensions = $application->getTerminalDimensions();

        return $dimensions[0] ?: self::MAX_LINE_LENGTH;
    }
}

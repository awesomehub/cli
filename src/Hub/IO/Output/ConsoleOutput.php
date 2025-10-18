<?php

declare(strict_types=1);

namespace Hub\IO\Output;

use Symfony\Component\Console;

/**
 * Extends Symfony's ConsoleOutput with helpful features.
 */
class ConsoleOutput extends Console\Output\ConsoleOutput implements OverwritableOutputInterface
{
    protected bool $overwrite = false;
    protected array $options;
    protected int $lineLength;
    protected int $startTime;
    protected int $spinnerCurrent = 0;
    protected array|string $lastMessage = '';
    protected bool $lastMessageNl = false;

    public function startOverwrite(array $options = []): void
    {
        $this->overwrite = true;
        $this->startTime = time();
        $this->options = array_merge([
            'spinnerValues' => ['-', '\\', '|', '/'],
            'fallbackNewline' => true,
        ], $options);

        $this->lineLength = self::getTerminalWidth();
    }

    public function endOverwrite(): void
    {
        $this->overwrite = false;
    }

    public function isOverwritable(): bool
    {
        return $this->overwrite;
    }

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

            $size = $this->strlenWithoutDecoration($this->lastMessage);
            if (!$size) {
                break;
            }

            // "\x1B [ 1 F" can move the cursor to the previous line
            // ...let's fill its length with backspaces
            parent::write(str_repeat("\x08", $size), false, $type);

            // write the new message
            parent::write($messages, false, $type);

            $fill = $size - $this->strlenWithoutDecoration($messages);
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
    protected function processPlaceholders(array|string $message): array|string
    {
        $formatters = $this->getPlaceholderFormatters();

        return preg_replace_callback(
            '{%([a-z\-_]+)%}i',
            static fn ($matches) => isset($formatters[$matches[1]])
                ? \call_user_func($formatters[$matches[1]])
                : $matches[0],
            $message
        );
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
                'elapsed' => fn () => Console\Helper\Helper::formatTime(time() - $this->startTime),
                'memory' => fn () => Console\Helper\Helper::formatMemory(memory_get_usage(true)),
            ];
        }

        return $formatters;
    }

    /**
     * Gets the width of a string without decorations using mb_strwidth.
     *
     * @param mixed $string
     */
    protected function strlenWithoutDecoration($string): int
    {
        return Console\Helper\Helper::width(Console\Helper\Helper::removeDecoration($this->getFormatter(), $string));
    }

    /**
     * Gets the terminal width.
     */
    private static function getTerminalWidth(): int
    {
        return (new Console\Terminal())->getWidth();
    }
}

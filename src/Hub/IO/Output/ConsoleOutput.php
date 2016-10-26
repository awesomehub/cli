<?php

namespace Hub\IO\Output;

use Symfony\Component\Console;

/**
 * Extends Symfony's ConsoleOutput with helpful features.
 *
 * @package AwesomeHub
 */
class ConsoleOutput extends Console\Output\ConsoleOutput implements OverwritableOutputInterface
{
    protected $overwrite = false;
    protected $options;
    protected $startTime;
    protected $spinnerCurrent = 0;
    protected $lastMessage = '';
    protected $lastMessageNl = false;

    /**
     * @inheritdoc
     */
    public function startOverwrite(array $options = [])
    {
        $this->overwrite = true;
        $this->startTime = time();
        $this->options = array_merge([
            'spinnerValues' => ['-', '\\', '|', '/'],
            'fallbackNewline' => true,
        ], $options);
    }

    /**
     * @inheritdoc
     */
    public function endOverwrite()
    {
        $this->overwrite = false;
    }

    /**
     * @inheritdoc
     */
    public function isOverwritable()
    {
        return $this->overwrite;
    }

    /**
     * {@inheritDoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        while($this->isOverwritable()){
            ++$this->spinnerCurrent;
            $messages = implode($newline ? "\n" : '', (array) $messages);
            $messages = $this->processPlaceholders($messages);

            if(!$this->isDecorated()){
                if($this->options['fallbackNewline']){
                    $newline = true;
                }
                break;
            }

            if($this->lastMessageNl){
                break;
            }

            $size = Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $this->lastMessage);
            if(!$size){
                break;
            }

            // "\x1B [ 1 F" can move the cusrsor to the previous line
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
     *
     * @param string $message
     * @return string
     */
    protected function processPlaceholders($message)
    {
        $formatters = $this->getPlaceholderFormatters();
        return preg_replace_callback("{%([a-z\-_]+)%}i", function ($matches) use ($formatters) {
            return isset($formatters[$matches[1]])
                ? call_user_func($formatters[$matches[1]])
                : $matches[0];
        }, $message);
    }

    /**
     * Gets the available message placeholders and their callbacks.
     *
     * @return array
     */
    protected function getPlaceholderFormatters()
    {
        static $formatters;

        if(!$formatters){
            $formatters = [
                'spinner' => function () {
                    $values = $this->options['spinnerValues'];
                    return $values[$this->spinnerCurrent % count($values)];
                },
                'elapsed' => function () {
                    return Console\Helper\Helper::formatTime(time() - $this->startTime);
                },
                'memory' => function () {
                    return Console\Helper\Helper::formatMemory(memory_get_usage(true));
                }
            ];
        }

        return $formatters;
    }
}

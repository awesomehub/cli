<?php

namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Entry\Factory\UrlProcessor\UrlProcessorInterface;
use Hub\Exceptions\UrlEntryCreationFailedException;

/**
 * Manages and runs url processors to create new entries.
 */
class UrlEntryFactory implements UrlEntryFactoryInterface
{
    /** @var UrlProcessorInterface[] */
    private array $processors;

    /**
     * Constructor.
     *
     * @param UrlProcessorInterface[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = [];
        foreach ($processors as $processor) {
            $this->addProcessor($processor);
        }
    }

    /**
     * Adds a processor to the stack.
     */
    public function addProcessor(UrlProcessorInterface $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array | string $input): array
    {
        if ([] === $this->processors) {
            throw new \LogicException('No url processors has been defined');
        }

        if (!\is_array($input)) {
            $input = [$input];
        }

        $entries = [];
        foreach ($input as $url) {
            foreach ($this->processors as $processor) {
                switch ($processor->getAction($url)) {
                    case UrlProcessorInterface::ACTION_PARTIAL_PROCESSING:
                        try {
                            $childUrls = $processor->process($url);
                        } catch (\Exception $e) {
                            throw new UrlEntryCreationFailedException(sprintf("Failed processing url '%s' partially; %s", $url, $e->getMessage()), $processor, $url, 0, $e);
                        }

                        if (!$childUrls) {
                            // Ignore silently if the processor didn't throw an exception
                            break;
                        }
                        if (!\is_array($childUrls)) {
                            $childUrls = [$childUrls];
                        }

                        // Prevent infinite loop
                        if (\in_array($url, $childUrls, true)) {
                            throw new \LogicException(sprintf("Infinite loop detected; '%s' processor shall not return the same url passed to it '%s'", $processor::class, $url));
                        }

                        $entries = array_merge($entries, $this->create($childUrls));

                        break;

                    case UrlProcessorInterface::ACTION_PROCESSING:
                        try {
                            $result = $processor->process($url);
                        } catch (\Exception $e) {
                            throw new UrlEntryCreationFailedException(sprintf("Failed processing url '%s' using '%s' processor; %s", $url, $processor::class, $e->getMessage()), $processor, $url, 0, $e);
                        }

                        if (!$result) {
                            // Ignore silently if the processor didn't throw an exception
                            break;
                        }
                        if (!\is_array($result)) {
                            if ($result instanceof EntryInterface) {
                                $entries[] = $result;

                                break;
                            }

                            throw new \UnexpectedValueException(sprintf("Invalid processor output of type [%s] for processor '%s'", \gettype($result), $processor::class));
                        }

                        array_walk_recursive($result, static function ($item, $key) use ($processor) {
                            if (!$item instanceof EntryInterface) {
                                throw new \UnexpectedValueException(sprintf("Invalid inner processor output value of type [%s] at index[%s] for processor '%s'", \gettype($item), $key, $processor::class));
                            }
                        });

                        $entries = array_merge($entries, $result);

                        break;

                    case UrlProcessorInterface::ACTION_SKIP:
                        break;

                    default:
                        throw new \UnexpectedValueException(sprintf("Got an invalid processing mode from processor '%s'", $processor::class));
                }
            }
        }

        return $entries;
    }
}

<?php
namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Entry\Factory\UrlProcessor\UrlProcessorInterface;
use Hub\Exceptions\UrlEntryCreationFailedException;

/**
 * Manages and runs url processors.
 *
 * @package AwesomeHub
 */
class UrlEntryFactory implements EntryFactoryInterface
{
    /**
     * @var UrlProcessorInterface[]
     */
    private $processors;

    /**
     * Constructor.
     *
     * @param UrlProcessorInterface[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = array();
        foreach ($processors as $processor) {
            $this->addProcessor($processor);
        }
    }

    /**
     * Adds a processor to the stack.
     *
     * @param  UrlProcessorInterface $processor
     * @return self
     */
    public function addProcessor(UrlProcessorInterface $processor)
    {
        $this->processors[] = $processor;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function create($urls)
    {
        if (0 === count($this->processors)) {
            throw new \LogicException('No url processors has been defined.');
        }

        if(!is_array($urls)){
            $urls = [ $urls ];
        }

        $entries = [];
        foreach ($this->processors as $processor){
            foreach ($urls as $url){
                switch ($processor->getProcessingAction($url)){
                    case UrlProcessorInterface::ACTION_SKIP:
                        break;

                    case UrlProcessorInterface::ACTION_PARTIAL_PROCESSING:
                        try {
                            $childUrls = $processor->process($url);
                        }
                        catch (\Exception $e){
                            throw new UrlEntryCreationFailedException(
                                "Failed processing url '$url' partially; {$e->getMessage()}", $processor, $url, 0, $e
                            );
                        }

                        if(!$childUrls){
                            // Ignore silently if the processor didn't throw an exception
                            break;
                        }
                        else if(!is_array($childUrls)){
                            $childUrls = [ $childUrls ];
                        }

                        if(in_array($url, $childUrls)){
                            throw new \LogicException(
                                "Infinite loop detected; '" . get_class($processor) . "' processor shouldn't return the same url passed to it."
                            );
                        }

                        $entries = array_merge($entries, $this->create($childUrls));
                        break;

                    case UrlProcessorInterface::ACTION_PROCESSING:
                        try {
                            $result = $processor->process($url);
                        }
                        catch (\Exception $e){
                            throw new UrlEntryCreationFailedException(
                                "Failed processing url '$url' using '" . get_class($processor) . "' processor; {$e->getMessage()}", $processor, $url, 0, $e
                            );
                        }

                        if(!$result){
                            // Fail silently if the processor didn't throw an exception
                            break;
                        }
                        else if(!is_array($result)){
                            if($result instanceof EntryInterface){
                                array_push($entries, $result);
                                break;
                            }

                            throw new \UnexpectedValueException(
                                "Invalid processor output of type [" . gettype($result)  . "] for processor '" . get_class($processor) . "'."
                            );
                        }
                        else {
                            array_walk_recursive($result, function($item, $key) use($processor) {
                                if(!$item instanceof EntryInterface){
                                    throw new \UnexpectedValueException(
                                        "Invalid inner processor output value of type [" . gettype($item)  . "] at index[$key] for processor '" . get_class($processor) . "'."
                                    );
                                }
                            });
                        }

                        $entries = array_merge($entries, $result);
                        break;

                    default:
                        throw new \LogicException("Invalid processing mode defined in processor '" . get_class($processor) . "'.");
                }
            }
        }

        return $entries;
    }
}

<?php
namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Entry\Factory\UrlProcessor\UrlProcessorInterface;
use Hub\Exceptions\UrlEntryCreationFailedException;

/**
 * Manages and runs url processors to create new entries.
 *
 * @package AwesomeHub
 */
class UrlEntryFactory implements UrlEntryFactoryInterface
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
            throw new \LogicException('No url processors has been defined');
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
                                sprintf(
                                    "Failed processing url '%s' partially; %s",
                                    $url, $e->getMessage()
                                ),
                                $processor, $url, 0, $e
                            );
                        }

                        if(!$childUrls){
                            // Ignore silently if the processor didn't throw an exception
                            break;
                        }
                        else if(!is_array($childUrls)){
                            $childUrls = [ $childUrls ];
                        }

                        // Prevent infinite loop
                        if(in_array($url, $childUrls)){
                            throw new \LogicException(
                                sprintf(
                                    "Infinite loop detected; '%s' processor shall not return the same url passed to it '%s'",
                                    get_class($processor), $url
                                )
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
                                sprintf(
                                    "Failed processing url '%s' using '%s' processor; %s",
                                    $url, get_class($processor), $e->getMessage()
                                ),
                                $processor, $url, 0, $e
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
                                sprintf(
                                    "Invalid processor output of type [%s] for processor '%s'",
                                    gettype($result), get_class($processor)
                                )
                            );
                        }
                        else {
                            array_walk_recursive($result, function($item, $key) use($processor) {
                                if(!$item instanceof EntryInterface){
                                    throw new \UnexpectedValueException(
                                        sprintf(
                                            "Invalid inner processor output value of type [%s] at index[%s] for processor '%s'",
                                            gettype($item), $key, get_class($processor)
                                        )
                                    );
                                }
                            });
                        }

                        $entries = array_merge($entries, $result);
                        break;

                    default:
                        throw new \LogicException(
                            sprintf("Invalid processing mode defined in processor '%s'", get_class($processor))
                        );
                }
            }
        }

        return $entries;
    }
}

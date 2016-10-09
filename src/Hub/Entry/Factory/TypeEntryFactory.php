<?php
namespace Hub\Entry\Factory;

use Hub\Entry\EntryInterface;
use Hub\Exceptions\EntryCreationFailedException;

/**
 * Creates new Entry instances based on entry type and data.
 *
 * @package AwesomeHub
 */
class TypeEntryFactory implements EntryFactoryInterface
{
    /**
     * @var array
     */
    protected $types;

    /**
     * Constructor.
     *
     * @param array $classes Entry class names
     */
    public function __construct(array $classes)
    {
        foreach ($classes as $className){
            $this->addClass($className);
        }
    }

    /**
     * Adds an entry classname to the stack.
     *
     * @param string $className
     * @return self
     */
    public function addClass($className)
    {
        if(!class_exists($className) || !is_subclass_of($className, EntryInterface::class)){
            throw new \InvalidArgumentException("Invalid Entry class name provided '$className'.");
        }

        $parameters = [];
        foreach ((new \ReflectionClass($className))->getConstructor()->getParameters() as $parameter){
            $parameters[] = [
                'name' => $parameter->getName(),
                'required' => !$parameter->isOptional()
            ];
        }

        /** @var EntryInterface $className */
        $this->types[$className::TYPE] = [
            'class' => $className,
            'parameters' => $parameters
        ];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function create($entry)
    {
        if (0 === count($this->types)) {
            throw new \LogicException('No entry types has been defined for the entry factory.');
        }

        if(!isset($this->types[$entry['type']])){
            return false;
        }

        $type = $this->types[$entry['type']];
        $args = [];
        foreach ($type['parameters'] as $parameter){
            if(!array_key_exists($parameter['name'], $entry['data'])){
                $args[] = null;
                if($parameter['required']){
                    throw new EntryCreationFailedException("Unable to satisfay all the required paramaters; Given a data array with keys [" . implode(", ", array_keys($entry['data'])) . "].");
                }
            }

            $args[] = $entry['data'][$parameter['name']];
        }

        return [
            new $type['class'](...$args)
        ];
    }
}

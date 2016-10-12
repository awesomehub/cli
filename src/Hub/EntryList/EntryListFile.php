<?php
namespace Hub\EntryList;

use Symfony\Component\Serializer;
use Hub\Filesystem\Filesystem;

/**
 * Creates list instances from files of different formats.
 *
 * @package AwesomeHub
 */
class EntryListFile extends EntryList implements EntryListFileInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var $format
     */
    protected $format;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param $format
     *
     * @throws \RuntimeException
     */
    public function __construct(Filesystem $filesystem, $path, $format)
    {
        $this->path = $path;
        $this->format = $format;

        try {
            $encoded = $filesystem->read($this->path);
        }
        catch(\Exception $e){
            throw new \RuntimeException("Unable to read the list definition file; {$e->getMessage()}");
        }

        parent::__construct($this->decode($encoded));
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Decodes given data into an array.
     *
     * @param string $data
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    protected function decode($data)
    {
        if(empty($data)){
            throw new \InvalidArgumentException("Empty list definition file provided at '$this->path'.");
        }

        $serializer = new Serializer\Encoder\ChainDecoder([
            new Serializer\Encoder\JsonDecode(true)
        ]);

        if(!$serializer->supportsDecoding($this->format)){
            throw new \LogicException("Unsupported list definition file format provided '$this->format'.");
        }

        try {
            return $serializer->decode($data, $this->format);
        }
        catch (\Exception $e){
            throw new \RuntimeException("Unable to decode list definition file at '{$this->path}'; {$e->getMessage()}", 0, $e);
        }
    }
}

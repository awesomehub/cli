<?php
namespace Docklyn;

/**
 * The dependency container for intiating and retreiving services.
 *
 * @package Docklyn
 */
class Container
{
    /**
     * @var array
     */
    private $services;

    /**
     * Gets the 'docklyn' service.
     *
     * @return \Docklyn\Docklyn A Docklyn\Docklyn instance.
     */
    public function getDocklynService()
    {
        if(!empty($this->services['docklyn'])){
            return $this->services['docklyn'];
        }

        $input = new \Symfony\Component\Console\Input\ArgvInput();

        $output = new \Symfony\Component\Console\Output\ConsoleOutput(
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL,
            null,
            new \Docklyn\Util\OutputFormatter()
        );

        $logger = new \Docklyn\Logger\LoggerManager([
            new \Docklyn\Logger\Handler\ConsoleLoggerHandler($output),
            new \Docklyn\Logger\Handler\StreamLoggerHandler(__DIR__ . '/../var/log/docklyn.log'),
        ]);

        $exception_handler = \Docklyn\Exception\ExceptionHandlerManager::register([
            new \Docklyn\Exception\Handler\LoggerExceptionHandler($logger)
        ]);

        $this->services['docklyn'] = $docklyn = new \Docklyn\Docklyn();

        $docklyn->setExceptionHandler($exception_handler);
        $docklyn->setApplication(new \Docklyn\Application($docklyn));
        $docklyn->setInput($input);
        $docklyn->setOutput($output);
        $docklyn->setLogger($logger);

        return $docklyn;
    }
}

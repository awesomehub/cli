<?php

namespace Hub\Command;

/**
 * Inspects github tokens status.
 * 
 * @package AwesomeHub
 */
class GithubTokensCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('github:tokens')
            ->setDescription('Inspects github tokens status.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function exec()
    {
        /** @var \Github\Utils\GithubWrapperInterface $github */
        $github = $this->container->get('github');
        $tokens = $github->getTokenPool()->getTokens();

        $this->io->section("Github Tokens");

        $i = 0;
        foreach ($tokens as $token){
            $i++;

            $github->setToken($token);
            $ratelimit = $github->api('rateLimit/getRateLimits');

            $list = [];
            foreach ($ratelimit['resources'] as $name => $resource) {
                $list[] = sprintf(
                    '   <comment>* %s</comment>: <debug>Remaining:</debug> %d/%d <debug>Reset</debug>: %s',
                    $name,
                    $resource['remaining'],
                    $resource['limit'],
                    date('Y-m-d g:i:s A e', $resource['reset'])
                );
            }

            $this->io->writeln(sprintf('<info>%d. %s</info> [%s]', $i, $token->getId(), get_class($token)));
            $this->io->writeln($list);
            $this->io->writeln('');
        }

        if(count($tokens) == 0){
            $this->io->writeln('No Github token has been defined.');
        }

        return 0;
    }
}

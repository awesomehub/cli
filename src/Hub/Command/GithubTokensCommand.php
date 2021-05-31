<?php

namespace Hub\Command;

/**
 * Inspects github tokens status.
 */
class GithubTokensCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
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
    protected function exec(): int
    {
        /** @var \Github\Utils\GithubWrapperInterface $github */
        $github = $this->container->get('github');
        $tokens = $github->getTokenPool()->getTokens();

        $this->io->section('Github Tokens');

        $i = 0;
        foreach ($tokens as $token) {
            ++$i;

            $github->setToken($token);
            /** @var \Github\Api\RateLimit\RateLimitResource[] $rsources */
            $rsources = $github->api('rateLimit/getResources');
            $list = [];
            foreach ($rsources as $resource) {
                $list[] = sprintf(
                    '   <comment>* %s</comment>: <debug>Remaining:</debug> %d/%d <debug>Reset</debug>: %s',
                    $resource->getName(),
                    $resource->getRemaining(),
                    $resource->getLimit(),
                    date('Y-m-d g:i:s A e', $resource->getReset())
                );
            }

            $this->io->writeln(sprintf('<info>%d. %s</info> [%s]', $i, $token->getId(), $token::class));
            $this->io->writeln($list);
            $this->io->writeln('');
        }

        if ([] === $tokens) {
            $this->io->writeln('No Github token has been defined.');
        }

        return 0;
    }
}

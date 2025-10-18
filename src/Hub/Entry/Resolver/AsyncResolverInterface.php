<?php

declare(strict_types=1);

namespace Hub\Entry\Resolver;

/**
 * Marks resolvers that can be instantiated inside async worker processes.
 */
interface AsyncResolverInterface
{
    /**
     * Provides the data needed to re-create the resolver inside an async worker.
     *
     * @return array<string, mixed>
     */
    public function getAsyncContext(): array;

    /**
     * Creates a fresh resolver instance that can be used within an async worker.
     *
     * @param array<string, mixed> $context
     */
    public static function createAsyncResolver(array $context): static;
}

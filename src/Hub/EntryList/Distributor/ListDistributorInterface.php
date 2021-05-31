<?php

declare(strict_types=1);

namespace Hub\EntryList\Distributor;

use Hub\EntryList\EntryListInterface;

/**
 * Interface for a ListDistributor.
 */
interface ListDistributorInterface
{
    /**
     * Distributes the list.
     */
    public function distribute(EntryListInterface $list): void;
}

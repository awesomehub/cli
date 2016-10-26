<?php

namespace Hub\EntryList\Distributer;

use Hub\EntryList\EntryListInterface;

/**
 * Interface for a ListDistributer.
 */
interface ListDistributerInterface
{
    /**
     * Distributes the list.
     *
     * @param EntryListInterface $list
     */
    public function distribute(EntryListInterface $list);
}

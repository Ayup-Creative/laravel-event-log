<?php

namespace AyupCreative\EventLog\Support;

use Illuminate\Database\Eloquent\Collection;
use Exception;

class MetadataCollection extends Collection
{
    /**
     * Dynamically access metadata by key.
     *
     * @param string $key
     * @return mixed
     *
     * @throws Exception
     */
    public function __get($key)
    {
        $item = $this->firstWhere('key', $key);

        if ($item) {
            return $item->value;
        }

        return parent::__get($key);
    }
}

<?php
/**
 * Class ProviderB
 */

namespace Moro\Container7\Test;

/**
 * Class ProviderB
 */
class ProviderB
{
    public function withIntegerParameters(int $i)
    {
        return $i;
    }
}
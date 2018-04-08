<?php
/**
 * Class ProviderF
 */

namespace Moro\Container7\Test;

use Moro\Container7\Aliases;
use Moro\Container7\Tags;

/**
 * Class ProviderF
 */
class ProviderF
{
    final public function tags(Aliases $aliases): Tags
    {
        return new Tags($aliases);
    }
}
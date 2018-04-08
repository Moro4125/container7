<?php
/**
 * Class ProviderG
 */

namespace Moro\Container7\Test;

use Moro\Container7\Aliases;

/**
 * Class ProviderG
 */
class ProviderG
{
    final public function aliases(): Aliases
    {
        return new Aliases();
    }
}
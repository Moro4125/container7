<?php
/**
 * Class ProviderE
 */

namespace Moro\Container7\Test;

use Moro\Container7\Parameters;

/**
 * Class ProviderD
 */
class ProviderE
{
    final public function parameters(): Parameters
    {
        return new Parameters();
    }
}
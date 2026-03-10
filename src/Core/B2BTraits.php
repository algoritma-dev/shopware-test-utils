<?php

namespace Algoritma\ShopwareTestUtils\Core;

use Algoritma\ShopwareTestUtils\Traits\B2B\B2BHelpersTrait;
use Shopware\Commercial\SwagCommercial;

if (class_exists(SwagCommercial::class)) {
    trait B2BTraits
    {
        use B2BHelpersTrait;
    }
} else {
    trait B2BTraits {}
}

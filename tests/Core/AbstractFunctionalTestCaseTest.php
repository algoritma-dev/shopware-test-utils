<?php

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\AbstractFunctionalTestCase;
use PHPUnit\Framework\TestCase;

class AbstractFunctionalTestCaseTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(AbstractFunctionalTestCase::class));
    }
}

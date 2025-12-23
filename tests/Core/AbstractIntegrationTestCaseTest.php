<?php

namespace Algoritma\ShopwareTestUtils\Tests\Core;

use Algoritma\ShopwareTestUtils\Core\AbstractIntegrationTestCase;
use PHPUnit\Framework\TestCase;

class AbstractIntegrationTestCaseTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(AbstractIntegrationTestCase::class));
    }
}

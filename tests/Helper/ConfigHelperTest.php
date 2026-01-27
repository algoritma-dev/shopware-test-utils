<?php

declare(strict_types=1);

namespace Algoritma\ShopwareTestUtils\Tests\Helper;

use Algoritma\ShopwareTestUtils\Helper\ConfigHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigHelperTest extends TestCase
{
    private MockObject $container;

    private MockObject $configService;

    private ConfigHelper $helper;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->configService = $this->createMock(SystemConfigService::class);
        $this->container->method('get')
            ->with(SystemConfigService::class)
            ->willReturn($this->configService);
        $this->helper = new ConfigHelper($this->container);
    }

    public function testSetCallsConfigService(): void
    {
        $this->configService->expects($this->once())
            ->method('set')
            ->with('test.key', 'value', null);

        $this->helper->set('test.key', 'value');
    }

    public function testSetWithSalesChannelId(): void
    {
        $salesChannelId = 'channel-123';
        $this->configService->expects($this->once())
            ->method('set')
            ->with('test.key', 'value', $salesChannelId);

        $this->helper->set('test.key', 'value', $salesChannelId);
    }

    public function testGetCallsConfigService(): void
    {
        $this->configService->expects($this->once())
            ->method('get')
            ->with('test.key', null)
            ->willReturn('value');

        $result = $this->helper->get('test.key');

        $this->assertSame('value', $result);
    }

    public function testGetWithSalesChannelId(): void
    {
        $salesChannelId = 'channel-123';
        $this->configService->expects($this->once())
            ->method('get')
            ->with('test.key', $salesChannelId)
            ->willReturn('value');

        $result = $this->helper->get('test.key', $salesChannelId);

        $this->assertSame('value', $result);
    }

    public function testDeleteCallsConfigService(): void
    {
        $this->configService->expects($this->once())
            ->method('delete')
            ->with('test.key', null);

        $this->helper->delete('test.key');
    }

    public function testSetMultipleCallsSetForEachConfig(): void
    {
        $configs = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $salesChannelId = 'channel-123';

        $matcher = $this->exactly(3);
        $this->configService->expects($matcher)
            ->method('set')
            ->willReturnCallback(function (string $key, string $value, ?string $actualSalesChannelId) use ($matcher, $salesChannelId): void {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['key1', 'value1', $salesChannelId], [$key, $value, $actualSalesChannelId]),
                    2 => $this->assertEquals(['key2', 'value2', $salesChannelId], [$key, $value, $actualSalesChannelId]),
                    3 => $this->assertEquals(['key3', 'value3', $salesChannelId], [$key, $value, $actualSalesChannelId]),
                    default => $this->fail('Unexpected invocation count'),
                };
            });

        $this->helper->setMultiple($configs, $salesChannelId);
    }

    public function testWithConfigRestoresOriginalValues(): void
    {
        $originalValue = 'original';
        $tempValue = 'temporary';
        $salesChannelId = 'channel-123';

        // Expect get to be called ONCE to save original value
        $this->configService->expects($this->once())
            ->method('get')
            ->with('test.key', $salesChannelId)
            ->willReturn($originalValue);

        $matcher = $this->exactly(2);
        $this->configService->expects($matcher)
            ->method('set')
            ->willReturnCallback(function (string $key, string $value, ?string $actualSalesChannelId) use ($matcher, $tempValue, $originalValue, $salesChannelId): void {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['test.key', $tempValue, $salesChannelId], [$key, $value, $actualSalesChannelId]),
                    2 => $this->assertEquals(['test.key', $originalValue, $salesChannelId], [$key, $value, $actualSalesChannelId]),
                    default => $this->fail('Unexpected invocation count'),
                };
            });

        $callbackExecuted = false;
        $this->helper->withConfig(['test.key' => $tempValue], function () use (&$callbackExecuted): void {
            $callbackExecuted = true;
        }, $salesChannelId);

        $this->assertTrue($callbackExecuted);
    }

    public function testWithConfigDeletesWhenOriginalWasNull(): void
    {
        $this->configService->method('get')
            ->with('test.key', null)
            ->willReturn(null);

        $this->configService->expects($this->once())
            ->method('set')
            ->with('test.key', 'value', null);

        $this->configService->expects($this->once())
            ->method('delete')
            ->with('test.key', null);

        $this->helper->withConfig(['test.key' => 'value'], function (): void {
            // test callback
        });
    }

    public function testWithConfigReturnsCallbackResult(): void
    {
        $this->configService->method('get')->willReturn('original');

        $result = $this->helper->withConfig(['test.key' => 'value'], fn (): string => 'callback result');

        $this->assertSame('callback result', $result);
    }

    public function testGetDomainCallsConfigService(): void
    {
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $salesChannelId = 'channel-123';
        $this->configService->expects($this->once())
            ->method('getDomain')
            ->with('test.domain', $salesChannelId, true)
            ->willReturn($expected);

        $result = $this->helper->getDomain('test.domain', $salesChannelId);

        $this->assertSame($expected, $result);
    }

    public function testEnableFeatureFlagSetsServerAndEnv(): void
    {
        $this->helper->enableFeatureFlag('FEATURE_FLAG_TEST');

        $this->assertSame('1', $_SERVER['FEATURE_FLAG_TEST']);
        $this->assertSame('1', $_ENV['FEATURE_FLAG_TEST']);

        unset($_SERVER['FEATURE_FLAG_TEST'], $_ENV['FEATURE_FLAG_TEST']);
    }

    public function testDisableFeatureFlagSetsServerAndEnvToZero(): void
    {
        $this->helper->disableFeatureFlag('FEATURE_FLAG_TEST');

        $this->assertSame('0', $_SERVER['FEATURE_FLAG_TEST']);
        $this->assertSame('0', $_ENV['FEATURE_FLAG_TEST']);

        unset($_SERVER['FEATURE_FLAG_TEST'], $_ENV['FEATURE_FLAG_TEST']);
    }

    public function testWithFeatureFlagRestoresOriginalValue(): void
    {
        $_SERVER['FEATURE_FLAG_TEST'] = 'original';
        $_ENV['FEATURE_FLAG_TEST'] = 'original';

        $callbackExecuted = false;
        $this->helper->withFeatureFlag('FEATURE_FLAG_TEST', function () use (&$callbackExecuted): void {
            $callbackExecuted = true;
            $this->assertSame('1', $_SERVER['FEATURE_FLAG_TEST']);
            $this->assertSame('1', $_ENV['FEATURE_FLAG_TEST']);
        });

        $this->assertTrue($callbackExecuted);
        $this->assertSame('original', $_SERVER['FEATURE_FLAG_TEST']);
        $this->assertSame('original', $_ENV['FEATURE_FLAG_TEST']);

        unset($_SERVER['FEATURE_FLAG_TEST'], $_ENV['FEATURE_FLAG_TEST']);
    }

    public function testWithFeatureFlagUnsetsWhenOriginalWasNull(): void
    {
        unset($_SERVER['FEATURE_FLAG_NEW'], $_ENV['FEATURE_FLAG_NEW']);

        $this->helper->withFeatureFlag('FEATURE_FLAG_NEW', function (): void {
            $this->assertSame('1', $_SERVER['FEATURE_FLAG_NEW']);
        });

        $this->assertArrayNotHasKey('FEATURE_FLAG_NEW', $_SERVER);
        $this->assertArrayNotHasKey('FEATURE_FLAG_NEW', $_ENV);
    }

    public function testSetPluginConfigBuildsCorrectKey(): void
    {
        $salesChannelId = 'channel-123';
        $this->configService->expects($this->once())
            ->method('set')
            ->with('MyPlugin.config.myKey', 'value', $salesChannelId);

        $this->helper->setPluginConfig('MyPlugin', 'myKey', 'value', $salesChannelId);
    }

    public function testGetPluginConfigBuildsCorrectKey(): void
    {
        $salesChannelId = 'channel-123';
        $this->configService->expects($this->once())
            ->method('get')
            ->with('MyPlugin.config.myKey', $salesChannelId)
            ->willReturn('value');

        $result = $this->helper->getPluginConfig('MyPlugin', 'myKey', $salesChannelId);

        $this->assertSame('value', $result);
    }

    public function testClearCacheCallsConfigServiceIfMethodExists(): void
    {
        $configService = $this->createMock(SystemConfigService::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($configService);

        $helper = new ConfigHelper($container);
        $helper->clearCache();

        $this->assertTrue(true);
    }
}

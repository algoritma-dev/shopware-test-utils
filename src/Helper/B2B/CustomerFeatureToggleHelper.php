<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper for enabling/disabling B2B features for customers.
 * Manages customer-specific feature flags.
 */
class CustomerFeatureToggleHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * Enable a B2B feature for a customer.
     */
    public function enableFeature(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        $currentFeatures = $features['features'] ?? [];
        $currentFeatures[$featureCode] = true;

        $this->updateFeatures($customerId, $currentFeatures, $context);
    }

    /**
     * Disable a B2B feature for a customer.
     */
    public function disableFeature(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        $currentFeatures = $features['features'] ?? [];
        $currentFeatures[$featureCode] = false;

        $this->updateFeatures($customerId, $currentFeatures, $context);
    }

    /**
     * Check if feature is enabled.
     */
    public function isFeatureEnabled(string $customerId, string $featureCode, ?Context $context = null): bool
    {
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        return ($features['features'][$featureCode] ?? false) === true;
    }

    /**
     * Enable all B2B features for a customer.
     */
    public function enableAllFeatures(string $customerId, ?Context $context = null): void
    {
        $this->updateFeatures($customerId, [
            'QUICK_ORDER' => true,
            'EMPLOYEE_MANAGEMENT' => true,
            'QUOTE_MANAGEMENT' => true,
            'ORDER_APPROVAL' => true,
            'SHOPPING_LISTS' => true,
            'BUDGET_MANAGEMENT' => true,
            'ORGANIZATION_UNITS' => true,
        ], $context);
    }

    /**
     * Disable all B2B features for a customer.
     */
    public function disableAllFeatures(string $customerId, ?Context $context = null): void
    {
        $this->updateFeatures($customerId, [], $context);
    }

    private function getOrCreateCustomerFeatures(string $customerId, Context $context): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_customer_specific_features.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        $existing = $repository->search($criteria, $context)->first();

        if ($existing) {
            return [
                'id' => $existing->getId(),
                'features' => $existing->getFeatures() ?? [],
            ];
        }

        return [
            'id' => Uuid::randomHex(),
            'customerId' => $customerId,
            'features' => [],
        ];
    }

    private function updateFeatures(string $customerId, array $features, Context $context): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('b2b_customer_specific_features.repository');

        $data = $this->getOrCreateCustomerFeatures($customerId, $context);
        $data['features'] = $features;
        $data['customerId'] = $customerId;

        $repository->upsert([$data], $context);
    }
}

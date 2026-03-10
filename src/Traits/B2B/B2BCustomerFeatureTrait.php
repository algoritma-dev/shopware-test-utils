<?php

namespace Algoritma\ShopwareTestUtils\Traits\B2B;

use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesCollection;
use Shopware\Commercial\B2B\QuickOrder\Entity\CustomerSpecificFeaturesEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Trait for enabling/disabling B2B features for customers.
 */
trait B2BCustomerFeatureTrait
{
    use KernelTestBehaviour;

    protected function enableB2bCustomerFeature(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        $currentFeatures = $features['features'] ?? [];
        $currentFeatures[$featureCode] = true;

        $this->updateCustomerFeatures($customerId, $currentFeatures, $context);
    }

    protected function disableB2bCustomerFeature(string $customerId, string $featureCode, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        $currentFeatures = $features['features'] ?? [];
        $currentFeatures[$featureCode] = false;

        $this->updateCustomerFeatures($customerId, $currentFeatures, $context);
    }

    protected function isB2bCustomerFeatureEnabled(string $customerId, string $featureCode, ?Context $context = null): bool
    {
        $context ??= Context::createCLIContext();
        $features = $this->getOrCreateCustomerFeatures($customerId, $context);

        return ($features['features'][$featureCode] ?? false) === true;
    }

    protected function enableAllB2bCustomerFeatures(string $customerId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->updateCustomerFeatures($customerId, [
            'QUICK_ORDER' => true,
            'EMPLOYEE_MANAGEMENT' => true,
            'QUOTE_MANAGEMENT' => true,
            'ORDER_APPROVAL' => true,
            'SHOPPING_LISTS' => true,
            'BUDGET_MANAGEMENT' => true,
            'ORGANIZATION_UNITS' => true,
        ], $context);
    }

    protected function disableAllB2bCustomerFeatures(string $customerId, ?Context $context = null): void
    {
        $context ??= Context::createCLIContext();
        $this->updateCustomerFeatures($customerId, [], $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrCreateCustomerFeatures(string $customerId, Context $context): array
    {
        $repository = $this->getB2bCustomerSpecificFeaturesRepository();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        $entity = $repository->search($criteria, $context)->first();

        if ($entity instanceof CustomerSpecificFeaturesEntity) {
            return [
                'id' => $entity->getId(),
                'features' => $entity->getFeatures(),
            ];
        }

        return [
            'id' => Uuid::randomHex(),
            'customerId' => $customerId,
            'features' => [],
        ];
    }

    private function updateCustomerFeatures(string $customerId, array $features, Context $context): void
    {
        $repository = $this->getB2bCustomerSpecificFeaturesRepository();

        $data = $this->getOrCreateCustomerFeatures($customerId, $context);
        $data['features'] = $features;
        $data['customerId'] = $customerId;

        $repository->upsert([$data], $context);
    }

    /**
     * @return EntityRepository<CustomerSpecificFeaturesCollection>
     */
    private function getB2bCustomerSpecificFeaturesRepository(): EntityRepository
    {
        return static::getContainer()->get('b2b_customer_specific_features.repository');
    }
}

<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use PHPUnit\Framework\Assert;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CustomerTrait
{
    use KernelTestBehaviour;

    protected function customerAssertLoggedIn(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        Assert::assertInstanceOf(CustomerEntity::class, $customer, 'No customer logged in (context has no customer)');
    }

    protected function customerAssertGuestSession(SalesChannelContext $context): void
    {
        $customer = $context->getCustomer();
        Assert::assertNotInstanceOf(CustomerEntity::class, $customer, 'Customer is logged in but should be guest');
    }

    protected function customerAssertHasAddress(CustomerEntity $customer, string $addressId): void
    {
        $addresses = $customer->getAddresses();

        if (! $addresses instanceof CustomerAddressCollection) {
            /** @var EntityRepository<CustomerCollection> $repository */
            $repository = $this->getContainer()->get('customer.repository');

            $criteria = new Criteria([$customer->getId()]);
            $criteria->addAssociation('addresses');

            $reloadedCustomer = $repository->search($criteria, Context::createCLIContext())->get($customer->getId());

            if ($reloadedCustomer instanceof CustomerEntity) {
                $addresses = $reloadedCustomer->getAddresses();
            }
        }

        Assert::assertInstanceOf(CustomerAddressCollection::class, $addresses, 'Customer has no addresses. Forgot Criteria::addAssociation(\'addresses\')?');

        $found = false;
        foreach ($addresses as $address) {
            if ($address->getId() === $addressId) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Customer does not have address %s', $addressId));
    }

    protected function customerAssertBelongsToGroup(CustomerEntity $customer, string $groupId): void
    {
        $actualGroupId = $customer->getGroupId();
        Assert::assertNotNull($actualGroupId, 'Customer has no group');
        Assert::assertSame($groupId, $actualGroupId, sprintf('Customer is in group %s, expected %s', $actualGroupId, $groupId));
    }
}

<?php

namespace Algoritma\ShopwareTestUtils\Helper\B2B;

use Shopware\Commercial\B2B\OrderApproval\Domain\CartToPendingOrder\PendingOrderRequestedRoute;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderCollection;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderApprovalHelper
{
    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * @param array<string, mixed> $data
     */
    public function requestPendingOrder(SalesChannelContext $context, CustomerEntity $customer, array $data = []): PendingOrderEntity
    {
        /** @var PendingOrderRequestedRoute $route */
        $route = $this->container->get(PendingOrderRequestedRoute::class);

        $dataBag = new RequestDataBag($data);
        // @phpstan-ignore-next-line
        $response = $route->request($context, $customer, $dataBag);

        return $response->getPendingOrder();
    }

    // --- Order Approval Assertions ---

    /**
     * Assert order needs approval.
     */
    public function assertOrderNeedsApproval(string $orderId, ?Context $context = null): void
    {
        $pendingOrder = $this->loadPendingOrderByOrderId($orderId, $context);

        assert(
            $pendingOrder instanceof PendingOrderEntity,
            sprintf('Expected order "%s" to require approval, but no pending order found', $orderId)
        );
    }

    /**
     * Assert pending order was created.
     */
    public function assertPendingOrderCreated(string $employeeId, ?Context $context = null): void
    {
        $pendingOrders = $this->loadPendingOrdersForEmployee($employeeId, $context);

        assert(
            count($pendingOrders) > 0,
            sprintf('Expected pending order to be created for employee "%s", but none found', $employeeId)
        );
    }

    /**
     * Assert pending order is in specific state.
     */
    public function assertPendingOrderInState(string $pendingOrderId, string $expectedState, ?Context $context = null): void
    {
        $pendingOrder = $this->loadPendingOrderEntity($pendingOrderId, $context);
        $actualState = $pendingOrder->getStateMachineState()->getTechnicalName();

        assert(
            $actualState === $expectedState,
            sprintf('Expected pending order to be in state "%s", but got "%s"', $expectedState, $actualState)
        );
    }

    private function loadPendingOrderEntity(string $pendingOrderId, ?Context $context): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository<PendingOrderCollection> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');
        $criteria = new Criteria([$pendingOrderId]);
        $criteria->addAssociation('stateMachineState');

        $pendingOrder = $repository->search($criteria, $context)->first();
        if (! $pendingOrder instanceof PendingOrderEntity) {
            throw new \RuntimeException(sprintf('PendingOrder with ID "%s" not found', $pendingOrderId));
        }

        return $pendingOrder;
    }

    private function loadPendingOrderByOrderId(string $orderId, ?Context $context): ?PendingOrderEntity
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository<PendingOrderCollection> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        $entity = $repository->search($criteria, $context)->first();

        return $entity instanceof PendingOrderEntity ? $entity : null;
    }

    /**
     * @return array<int, PendingOrderEntity>
     */
    private function loadPendingOrdersForEmployee(string $employeeId, ?Context $context): array
    {
        $context ??= Context::createCLIContext();
        /** @var EntityRepository<PendingOrderCollection> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('employeeId', $employeeId));

        /** @var array<string, PendingOrderEntity> $elements */
        $elements = $repository->search($criteria, $context)->getElements();

        return array_values($elements);
    }
}

<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Doctrine\DBAL\Connection;
use Shopware\Commercial\B2B\OrderApproval\Domain\State\PendingOrderStates;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderCollection;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating pending orders with approval rules.
 * Pure factory: only creates pending orders, no business logic.
 */
class PendingOrderFactory extends AbstractFactory
{
    /**
     * Set the cart for the pending order.
     */
    public function withCart(Cart $cart, SalesChannelContext $context): self
    {
        // Serialize cart data
        $this->data['cartData'] = serialize($cart);
        $this->data['salesChannelId'] = $context->getSalesChannelId();
        $this->data['currencyId'] = $context->getCurrency()->getId();
        $this->data['price'] = $cart->getPrice();

        return $this;
    }

    /**
     * Set the employee who created the pending order.
     */
    public function withEmployee(string $employeeId): self
    {
        $this->data['employeeId'] = $employeeId;

        return $this;
    }

    /**
     * Set the customer (business partner).
     */
    public function withCustomer(string $customerId): self
    {
        $this->data['customerId'] = $customerId;

        return $this;
    }

    /**
     * Set the approval rule that triggered this pending order.
     */
    public function withApprovalRule(string $approvalRuleId): self
    {
        $this->data['approvalRuleId'] = $approvalRuleId;

        return $this;
    }

    /**
     * Set designated payers for budget approval.
     *
     * @param array<string> $payerIds
     */
    public function withDesignatedPayers(array $payerIds): self
    {
        $this->data['designatedPayerIds'] = $payerIds;

        return $this;
    }

    /**
     * Set a reason/comment for the pending order.
     */
    public function withReason(string $reason): self
    {
        $this->data['reason'] = $reason;

        return $this;
    }

    /**
     * Set custom fields.
     *
     * @param array<string, mixed> $customFields
     */
    public function withCustomFields(array $customFields): self
    {
        $this->data['customFields'] = $customFields;

        return $this;
    }

    /**
     * Set payment method.
     */
    public function withPaymentMethod(string $paymentMethodId): self
    {
        $this->data['paymentMethodId'] = $paymentMethodId;

        return $this;
    }

    /**
     * Set shipping method.
     */
    public function withShippingMethod(string $shippingMethodId): self
    {
        $this->data['shippingMethodId'] = $shippingMethodId;

        return $this;
    }

    /**
     * Create the pending order.
     */
    public function create(?Context $context = null): PendingOrderEntity
    {
        $context ??= Context::createCLIContext();

        /** @var EntityRepository<PendingOrderCollection> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        if (! isset($this->data['id'])) {
            $this->data['id'] = Uuid::randomHex();
        }

        // Set state to pending if not set
        if (! isset($this->data['stateId'])) {
            $stateId = $this->getStateMachineStateId(PendingOrderStates::STATE_PENDING);
            $this->data['stateId'] = $stateId;
        }

        $repository->create([$this->data], $context);

        return $this->load($this->data['id'], $context);
    }

    /**
     * Create a pending order directly from a cart.
     */
    public static function fromCart(
        ContainerInterface $container,
        Cart $cart,
        SalesChannelContext $context,
        ?string $employeeId = null,
        ?string $approvalRuleId = null
    ): PendingOrderEntity {
        $factory = new self($container);
        $factory->withCart($cart, $context);

        if ($employeeId) {
            $factory->withEmployee($employeeId);
        }

        if ($context->getCustomer() instanceof CustomerEntity) {
            $factory->withCustomer($context->getCustomer()->getId());
        }

        if ($approvalRuleId) {
            $factory->withApprovalRule($approvalRuleId);
        }

        return $factory->create($context->getContext());
    }

    protected function getRepositoryName(): string
    {
        return 'b2b_components_pending_order.repository';
    }

    protected function getEntityName(): string
    {
        return 'b2b_components_pending_order';
    }

    protected function getDefaults(): array
    {
        return [];
    }

    private function load(string $id, Context $context): PendingOrderEntity
    {
        /** @var EntityRepository<PendingOrderCollection> $repository */
        $repository = $this->container->get('b2b_components_pending_order.repository');

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('employee');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('approvalRule');
        $criteria->addAssociation('stateMachineState');

        /** @var PendingOrderEntity|null $entity */
        $entity = $repository->search($criteria, $context)->first();

        if (! $entity) {
            throw new \RuntimeException(sprintf('PendingOrder with ID "%s" not found', $id));
        }

        return $entity;
    }

    private function getStateMachineStateId(string $technicalName): string
    {
        $connection = $this->container->get(Connection::class);

        $sql = <<<'EOD'

                        SELECT LOWER(HEX(id))
                        FROM state_machine_state
                        WHERE technical_name = :technicalName
                        AND state_machine_id = (
                            SELECT id FROM state_machine WHERE technical_name = :stateMachine
                        )
                    
            EOD;

        $result = $connection->fetchOne($sql, [
            'technicalName' => $technicalName,
            'stateMachine' => PendingOrderStates::STATE_MACHINE,
        ]);

        if (! $result) {
            throw new \RuntimeException(sprintf('State "%s" not found for state machine "%s"', $technicalName, PendingOrderStates::STATE_MACHINE));
        }

        return $result;
    }
}

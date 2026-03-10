<?php

namespace Algoritma\ShopwareTestUtils\Traits;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

/**
 * Trait for state machine operations in tests.
 */
trait StateMachineTrait
{
    use KernelTestBehaviour;

    /**
     * Transitions an order to a specific state.
     */
    protected function transitionOrderState(string $orderId, string $toState, ?Context $context = null): void
    {
        $context = $context ?? Context::createCLIContext();

        $this->getStateMachineRegistry()->transition(
            new Transition(
                'order',
                $orderId,
                $toState,
                'stateId'
            ),
            $context
        );
    }

    /**
     * Transitions an order transaction to a specific state.
     */
    protected function transitionPaymentState(string $transactionId, string $toState, ?Context $context = null): void
    {
        $context = $context ?? Context::createCLIContext();

        $this->getStateMachineRegistry()->transition(
            new Transition(
                'order_transaction',
                $transactionId,
                $toState,
                'stateId'
            ),
            $context
        );
    }

    /**
     * Transitions an order delivery to a specific state.
     */
    protected function transitionDeliveryState(string $deliveryId, string $toState, ?Context $context = null): void
    {
        $context = $context ?? Context::createCLIContext();

        $this->getStateMachineRegistry()->transition(
            new Transition(
                'order_delivery',
                $deliveryId,
                $toState,
                'stateId'
            ),
            $context
        );
    }

    /**
     * Gets all available transitions for an entity in a state machine.
     *
     * @return array<int, StateMachineTransitionEntity>
     */
    protected function getAvailableTransitions(string $entityId, string $stateMachineName, ?Context $context = null): array
    {
        $context = $context ?? Context::createCLIContext();

        return $this->getStateMachineRegistry()->getAvailableTransitions(
            $stateMachineName,
            $entityId,
            'stateId',
            $context
        );
    }

    /**
     * Gets the current state of an entity.
     */
    protected function getEntityState(string $entityName, string $entityId, ?Context $context = null): ?string
    {
        $context = $context ?? Context::createCLIContext();

        /** @var EntityRepository<EntityCollection<Entity>> $repository */
        $repository = static::getContainer()->get($entityName . '.repository');
        $criteria = new Criteria([$entityId]);
        $criteria->addAssociation('stateMachineState');

        $entity = $repository->search($criteria, $context)->first();

        if (! $entity instanceof Entity) {
            return null;
        }

        $stateMachineState = $entity->get('stateMachineState');

        if (! $stateMachineState instanceof StateMachineStateEntity) {
            return null;
        }

        return $stateMachineState->getTechnicalName();
    }

    /**
     * Forces an entity to a specific state (bypassing normal transitions).
     */
    protected function forceEntityState(string $entityName, string $entityId, string $stateId, ?Context $context = null): void
    {
        $context = $context ?? Context::createCLIContext();

        $repository = static::getContainer()->get($entityName . '.repository');
        $repository->update([
            [
                'id' => $entityId,
                'stateId' => $stateId,
            ],
        ], $context);
    }

    /**
     * Gets the state ID for a given state machine and state name.
     */
    protected function getStateMachineStateId(string $stateMachineName, string $stateName, ?Context $context = null): ?string
    {
        $connection = static::getContainer()->get(Connection::class);

        $sql = <<<'EOD'
            SELECT LOWER(HEX(state_machine_state.id))
            FROM state_machine_state
            JOIN state_machine ON state_machine.id = state_machine_state.state_machine_id
            WHERE state_machine.technical_name = :machine AND state_machine_state.technical_name = :state
EOD;

        return $connection->fetchOne($sql, ['machine' => $stateMachineName, 'state' => $stateName]) ?: null;
    }

    /**
     * Transitions order through multiple states sequentially.
     *
     * @param array<int, string> $states
     */
    protected function transitionOrderThroughStates(string $orderId, array $states, ?Context $context = null): void
    {
        $context = $context ?? Context::createCLIContext();

        foreach ($states as $state) {
            $this->transitionOrderState($orderId, $state, $context);
        }
    }

    private function getStateMachineRegistry(): StateMachineRegistry
    {
        return static::getContainer()->get(StateMachineRegistry::class);
    }
}

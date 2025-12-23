<?php

namespace Algoritma\ShopwareTestUtils\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StateManager
{
    private readonly StateMachineRegistry $stateMachineRegistry;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->stateMachineRegistry = $this->container->get(StateMachineRegistry::class);
    }

    /**
     * Transitions an order to a specific state.
     */
    public function transitionOrderState(string $orderId, string $toState, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $this->stateMachineRegistry->transition(
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
    public function transitionPaymentState(string $transactionId, string $toState, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $this->stateMachineRegistry->transition(
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
    public function transitionDeliveryState(string $deliveryId, string $toState, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $this->stateMachineRegistry->transition(
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
     */
    public function getAvailableTransitions(string $entityId, string $stateMachineName, ?Context $context = null): array
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        return $this->stateMachineRegistry->getAvailableTransitions(
            $stateMachineName,
            $entityId,
            'stateId',
            $context
        );
    }

    /**
     * Gets the current state of an entity.
     */
    public function getCurrentState(string $entityName, string $entityId, ?Context $context = null): ?string
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $repository = $this->container->get($entityName . '.repository');
        $criteria = new Criteria([$entityId]);
        $criteria->addAssociation('stateMachineState');

        $entity = $repository->search($criteria, $context)->first();

        if (! $entity) {
            return null;
        }

        $stateMachineState = $entity->getStateMachineState();

        return $stateMachineState ? $stateMachineState->getTechnicalName() : null;
    }

    /**
     * Forces an entity to a specific state (bypassing normal transitions).
     * Useful for testing edge cases.
     */
    public function forceState(string $entityName, string $entityId, string $stateId, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $repository = $this->container->get($entityName . '.repository');
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
    public function getStateId(string $stateMachineName, string $stateName, ?Context $context = null): ?string
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $connection = $this->container->get(Connection::class);

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
     */
    public function transitionOrderThroughStates(string $orderId, array $states, ?Context $context = null): void
    {
        if (! $context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        foreach ($states as $state) {
            $this->transitionOrderState($orderId, $state, $context);
        }
    }
}

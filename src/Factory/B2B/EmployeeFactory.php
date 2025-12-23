<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    private readonly Generator $faker;

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'shopware',
            'active' => true,
        ];
    }

    public function withName(string $firstName, string $lastName): self
    {
        $this->data['firstName'] = $firstName;
        $this->data['lastName'] = $lastName;

        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->data['email'] = $email;

        return $this;
    }

    public function withBusinessPartner(string $customerId): self
    {
        $this->data['businessPartnerCustomerId'] = $customerId;

        return $this;
    }

    public function withRole(string $roleId): self
    {
        $this->data['roleId'] = $roleId;

        return $this;
    }

    public function create(?Context $context = null): EmployeeEntity
    {
        if (! $context instanceof Context) {
            $context = Context::createCLIContext();
        }

        /** @var EntityRepository<EmployeeEntity> $repository */
        $repository = $this->container->get('b2b_employee.repository');

        $repository->create([$this->data], $context);

        /** @var EmployeeEntity $entity */
        $entity = $repository->search(new Criteria([$this->data['id']]), $context)->first();

        return $entity;
    }
}

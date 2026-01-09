<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Faker\Factory;
use Faker\Generator;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeDefinition;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeStatus;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeFactory extends AbstractFactory
{
    private readonly Generator $faker;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->faker = Factory::create();

        $this->data = [
            'id' => Uuid::randomHex(),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => TestDefaults::HASHED_PASSWORD,
            'active' => true,
            'status' => EmployeeStatus::ACTIVE->value,
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

    protected function getRepositoryName(): string
    {
        return 'b2b_employee.repository';
    }

    protected function getEntityName(): string
    {
        return EmployeeDefinition::ENTITY_NAME;
    }

    protected function getRepository(): EntityRepository
    {
        return $this->container->get($this->getRepositoryName());
    }
}

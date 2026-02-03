<?php

namespace Algoritma\ShopwareTestUtils\Factory\B2B;

use Algoritma\ShopwareTestUtils\Factory\AbstractFactory;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeCollection;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeDefinition;
use Shopware\Commercial\B2B\EmployeeManagement\Entity\Employee\EmployeeStatus;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;

class EmployeeFactory extends AbstractFactory
{
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

    protected function getDefaults(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => TestDefaults::HASHED_PASSWORD,
            'active' => true,
            'status' => EmployeeStatus::ACTIVE->value,
        ];
    }

    /**
     * @return EntityRepository<EmployeeCollection>
     */
    protected function getRepository(): EntityRepository
    {
        return $this->container->get($this->getRepositoryName());
    }
}

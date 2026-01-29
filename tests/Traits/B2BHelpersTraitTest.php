<?php

namespace Algoritma\ShopwareTestUtils\Tests\Traits;

use Algoritma\ShopwareTestUtils\Helper\B2B\ApprovalWorkflowHelper;
use Algoritma\ShopwareTestUtils\Helper\B2B\EmployeeLoginHelper;
use Algoritma\ShopwareTestUtils\Traits\B2B\B2BHelpersTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Commercial\B2B\OrderApproval\Entity\PendingOrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class B2BHelpersTraitTest extends TestCase
{
    public function testTraitDelegatesToB2bHelpers(): void
    {
        $loginHelper = $this->createMock(EmployeeLoginHelper::class);
        $approvalHelper = $this->createMock(ApprovalWorkflowHelper::class);

        $salesChannelContext = $this->createStub(SalesChannelContext::class);
        $pendingOrder = $this->createStub(PendingOrderEntity::class);

        $loginHelper->expects($this->once())
            ->method('loginByEmail')
            ->with('employee@test.com', null)
            ->willReturn($salesChannelContext);

        $approvalHelper->expects($this->once())
            ->method('approvePendingOrder')
            ->with('pending-id', null)
            ->willReturn($pendingOrder);

        $subject = new class($loginHelper, $approvalHelper) {
            use B2BHelpersTrait;

            public function __construct(
                private EmployeeLoginHelper $loginHelper,
                private ApprovalWorkflowHelper $approvalHelper
            ) {}

            protected function getB2bEmployeeLoginHelper(): EmployeeLoginHelper
            {
                return $this->loginHelper;
            }

            protected function getB2bApprovalWorkflowHelper(): ApprovalWorkflowHelper
            {
                return $this->approvalHelper;
            }

            public function callLoginByEmail(string $email): SalesChannelContext
            {
                return $this->b2bEmployeeLoginByEmail($email);
            }

            public function callApprovePendingOrder(string $pendingOrderId): PendingOrderEntity
            {
                return $this->b2bApprovalApprovePendingOrder($pendingOrderId);
            }
        };

        $this->assertSame($salesChannelContext, $subject->callLoginByEmail('employee@test.com'));
        $this->assertSame($pendingOrder, $subject->callApprovePendingOrder('pending-id'));
    }
}

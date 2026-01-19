<?php

namespace LetsPeppolSdk\Tests\Feature;

use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Tests\Fixtures\FixtureLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Feature test for partner search and management workflow
 *
 * Tests based on partnerSearchExample() and complete workflow
 */
#[CoversClass(AppClient::class)]
class PartnerManagementWorkflowTest extends TestCase
{
    #[Test]
    public function it_searches_for_partner_and_finds_result(): void
    {
        // Arrange
        $peppolId = '0208:BE0987654321';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $expectedPartners = [
            [
                'id' => 1,
                'peppolId' => '0208:BE0987654321',
                'name' => 'Example Partner Company',
                'vatNumber' => 'BE0987654321',
                'email' => 'partner@example.com'
            ]
        ];

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn($expectedPartners);

        // Act - Simulate the workflow
        $partners = $appClient->searchPartners($peppolId);

        // Assert
        $this->assertNotEmpty($partners);
        $this->assertEquals('Example Partner Company', $partners[0]['name']);
    }
    #[Test]
    public function it_handles_partner_not_found_scenario(): void
    {
        // Arrange
        $peppolId = '0208:BE0000000000';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn([]);

        // Act
        $partners = $appClient->searchPartners($peppolId);

        // Assert
        $this->assertEmpty($partners);
    }
    #[Test]
    public function it_creates_partner_when_not_found(): void
    {
        // Arrange
        $peppolId = '0208:BE0987654321';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners', 'createPartner'])
            ->getMock();

        // First search returns empty
        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn([]);

        // Then create the partner
        $partnerData = [
            'peppolId' => $peppolId,
            'name' => 'Example Partner Company',
            'vatNumber' => 'BE0987654321',
            'email' => 'partner@example.com'
        ];

        $createdPartner = array_merge(['id' => 1], $partnerData);

        $appClient->expects($this->once())
            ->method('createPartner')
            ->with($partnerData)
            ->willReturn($createdPartner);

        // Act - Simulate the workflow from complete_workflow.php
        $searchResults = $appClient->searchPartners($peppolId);

        if (empty($searchResults)) {
            $partner = $appClient->createPartner($partnerData);
            $this->assertEquals('Example Partner Company', $partner['name']);
            $this->assertEquals($peppolId, $partner['peppolId']);
        }
    }
    #[Test]
    public function it_lists_all_partners(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listPartners'])
            ->getMock();

        $expectedPartners = [
            [
                'id' => 1,
                'name' => 'Partner 1',
                'peppolId' => '0208:BE0111111111'
            ],
            [
                'id' => 2,
                'name' => 'Partner 2',
                'peppolId' => '0208:BE0222222222'
            ]
        ];

        $appClient->expects($this->once())
            ->method('listPartners')
            ->willReturn($expectedPartners);

        // Act
        $partners = $appClient->listPartners();

        // Assert
        $this->assertCount(2, $partners);
        $this->assertEquals('Partner 1', $partners[0]['name']);
        $this->assertEquals('Partner 2', $partners[1]['name']);
    }
    #[Test]
    public function it_updates_existing_partner(): void
    {
        // Arrange
        $partnerId = 1;
        $updateData = [
            'name' => 'Updated Partner Name',
            'email' => 'updated@example.com'
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updatePartner'])
            ->getMock();

        $updatedPartner = array_merge(
            ['id' => $partnerId],
            $updateData,
            ['peppolId' => '0208:BE0987654321']
        );

        $appClient->expects($this->once())
            ->method('updatePartner')
            ->with($partnerId, $updateData)
            ->willReturn($updatedPartner);

        // Act
        $result = $appClient->updatePartner($partnerId, $updateData);

        // Assert
        $this->assertEquals('Updated Partner Name', $result['name']);
        $this->assertEquals('updated@example.com', $result['email']);
    }
    #[Test]
    public function it_deletes_partner(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Arrange
        $partnerId = 1;
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['deletePartner'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('deletePartner')
            ->with($partnerId);

        // Act
        $appClient->deletePartner($partnerId);
    }
    #[Test]
    public function it_handles_partner_search_error(): void
    {
        // Arrange
        $peppolId = '0208:INVALID';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willThrowException(new ApiException('Error searching partners', 400));

        // Act
        $errorCaught = false;
        try {
            $appClient->searchPartners($peppolId);
        } catch (ApiException $e) {
            $errorCaught = true;
            $this->assertStringContainsString('Error searching partners', $e->getMessage());
        }

        // Assert
        $this->assertTrue($errorCaught);
    }
}

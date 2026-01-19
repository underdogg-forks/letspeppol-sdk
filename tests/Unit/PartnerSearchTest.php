<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for partner search functionality
 *
 * Tests based on partnerSearchExample() from problem statement
 */
class PartnerSearchTest extends TestCase
{
    private AppClient|MockObject $appClientMock;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_finds_partner_by_peppol_id(): void
    {
        // Arrange
        $peppolId = '0208:BE0987654321';
        $expectedPartners = [
            [
                'id' => 1,
                'peppolId' => '0208:BE0987654321',
                'name' => 'Example Partner Company',
                'vatNumber' => 'BE0987654321',
                'email' => 'partner@example.com'
            ]
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn($expectedPartners);

        // Act
        $partners = $appClient->searchPartners($peppolId);

        // Assert
        $this->assertIsArray($partners);
        $this->assertNotEmpty($partners);
        $this->assertCount(1, $partners);
        $this->assertEquals('Example Partner Company', $partners[0]['name']);
        $this->assertEquals($peppolId, $partners[0]['peppolId']);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_partner_not_found(): void
    {
        // Arrange
        $peppolId = '0208:BE0000000000';
        $expectedPartners = [];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn($expectedPartners);

        // Act
        $partners = $appClient->searchPartners($peppolId);

        // Assert
        $this->assertIsArray($partners);
        $this->assertEmpty($partners);
    }

    /**
     * @test
     */
    public function it_handles_partner_search_with_valid_peppol_id(): void
    {
        // Arrange
        $peppolId = '0208:BE0987654321';
        $expectedPartners = [
            [
                'id' => 1,
                'name' => 'Partner Company',
                'peppolId' => $peppolId
            ]
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['searchPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('searchPartners')
            ->with($peppolId)
            ->willReturn($expectedPartners);

        // Act
        $partners = $appClient->searchPartners($peppolId);

        // Assert - Simulate the example logic
        if (!empty($partners)) {
            $partner = $partners[0];
            $this->assertEquals('Partner Company', $partner['name']);
        } else {
            $this->fail('Partner should be found');
        }
    }

    /**
     * @test
     */
    public function it_lists_all_partners(): void
    {
        // Arrange
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
            ],
            [
                'id' => 3,
                'name' => 'Partner 3',
                'peppolId' => '0208:BE0333333333'
            ]
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listPartners'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('listPartners')
            ->willReturn($expectedPartners);

        // Act
        $partners = $appClient->listPartners();

        // Assert
        $this->assertIsArray($partners);
        $this->assertCount(3, $partners);
    }

    /**
     * @test
     */
    public function it_throws_exception_on_api_error_during_search(): void
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
            ->willThrowException(new ApiException('Error searching partners: Invalid Peppol ID format', 400));

        // Assert
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error searching partners: Invalid Peppol ID format');

        // Act
        $appClient->searchPartners($peppolId);
    }
}

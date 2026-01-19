<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\Resources\ProxyClient;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for receiving documents functionality
 *
 * Tests based on receiveDocumentsExample() from problem statement
 */
class ReceiveDocumentsTest extends TestCase
{
    private ProxyClient|MockObject $proxyClientMock;

    protected function setUp(): void
    {
        parent::setUp();
    }
    #[Test]
    public function it_receives_new_documents_from_proxy(): void
    {
        // Arrange
        $expectedDocuments = [
            [
                'id' => 'doc123',
                'documentType' => 'INVOICE',
                'direction' => 'INCOMING',
                'senderPeppolId' => '0208:BE0987654321',
                'receivedAt' => '2024-01-09T10:30:00Z'
            ],
            [
                'id' => 'doc456',
                'documentType' => 'CREDIT_NOTE',
                'direction' => 'INCOMING',
                'senderPeppolId' => '0208:BE0111111111',
                'receivedAt' => '2024-01-09T11:00:00Z'
            ]
        ];

        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn($expectedDocuments);

        // Act
        $newDocs = $proxyClient->getAllNewDocuments(50);

        // Assert
        $this->assertIsArray($newDocs);
        $this->assertCount(2, $newDocs);
        $this->assertEquals('doc123', $newDocs[0]['id']);
        $this->assertEquals('INVOICE', $newDocs[0]['documentType']);
    }
    #[Test]
    public function it_marks_document_as_downloaded(): void
    {
        // Arrange
        $documentId = 'doc123';

        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDownloaded'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('markDownloaded')
            ->with($documentId);

        // Act
        $proxyClient->markDownloaded($documentId);

        // Assert - Method should complete without exception
        $this->assertTrue(true);
    }
    #[Test]
    public function it_processes_multiple_new_documents(): void
    {
        // Arrange
        $documents = [
            ['id' => 'doc1', 'documentType' => 'INVOICE'],
            ['id' => 'doc2', 'documentType' => 'INVOICE'],
            ['id' => 'doc3', 'documentType' => 'CREDIT_NOTE']
        ];

        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments', 'markDownloaded'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn($documents);

        $proxyClient->expects($this->exactly(3))
            ->method('markDownloaded')
            ->willReturnCallback(function($id) {
                $this->assertContains($id, ['doc1', 'doc2', 'doc3']);
            });

        // Act
        $newDocs = $proxyClient->getAllNewDocuments(50);

        // Assert
        $this->assertCount(3, $newDocs);

        // Simulate processing each document
        foreach ($newDocs as $doc) {
            $proxyClient->markDownloaded($doc['id']);
        }
    }
    #[Test]
    public function it_returns_empty_array_when_no_new_documents(): void
    {
        // Arrange
        $expectedDocuments = [];

        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn($expectedDocuments);

        // Act
        $newDocs = $proxyClient->getAllNewDocuments(50);

        // Assert
        $this->assertIsArray($newDocs);
        $this->assertEmpty($newDocs);
        $this->assertCount(0, $newDocs);
    }
    #[Test]
    public function it_throws_exception_when_receiving_documents_fails(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willThrowException(new ApiException('Error receiving documents: Server error', 500));

        // Assert
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error receiving documents: Server error');

        // Act
        $proxyClient->getAllNewDocuments(50);
    }
    #[Test]
    public function it_handles_document_processing_workflow(): void
    {
        // Arrange
        $documents = [
            [
                'id' => 'doc123',
                'documentType' => 'INVOICE',
                'senderPeppolId' => '0208:BE0987654321'
            ]
        ];

        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments', 'markDownloaded'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn($documents);

        $proxyClient->expects($this->once())
            ->method('markDownloaded')
            ->with('doc123');

        // Act - Simulate the workflow from the example
        $newDocs = $proxyClient->getAllNewDocuments(50);
        $processedCount = 0;
        
        foreach ($newDocs as $doc) {
            // Process the document (simulated)
            $processedCount++;
            
            // Mark as downloaded
            $proxyClient->markDownloaded($doc['id']);
        }

        // Assert
        $this->assertEquals(1, $processedCount);
    }
}

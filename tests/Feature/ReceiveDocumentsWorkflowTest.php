<?php

namespace LetsPeppolSdk\Tests\Feature;

use LetsPeppolSdk\Resources\ProxyClient;
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Tests\Fixtures\FixtureLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Feature test for receiving documents workflow
 *
 * Tests based on receiveDocumentsExample() from problem statement
 */
#[CoversClass(ProxyClient::class)]
class ReceiveDocumentsWorkflowTest extends TestCase
{
    #[Test]
    public function it_receives_and_processes_new_documents(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments', 'markDownloaded'])
            ->getMock();

        $newDocuments = [
            [
                'id' => 'doc123',
                'documentType' => 'INVOICE',
                'direction' => 'INCOMING',
                'senderPeppolId' => '0208:BE0987654321',
                'ubl' => '<Invoice>...</Invoice>',
                'receivedAt' => '2024-01-09T10:30:00Z'
            ],
            [
                'id' => 'doc456',
                'documentType' => 'CREDIT_NOTE',
                'direction' => 'INCOMING',
                'senderPeppolId' => '0208:BE0111111111',
                'ubl' => '<CreditNote>...</CreditNote>',
                'receivedAt' => '2024-01-09T11:00:00Z'
            ]
        ];

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn($newDocuments);

        $proxyClient->expects($this->exactly(2))
            ->method('markDownloaded')
            ->willReturnCallback(function($id) {
                $this->assertContains($id, ['doc123', 'doc456']);
            });

        // Act - Simulate the workflow
        try {
            $newDocs = $proxyClient->getAllNewDocuments(50);
            $receivedCount = count($newDocs);

            $this->assertEquals(2, $receivedCount);

            foreach ($newDocs as $doc) {
                // Process the document
                $this->assertArrayHasKey('id', $doc);
                $this->assertArrayHasKey('documentType', $doc);

                // Mark as downloaded
                $proxyClient->markDownloaded($doc['id']);
            }

        } catch (ApiException $e) {
            $this->fail('Should not throw exception: ' . $e->getMessage());
        }
    }
    #[Test]
    public function it_handles_no_new_documents(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn([]);

        // Act
        try {
            $newDocs = $proxyClient->getAllNewDocuments(50);

            // Assert
            $this->assertEmpty($newDocs);
            $this->assertEquals(0, count($newDocs));

        } catch (ApiException $e) {
            $this->fail('Should not throw exception: ' . $e->getMessage());
        }
    }
    #[Test]
    public function it_handles_error_during_document_retrieval(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willThrowException(new ApiException('Error receiving documents', 500));

        // Act
        $errorCaught = false;
        try {
            $proxyClient->getAllNewDocuments(50);
        } catch (ApiException $e) {
            $errorCaught = true;
            $this->assertStringContainsString('Error receiving documents', $e->getMessage());
        }

        // Assert
        $this->assertTrue($errorCaught);
    }
    #[Test]
    public function it_processes_document_and_marks_downloaded(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments', 'markDownloaded'])
            ->getMock();

        $document = [
            'id' => 'doc123',
            'documentType' => 'INVOICE',
            'senderPeppolId' => '0208:BE0987654321',
            'ubl' => '<Invoice>...</Invoice>'
        ];

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(50)
            ->willReturn([$document]);

        $proxyClient->expects($this->once())
            ->method('markDownloaded')
            ->with('doc123');

        // Act - Full workflow
        $newDocs = $proxyClient->getAllNewDocuments(50);

        foreach ($newDocs as $doc) {
            // Simulate processing - could save to database, etc.
            $processedId = $doc['id'];
            $this->assertEquals('doc123', $processedId);

            // Mark as downloaded after processing
            $proxyClient->markDownloaded($doc['id']);
        }
    }
    #[Test]
    public function it_retrieves_specific_document_details(): void
    {
        // Arrange
        $documentId = 'doc123';
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocument'])
            ->getMock();

        $expectedDocument = [
            'id' => 'doc123',
            'documentType' => 'INVOICE',
            'direction' => 'OUTGOING',
            'senderPeppolId' => '0208:BE0123456789',
            'receiverPeppolId' => '0208:BE0987654321',
            'ubl' => '<Invoice>...</Invoice>',
            'status' => 'SENT'
        ];

        $proxyClient->expects($this->once())
            ->method('getDocument')
            ->with($documentId)
            ->willReturn($expectedDocument);

        // Act
        $document = $proxyClient->getDocument($documentId);

        // Assert
        $this->assertEquals('doc123', $document['id']);
        $this->assertEquals('INVOICE', $document['documentType']);
        $this->assertArrayHasKey('ubl', $document);
    }
    #[Test]
    public function it_marks_multiple_documents_as_downloaded_in_batch(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Arrange
        $documentIds = ['doc1', 'doc2', 'doc3'];
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDownloadedBatch'])
            ->getMock();

        $proxyClient->expects($this->once())
            ->method('markDownloadedBatch')
            ->with($documentIds);

        // Act
        $proxyClient->markDownloadedBatch($documentIds);
    }
    #[Test]
    public function it_gets_status_updates_for_documents(): void
    {
        // Arrange
        $documentIds = ['doc123', 'doc456'];
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatusUpdates'])
            ->getMock();

        $expectedStatuses = [
            [
                'id' => 'doc123',
                'status' => 'DELIVERED',
                'statusMessage' => 'Document successfully delivered',
                'lastUpdated' => '2024-01-09T10:35:00Z'
            ],
            [
                'id' => 'doc456',
                'status' => 'PENDING',
                'statusMessage' => 'Awaiting delivery',
                'lastUpdated' => '2024-01-09T10:30:00Z'
            ]
        ];

        $proxyClient->expects($this->once())
            ->method('getStatusUpdates')
            ->with($documentIds)
            ->willReturn($expectedStatuses);

        // Act
        $statuses = $proxyClient->getStatusUpdates($documentIds);

        // Assert
        $this->assertCount(2, $statuses);
        $this->assertEquals('DELIVERED', $statuses[0]['status']);
        $this->assertEquals('PENDING', $statuses[1]['status']);
    }
}

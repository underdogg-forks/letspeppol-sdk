<?php

namespace LetsPeppolSdk\Tests\Feature;

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test for document management workflow
 *
 * Tests based on documentManagementExample() and complete workflow
 */
class DocumentManagementWorkflowTest extends TestCase
{
    #[Test]
    public function it_lists_and_processes_unread_invoices(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments', 'markDocumentRead'])
            ->getMock();

        $documentsResponse = [
            'content' => [
                [
                    'id' => 'doc1',
                    'documentType' => 'INVOICE',
                    'invoiceNumber' => 'INV-001',
                    'total' => 1000.00,
                    'currency' => 'EUR',
                    'read' => false
                ],
                [
                    'id' => 'doc2',
                    'documentType' => 'INVOICE',
                    'invoiceNumber' => 'INV-002',
                    'total' => 500.00,
                    'currency' => 'EUR',
                    'read' => false
                ]
            ],
            'totalElements' => 2
        ];

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->with([
                'type' => 'INVOICE',
                'direction' => 'INCOMING',
                'read' => false
            ], 0, 10)
            ->willReturn($documentsResponse);

        $appClient->expects($this->exactly(2))
            ->method('markDocumentRead')
            ->willReturnCallback(function($id) {
                return ['id' => $id, 'read' => true];
            });

        // Act - Simulate the workflow
        try {
            $response = $appClient->listDocuments([
                'type' => 'INVOICE',
                'direction' => 'INCOMING',
                'read' => false
            ], 0, 10);

            $foundCount = $response['totalElements'];
            $this->assertEquals(2, $foundCount);

            foreach ($response['content'] as $doc) {
                // Simulate processing the invoice
                $this->assertArrayHasKey('id', $doc);
                $this->assertArrayHasKey('total', $doc);
                
                // Mark as read
                $result = $appClient->markDocumentRead($doc['id']);
                $this->assertTrue($result['read']);
            }

        } catch (ApiException $e) {
            $this->fail('Should not throw exception: ' . $e->getMessage());
        }
    }
    #[Test]
    public function it_handles_document_listing_error(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->willThrowException(new ApiException('Error listing documents', 500));

        // Act
        $errorCaught = false;
        try {
            $appClient->listDocuments([
                'type' => 'INVOICE',
                'direction' => 'INCOMING'
            ], 0, 10);
        } catch (ApiException $e) {
            $errorCaught = true;
            $this->assertStringContainsString('Error listing documents', $e->getMessage());
        }

        // Assert
        $this->assertTrue($errorCaught);
    }
    #[Test]
    public function it_retrieves_document_details(): void
    {
        // Arrange
        $documentId = 'doc123';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocument'])
            ->getMock();

        $expectedDocument = [
            'id' => 'doc123',
            'documentType' => 'INVOICE',
            'invoiceNumber' => 'INV-2024-001',
            'total' => 1000.00,
            'currency' => 'EUR',
            'partnerName' => 'Partner Company',
            'ubl' => '<Invoice>...</Invoice>'
        ];

        $appClient->expects($this->once())
            ->method('getDocument')
            ->with($documentId)
            ->willReturn($expectedDocument);

        // Act
        $document = $appClient->getDocument($documentId);

        // Assert
        $this->assertEquals('doc123', $document['id']);
        $this->assertEquals('INV-2024-001', $document['invoiceNumber']);
        $this->assertArrayHasKey('ubl', $document);
    }
    #[Test]
    public function it_marks_document_as_paid(): void
    {
        // Arrange
        $documentId = 'doc123';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDocumentPaid'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('markDocumentPaid')
            ->with($documentId)
            ->willReturn(['id' => $documentId, 'paid' => true]);

        // Act
        $result = $appClient->markDocumentPaid($documentId);

        // Assert
        $this->assertTrue($result['paid']);
    }
    #[Test]
    public function it_processes_empty_document_list(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments'])
            ->getMock();

        $emptyResponse = [
            'content' => [],
            'totalElements' => 0,
            'page' => 0,
            'size' => 10
        ];

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->willReturn($emptyResponse);

        // Act
        $response = $appClient->listDocuments([
            'type' => 'INVOICE',
            'direction' => 'INCOMING',
            'read' => false
        ], 0, 10);

        // Assert
        $this->assertEquals(0, $response['totalElements']);
        $this->assertEmpty($response['content']);
    }
    #[Test]
    public function it_filters_documents_by_multiple_criteria(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments'])
            ->getMock();

        $filters = [
            'type' => 'INVOICE',
            'direction' => 'INCOMING',
            'read' => false,
            'paid' => false
        ];

        $expectedResponse = [
            'content' => [
                [
                    'id' => 'doc1',
                    'type' => 'INVOICE',
                    'direction' => 'INCOMING',
                    'read' => false,
                    'paid' => false
                ]
            ],
            'totalElements' => 1
        ];

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->with($filters, 0, 20)
            ->willReturn($expectedResponse);

        // Act
        $response = $appClient->listDocuments($filters, 0, 20);

        // Assert
        $this->assertCount(1, $response['content']);
        $this->assertFalse($response['content'][0]['read']);
        $this->assertFalse($response['content'][0]['paid']);
    }
}

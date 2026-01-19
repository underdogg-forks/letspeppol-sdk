<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Exceptions\ApiException;
use LetsPeppolSdk\Tests\Fixtures\FixtureLoader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit test for document management functionality
 *
 * Tests based on documentManagementExample() from problem statement
 */
#[CoversClass(AppClient::class)]
class DocumentManagementTest extends TestCase
{
    #[Test]
    public function it_lists_documents_with_filters(): void
    {
        // Arrange
        $filters = [
            'type' => 'INVOICE',
            'direction' => 'INCOMING',
            'read' => false
        ];

        $expectedResponse = FixtureLoader::load('documents', 'list_response');

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->with($filters, 0, 10)
            ->willReturn($expectedResponse);

        // Act
        $response = $appClient->listDocuments($filters, 0, 10);

        // Assert
        $this->assertIsArray($response);
        $this->assertArrayHasKey('content', $response);
        $this->assertArrayHasKey('totalElements', $response);
        $this->assertEquals(2, $response['totalElements']);
        $this->assertCount(2, $response['content']);
    }
    #[Test]
    public function it_marks_document_as_read(): void
    {
        // Arrange
        $documentId = 'doc123';
        $expectedResponse = [
            'id' => 'doc123',
            'read' => true
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDocumentRead'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('markDocumentRead')
            ->with($documentId)
            ->willReturn($expectedResponse);

        // Act
        $response = $appClient->markDocumentRead($documentId);

        // Assert
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('read', $response);
        $this->assertTrue($response['read']);
    }
    #[Test]
    public function it_lists_unread_invoices(): void
    {
        // Arrange
        $filters = [
            'type' => 'INVOICE',
            'direction' => 'INCOMING',
            'read' => false
        ];

        $expectedResponse = [
            'content' => [
                ['id' => 'doc1', 'total' => 100, 'currency' => 'EUR'],
                ['id' => 'doc2', 'total' => 200, 'currency' => 'EUR'],
                ['id' => 'doc3', 'total' => 300, 'currency' => 'EUR']
            ],
            'totalElements' => 3
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDocuments'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('listDocuments')
            ->with($filters, 0, 10)
            ->willReturn($expectedResponse);

        // Act
        $response = $appClient->listDocuments($filters, 0, 10);

        // Assert
        $this->assertEquals(3, $response['totalElements']);
        $this->assertCount(3, $response['content']);
    }
    #[Test]
    public function it_processes_and_marks_documents_as_read(): void
    {
        // Arrange
        $documents = [
            ['id' => 'doc1', 'total' => 100],
            ['id' => 'doc2', 'total' => 200]
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDocumentRead'])
            ->getMock();

        $appClient->expects($this->exactly(2))
            ->method('markDocumentRead')
            ->willReturnCallback(function($id) {
                return ['id' => $id, 'read' => true];
            });

        // Act & Assert
        foreach ($documents as $doc) {
            $result = $appClient->markDocumentRead($doc['id']);
            $this->assertTrue($result['read']);
        }
    }
    #[Test]
    public function it_throws_exception_when_document_not_found(): void
    {
        // Arrange
        $documentId = 'nonexistent';

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markDocumentRead'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('markDocumentRead')
            ->with($documentId)
            ->willThrowException(new ApiException('Document not found', 404));

        // Assert
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Document not found');
        $this->expectExceptionCode(404);

        // Act
        $appClient->markDocumentRead($documentId);
    }
}

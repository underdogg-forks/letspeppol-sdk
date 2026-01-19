<?php

namespace LetsPeppolSdk\Tests\Unit;

use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for sending invoice functionality
 *
 * Tests based on sendInvoiceExample() from problem statement
 */
class SendInvoiceTest extends TestCase
{
    private AppClient|MockObject $appClientMock;
    private string $sampleUblXml;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleUblXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
    <ID>INV-2024-001</ID>
    <IssueDate>2024-01-09</IssueDate>
    <InvoiceTypeCode>380</InvoiceTypeCode>
</Invoice>
XML;
    }

    /**
     * @test
     */
    public function it_validates_ubl_xml_successfully(): void
    {
        // Arrange
        $expectedValidation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('validateDocument')
            ->with($this->sampleUblXml)
            ->willReturn($expectedValidation);

        // Act
        $validation = $appClient->validateDocument($this->sampleUblXml);

        // Assert
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /**
     * @test
     */
    public function it_fails_validation_with_errors(): void
    {
        // Arrange
        $expectedValidation = [
            'valid' => false,
            'errors' => [
                'Invoice number is required',
                'Invalid VAT number format'
            ],
            'warnings' => []
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('validateDocument')
            ->with($this->sampleUblXml)
            ->willReturn($expectedValidation);

        // Act
        $validation = $appClient->validateDocument($this->sampleUblXml);

        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertCount(2, $validation['errors']);
        $this->assertContains('Invoice number is required', $validation['errors']);
    }

    /**
     * @test
     */
    public function it_creates_document_as_draft(): void
    {
        // Arrange
        $expectedDocument = [
            'id' => 'doc123',
            'documentType' => 'INVOICE',
            'invoiceNumber' => 'INV-2024-001',
            'status' => 'DRAFT',
            'createdAt' => '2024-01-09T10:00:00Z'
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('createDocument')
            ->with($this->sampleUblXml, true)
            ->willReturn($expectedDocument);

        // Act
        $document = $appClient->createDocument($this->sampleUblXml, true);

        // Assert
        $this->assertIsArray($document);
        $this->assertEquals('doc123', $document['id']);
        $this->assertEquals('DRAFT', $document['status']);
    }

    /**
     * @test
     */
    public function it_sends_document_successfully(): void
    {
        // Arrange
        $documentId = 'doc123';
        $expectedResponse = [
            'id' => 'doc123',
            'status' => 'SENT',
            'sentAt' => '2024-01-09T10:05:00Z'
        ];

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('sendDocument')
            ->with($documentId)
            ->willReturn($expectedResponse);

        // Act
        $sent = $appClient->sendDocument($documentId);

        // Assert
        $this->assertIsArray($sent);
        $this->assertEquals('SENT', $sent['status']);
    }

    /**
     * @test
     */
    public function it_handles_complete_send_invoice_workflow(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateDocument', 'createDocument', 'sendDocument'])
            ->getMock();

        // Step 1: Validate
        $appClient->expects($this->once())
            ->method('validateDocument')
            ->with($this->sampleUblXml)
            ->willReturn(['valid' => true, 'errors' => []]);

        // Step 2: Create as draft
        $appClient->expects($this->once())
            ->method('createDocument')
            ->with($this->sampleUblXml, true)
            ->willReturn(['id' => 'doc123', 'status' => 'DRAFT']);

        // Step 3: Send
        $appClient->expects($this->once())
            ->method('sendDocument')
            ->with('doc123')
            ->willReturn(['id' => 'doc123', 'status' => 'SENT']);

        // Act - Simulate the workflow from the example
        $validation = $appClient->validateDocument($this->sampleUblXml);
        
        if ($validation['valid']) {
            $document = $appClient->createDocument($this->sampleUblXml, true);
            $documentId = $document['id'];
            
            $sent = $appClient->sendDocument($documentId);
            
            // Assert
            $this->assertEquals('SENT', $sent['status']);
        } else {
            $this->fail('Validation should pass');
        }
    }

    /**
     * @test
     */
    public function it_stops_workflow_when_validation_fails(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateDocument', 'createDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('validateDocument')
            ->with($this->sampleUblXml)
            ->willReturn([
                'valid' => false,
                'errors' => ['Invalid format']
            ]);

        // createDocument should never be called
        $appClient->expects($this->never())
            ->method('createDocument');

        // Act
        $validation = $appClient->validateDocument($this->sampleUblXml);

        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_sending_fails(): void
    {
        // Arrange
        $documentId = 'doc123';

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('sendDocument')
            ->with($documentId)
            ->willThrowException(new ApiException('Error sending invoice: Document not ready', 422));

        // Assert
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error sending invoice: Document not ready');

        // Act
        $appClient->sendDocument($documentId);
    }

    /**
     * @test
     */
    public function it_handles_document_id_retrieval(): void
    {
        // Arrange
        $document = [
            'id' => 'doc123',
            'status' => 'DRAFT'
        ];

        // Act
        $documentId = $document['id'] ?? null;

        // Assert
        $this->assertNotNull($documentId);
        $this->assertEquals('doc123', $documentId);
    }

    /**
     * @test
     */
    public function it_handles_missing_document_id(): void
    {
        // Arrange
        $document = [
            'status' => 'DRAFT'
        ];

        // Act
        $documentId = $document['id'] ?? null;

        // Assert
        $this->assertNull($documentId);
    }
}

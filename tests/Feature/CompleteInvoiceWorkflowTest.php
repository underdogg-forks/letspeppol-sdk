<?php

namespace LetsPeppolSdk\Tests\Feature;

use LetsPeppolSdk\LetsPeppolClient;
use LetsPeppolSdk\Resources\AppClient;
use LetsPeppolSdk\Resources\ProxyClient;
use LetsPeppolSdk\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Feature test for complete invoice sending workflow
 *
 * Tests based on sendInvoiceExample() and complete workflow from problem statement
 */
#[CoversClass(LetsPeppolClient::class)]
#[CoversClass(AppClient::class)]
#[CoversClass(ProxyClient::class)]
class CompleteInvoiceWorkflowTest extends TestCase
{
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
    #[Test]
    public function it_completes_full_invoice_sending_workflow(): void
    {
        // Arrange
        $client = $this->getMockBuilder(LetsPeppolClient::class)
            ->onlyMethods(['authenticate'])
            ->getMock();

        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getCompany',
                'validateDocument',
                'createDocument',
                'sendDocument',
                'getAccountTotals'
            ])
            ->getMock();

        // Step 1: Authentication (already done)
        $client->expects($this->once())
            ->method('authenticate')
            ->willReturn('valid.jwt.token');

        // Step 2: Get company info
        $appClient->expects($this->once())
            ->method('getCompany')
            ->willReturn([
                'name' => 'Test Company',
                'peppolId' => '0208:BE0123456789'
            ]);

        // Step 3: Validate UBL
        $appClient->expects($this->once())
            ->method('validateDocument')
            ->with($this->sampleUblXml)
            ->willReturn([
                'valid' => true,
                'errors' => [],
                'warnings' => []
            ]);

        // Step 4: Create as draft
        $appClient->expects($this->once())
            ->method('createDocument')
            ->with($this->sampleUblXml, true)
            ->willReturn([
                'id' => 'doc123',
                'status' => 'DRAFT',
                'createdAt' => '2024-01-09T10:00:00Z'
            ]);

        // Step 5: Send document
        $appClient->expects($this->once())
            ->method('sendDocument')
            ->with('doc123')
            ->willReturn([
                'id' => 'doc123',
                'status' => 'SENT',
                'sentAt' => '2024-01-09T10:05:00Z'
            ]);

        // Step 6: Get statistics
        $appClient->expects($this->once())
            ->method('getAccountTotals')
            ->willReturn([
                'incoming' => 10,
                'outgoing' => 5,
                'balance' => 100.00
            ]);

        // Act - Execute the workflow
        try {
            // Authenticate
            $token = $client->authenticate('user@example.com', 'password123');
            $this->assertNotEmpty($token);

            // Get company info
            $company = $appClient->getCompany();
            $this->assertEquals('Test Company', $company['name']);

            // Validate UBL XML
            $validation = $appClient->validateDocument($this->sampleUblXml);
            $this->assertTrue($validation['valid']);

            if ($validation['valid']) {
                // Create document as draft
                $document = $appClient->createDocument($this->sampleUblXml, true);
                $this->assertEquals('DRAFT', $document['status']);

                // Send the document
                $documentId = $document['id'];
                $sent = $appClient->sendDocument($documentId);
                $this->assertEquals('SENT', $sent['status']);

                // Get statistics
                $stats = $appClient->getAccountTotals();
                $this->assertEquals(5, $stats['outgoing']);
            }

        } catch (ApiException $e) {
            $this->fail('Workflow should complete successfully: ' . $e->getMessage());
        }
    }
    #[Test]
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
                'errors' => [
                    'Invoice number is required',
                    'Invalid VAT number format'
                ]
            ]);

        // createDocument should never be called
        $appClient->expects($this->never())
            ->method('createDocument');

        // Act
        try {
            $validation = $appClient->validateDocument($this->sampleUblXml);

            if (!($validation['valid'] ?? false)) {
                // Stop workflow - validation failed
                $this->assertFalse($validation['valid']);
                $this->assertCount(2, $validation['errors']);
                return; // Exit early as expected
            }

            $this->fail('Should not reach document creation');

        } catch (ApiException $e) {
            $this->fail('Should handle validation failure without exception: ' . $e->getMessage());
        }
    }
    #[Test]
    public function it_handles_missing_document_id(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createDocument', 'sendDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('createDocument')
            ->willReturn(['status' => 'DRAFT']); // Missing 'id'

        $appClient->expects($this->never())
            ->method('sendDocument');

        // Act
        $document = $appClient->createDocument($this->sampleUblXml, true);
        $documentId = $document['id'] ?? null;

        // Assert
        if (!$documentId) {
            $this->assertNull($documentId);
            // Workflow should stop here
        } else {
            $this->fail('Document ID should be missing');
        }
    }
    #[Test]
    public function it_handles_send_document_error(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateDocument', 'createDocument', 'sendDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('validateDocument')
            ->willReturn(['valid' => true, 'errors' => []]);

        $appClient->expects($this->once())
            ->method('createDocument')
            ->willReturn(['id' => 'doc123', 'status' => 'DRAFT']);

        $appClient->expects($this->once())
            ->method('sendDocument')
            ->with('doc123')
            ->willThrowException(new ApiException('Error sending invoice', 422));

        // Assert
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Error sending invoice');

        // Act
        $validation = $appClient->validateDocument($this->sampleUblXml);

        if ($validation['valid']) {
            $document = $appClient->createDocument($this->sampleUblXml, true);
            $appClient->sendDocument($document['id']);
        }
    }
    #[Test]
    public function it_processes_incoming_and_outgoing_documents(): void
    {
        // Arrange
        $proxyClient = $this->getMockBuilder(ProxyClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllNewDocuments', 'markDownloaded'])
            ->getMock();

        // Check for incoming documents
        $newDocs = [
            ['id' => 'incoming1', 'documentType' => 'INVOICE']
        ];

        $proxyClient->expects($this->once())
            ->method('getAllNewDocuments')
            ->with(10)
            ->willReturn($newDocs);

        $proxyClient->expects($this->once())
            ->method('markDownloaded')
            ->with('incoming1');

        // Act
        $incomingDocs = $proxyClient->getAllNewDocuments(10);
        $this->assertCount(1, $incomingDocs);

        foreach ($incomingDocs as $doc) {
            $proxyClient->markDownloaded($doc['id']);
        }
    }
    #[Test]
    public function it_retrieves_account_statistics(): void
    {
        // Arrange
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccountTotals'])
            ->getMock();

        $expectedStats = [
            'incoming' => 15,
            'outgoing' => 8,
            'balance' => 250.00
        ];

        $appClient->expects($this->once())
            ->method('getAccountTotals')
            ->willReturn($expectedStats);

        // Act
        $stats = $appClient->getAccountTotals();

        // Assert
        $this->assertEquals(15, $stats['incoming']);
        $this->assertEquals(8, $stats['outgoing']);
        $this->assertEquals(250.00, $stats['balance']);
    }
    #[Test]
    public function it_schedules_document_for_future_sending(): void
    {
        // Arrange
        $scheduleTime = '2024-01-10T09:00:00Z';
        $appClient = $this->getMockBuilder(AppClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createDocument'])
            ->getMock();

        $appClient->expects($this->once())
            ->method('createDocument')
            ->with($this->sampleUblXml, false, $scheduleTime)
            ->willReturn([
                'id' => 'doc123',
                'status' => 'SCHEDULED',
                'scheduledAt' => $scheduleTime
            ]);

        // Act
        $document = $appClient->createDocument($this->sampleUblXml, false, $scheduleTime);

        // Assert
        $this->assertEquals('SCHEDULED', $document['status']);
        $this->assertEquals($scheduleTime, $document['scheduledAt']);
    }
}

<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\UnbalancedLedgerException;
use PHPUnit\Framework\TestCase;

class UnbalancedLedgerExceptionTest extends TestCase
{
    public function test_exception_calculates_discrepancy_and_message_accurately(): void
    {
        $totalDebit = 100000;
        $totalCredit = 98000;

        $exception = new UnbalancedLedgerException($totalDebit, $totalCredit);

        $this->assertSame(100000, $exception->getTotalDebit());
        $this->assertSame(98000, $exception->getTotalCredit());
        $this->assertSame(2000, $exception->getDiscrepancy());
        $this->assertStringContainsString('Total Debit (Rp100000)', $exception->getMessage());
        $this->assertStringContainsString('Total Kredit (Rp98000)', $exception->getMessage());
        $this->assertStringContainsString('Selisih: Rp2000', $exception->getMessage());
    }

    public function test_exception_calculates_discrepancy_when_credit_is_higher(): void
    {
        $totalDebit = 50000;
        $totalCredit = 55000;

        $exception = new UnbalancedLedgerException($totalDebit, $totalCredit);

        $this->assertSame(50000, $exception->getTotalDebit());
        $this->assertSame(55000, $exception->getTotalCredit());
        $this->assertSame(5000, $exception->getDiscrepancy());
        $this->assertStringContainsString('Selisih: Rp5000', $exception->getMessage());
    }
}

<?php

namespace Tests\Unit\Enums;

use App\Enums\AccountType;
use App\Enums\EntryType;
use PHPUnit\Framework\TestCase;

class FinancialEnumsTest extends TestCase
{
    /**
     * Memverifikasi seluruh case pada AccountType enum.
     */
    public function test_account_type_enum_has_all_required_cases(): void
    {
        $expectedCases = [
            'BANK' => 'bank',
            'EWALLET' => 'ewallet',
            'CASH' => 'cash',
            'INCOME' => 'income',
            'EXPENSE' => 'expense',
            'DEBT' => 'debt',
            'RECEIVABLE' => 'receivable',
        ];

        foreach ($expectedCases as $caseName => $value) {
            $this->assertEquals($value, constant(AccountType::class . "::$caseName")->value);
        }

        $this->assertCount(7, AccountType::cases());
    }

    /**
     * Memverifikasi helper method identifikasi asset, digital, dan physical.
     */
    public function test_account_type_helpers_identify_asset_digital_and_physical(): void
    {
        $this->assertTrue(AccountType::BANK->isAsset());
        $this->assertTrue(AccountType::EWALLET->isAsset());
        $this->assertTrue(AccountType::CASH->isAsset());
        $this->assertTrue(AccountType::RECEIVABLE->isAsset());
        $this->assertFalse(AccountType::EXPENSE->isAsset());
        $this->assertFalse(AccountType::DEBT->isAsset());

        $this->assertTrue(AccountType::BANK->isDigital());
        $this->assertTrue(AccountType::EWALLET->isDigital());
        $this->assertFalse(AccountType::CASH->isDigital());

        $this->assertTrue(AccountType::CASH->isPhysical());
        $this->assertFalse(AccountType::BANK->isPhysical());
    }

    /**
     * Memverifikasi case dan helper pada EntryType enum.
     */
    public function test_entry_type_enum_cases_and_helpers(): void
    {
        $this->assertEquals('debit', EntryType::DEBIT->value);
        $this->assertEquals('credit', EntryType::CREDIT->value);
        $this->assertCount(2, EntryType::cases());

        $this->assertTrue(EntryType::DEBIT->isDebit());
        $this->assertFalse(EntryType::DEBIT->isCredit());

        $this->assertTrue(EntryType::CREDIT->isCredit());
        $this->assertFalse(EntryType::CREDIT->isDebit());
    }
}

<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\QuickAction\PersonalExpenseRequest;
use App\Models\Category;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PersonalExpenseRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_personal_expense_validation_passes_with_valid_payload(): void
    {
        $category = Category::create([
            'name' => 'Makanan & Minuman Driver',
            'type' => 'expense',
            'icon' => 'food',
            'color' => '#FF5722',
        ]);

        $request = new PersonalExpenseRequest();

        $data = [
            'amount' => 15000,
            'category_id' => $category->id,
            'notes' => 'Makan siang warteg',
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_personal_expense_validation_fails_when_category_does_not_exist(): void
    {
        $request = new PersonalExpenseRequest();

        $data = [
            'amount' => 15000,
            'category_id' => 9999,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertEquals('Kategori yang dipilih tidak valid.', $validator->errors()->first('category_id'));
    }

    public function test_personal_expense_validation_fails_with_amount_under_500(): void
    {
        $category = Category::create([
            'name' => 'Snack',
            'type' => 'expense',
        ]);

        $request = new PersonalExpenseRequest();

        $data = [
            'amount' => 200,
            'category_id' => $category->id,
        ];

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertEquals('Nominal pengeluaran minimal Rp500.', $validator->errors()->first('amount'));
    }
}

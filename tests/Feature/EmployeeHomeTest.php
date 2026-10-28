<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeHomeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $manager;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme']);
        $this->manager = $this->makeUser($this->company, User::ROLE_MANAGER, 'm@example.com');
        $this->employee = $this->makeUser($this->company, User::ROLE_EMPLOYEE, 'e@example.com');
    }

    private function makeUser(Company $company, string $role, string $email): User
    {
        $user = new User(['name' => ucfirst($role), 'email' => $email, 'password' => 'manager-password-1']);
        $user->companyID = $company->companyID;
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function laptop(int $stock = 10, ?Company $company = null)
    {
        return ($company ?? $this->company)->products()->create([
            'name' => 'Laptop', 'stockKeepUnit' => 'LAP-1', 'stockQuantity' => $stock,
            'minStockLevel' => 2, 'purchasePrice' => 100, 'sellingPrice' => 150,
        ]);
    }

    public function test_employee_sees_the_managers_products_but_no_add_controls(): void
    {
        $this->laptop();

        $this->actingAs($this->employee)->get('/home')
            ->assertOk()
            ->assertSee('Laptop')
            ->assertSee('LAP-1')
            ->assertSee('Update stock')
            ->assertSee('Log out')
            ->assertDontSee('Add item')
            ->assertDontSee('Add employee')
            ->assertDontSee('Edit account');
    }

    public function test_manager_menu_still_has_the_manager_links(): void
    {
        $this->actingAs($this->manager)->get('/dashboard')
            ->assertSee('Add employee')
            ->assertSee('Edit account');
    }

    public function test_employee_cannot_open_manager_pages_or_add_products(): void
    {
        $this->actingAs($this->employee)->get('/dashboard')->assertForbidden();
        $this->actingAs($this->employee)->get('/employees')->assertForbidden();
        $this->actingAs($this->employee)->post('/products', ['name' => 'x'])->assertForbidden();
    }

    public function test_employee_does_not_see_other_companies_products(): void
    {
        $other = Company::create(['name' => 'Other']);
        $other->products()->create([
            'name' => 'Secret Widget', 'stockKeepUnit' => 'S-1', 'minStockLevel' => 0,
            'purchasePrice' => 1, 'sellingPrice' => 2,
        ]);

        $this->actingAs($this->employee)->get('/home')->assertDontSee('Secret Widget');
    }

    public function test_outbound_movement_reduces_stock_and_records_revenue_at_selling_price(): void
    {
        $product = $this->laptop(10);

        $this->actingAs($this->employee)->post("/products/{$product->productID}/movements", [
            'movementProduct' => $product->productID,
            'skuType' => 'outbound',
            'skuQuantity' => 4,
            'unitPrice' => '1', // ignored for outbound
            'skuNote' => 'Order #12',
        ])->assertRedirect('/home')->assertSessionHas('status');

        $this->assertSame(6, $product->fresh()->stockQuantity);
        $this->assertDatabaseHas('stock_movements', [
            'companyID' => $this->company->companyID,
            'productID' => $product->productID,
            'userID' => $this->employee->userID,
            'skuType' => 'outbound',
            'skuQuantity' => 4,
            'unitPrice' => '150.00',
            'totalAmount' => '600.00',
            'skuNote' => 'Order #12',
        ]);

        $this->actingAs($this->manager)->get('/dashboard')->assertSee('Rp 600');
    }

    public function test_inbound_movement_adds_stock_and_records_an_expense(): void
    {
        $product = $this->laptop(10);

        $this->actingAs($this->employee)->post("/products/{$product->productID}/movements", [
            'movementProduct' => $product->productID,
            'skuType' => 'inbound',
            'skuQuantity' => 5,
            'unitPrice' => '90.50',
        ])->assertRedirect('/home');

        $this->assertSame(15, $product->fresh()->stockQuantity);
        $this->assertDatabaseHas('stock_movements', [
            'skuType' => 'inbound', 'skuQuantity' => 5, 'unitPrice' => '90.50', 'totalAmount' => '452.50',
        ]);
    }

    public function test_shipping_more_than_is_in_stock_is_rejected_and_changes_nothing(): void
    {
        $product = $this->laptop(40);

        $this->actingAs($this->employee)->from('/home')
            ->post("/products/{$product->productID}/movements", [
                'movementProduct' => $product->productID,
                'skuType' => 'outbound',
                'skuQuantity' => 100,
            ])
            ->assertRedirect('/home')
            ->assertSessionHasErrorsIn('recordMovement', 'skuQuantity');

        $this->assertSame(40, $product->fresh()->stockQuantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_shipping_exactly_the_remaining_stock_is_allowed(): void
    {
        $product = $this->laptop(3);

        $this->actingAs($this->employee)->post("/products/{$product->productID}/movements", [
            'movementProduct' => $product->productID, 'skuType' => 'outbound', 'skuQuantity' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $product->fresh()->stockQuantity);
    }

    public function test_movement_input_is_validated(): void
    {
        $product = $this->laptop();
        $url = "/products/{$product->productID}/movements";

        $this->actingAs($this->employee)->from('/home')->post($url, [
            'movementProduct' => $product->productID, 'skuType' => 'sideways', 'skuQuantity' => 0,
        ])->assertSessionHasErrorsIn('recordMovement', ['skuType', 'skuQuantity']);

        $this->actingAs($this->employee)->from('/home')->post($url, [
            'movementProduct' => $product->productID, 'skuType' => 'inbound', 'skuQuantity' => 1,
        ])->assertSessionHasErrorsIn('recordMovement', 'unitPrice');

        $this->actingAs($this->employee)->from('/home')->post($url, [
            'movementProduct' => $product->productID, 'skuType' => 'outbound', 'skuQuantity' => 1.5,
        ])->assertSessionHasErrorsIn('recordMovement', 'skuQuantity');

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(10, $product->fresh()->stockQuantity);
    }

    public function test_managers_cannot_record_movements_and_other_companies_products_are_hidden(): void
    {
        $product = $this->laptop();
        $other = Company::create(['name' => 'Other']);
        $foreign = $this->laptop(5, $other);
        $payload = ['movementProduct' => 1, 'skuType' => 'outbound', 'skuQuantity' => 1];

        $this->actingAs($this->manager)->post("/products/{$product->productID}/movements", $payload)->assertForbidden();
        $this->actingAs($this->employee)->post("/products/{$foreign->productID}/movements", $payload)->assertNotFound();

        $this->assertSame(5, $foreign->fresh()->stockQuantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_manager_can_change_an_employees_password(): void
    {
        DB::table('sessions')->insert([
            'id' => 'abc', 'user_id' => $this->employee->userID, 'payload' => '', 'last_activity' => time(),
        ]);

        $this->actingAs($this->manager)->put("/employees/{$this->employee->userID}/password", [
            'employeeID' => $this->employee->userID,
            'new_password' => 'a brand new passphrase',
            'new_password_confirmation' => 'a brand new passphrase',
            'current_password' => 'manager-password-1',
        ])->assertRedirect('/employees')->assertSessionHas('status');

        $this->assertTrue(Hash::check('a brand new passphrase', $this->employee->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'abc']);
    }

    public function test_changing_a_password_requires_the_managers_password_and_a_strong_new_one(): void
    {
        $url = "/employees/{$this->employee->userID}/password";
        $old = $this->employee->password;

        $this->actingAs($this->manager)->from('/employees')->put($url, [
            'employeeID' => $this->employee->userID,
            'new_password' => 'a brand new passphrase',
            'new_password_confirmation' => 'a brand new passphrase',
            'current_password' => 'wrong',
        ])->assertSessionHasErrorsIn('changePassword', 'current_password');

        $this->actingAs($this->manager)->from('/employees')->put($url, [
            'employeeID' => $this->employee->userID,
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
            'current_password' => 'manager-password-1',
        ])->assertSessionHasErrorsIn('changePassword', 'new_password');

        $this->actingAs($this->manager)->from('/employees')->put($url, [
            'employeeID' => $this->employee->userID,
            'new_password' => 'a brand new passphrase',
            'new_password_confirmation' => 'does not match at all',
            'current_password' => 'manager-password-1',
        ])->assertSessionHasErrorsIn('changePassword', 'new_password');

        $this->assertSame($old, $this->employee->fresh()->password);
    }

    public function test_password_changes_are_limited_to_own_company_employees_and_managers(): void
    {
        $other = Company::create(['name' => 'Other']);
        $foreign = $this->makeUser($other, User::ROLE_EMPLOYEE, 'f@example.com');
        $body = [
            'new_password' => 'a brand new passphrase',
            'new_password_confirmation' => 'a brand new passphrase',
            'current_password' => 'manager-password-1',
        ];

        $this->actingAs($this->manager)->put("/employees/{$foreign->userID}/password", $body)->assertNotFound();
        $this->actingAs($this->employee)->put("/employees/{$this->employee->userID}/password", $body)->assertForbidden();
    }
}

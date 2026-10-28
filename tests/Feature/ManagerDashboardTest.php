<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Company $company, string $role, string $email = 'user@example.com'): User
    {
        $user = new User([
            'name' => 'Dana Manager',
            'email' => $email,
            'password' => 'secure-password',
        ]);
        $user->companyID = $company->companyID;
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Laptop',
            'stockKeepUnit' => 'LAP-001',
            'minStockLevel' => 5,
            'purchasePrice' => '1000.50',
            'sellingPrice' => '1500',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/products', $this->productData())->assertRedirect('/login');
    }

    public function test_employees_cannot_access_manager_pages(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $employee = $this->makeUser($company, User::ROLE_EMPLOYEE);

        $this->actingAs($employee)->get('/dashboard')->assertForbidden();
        $this->actingAs($employee)->get('/account')->assertForbidden();
        $this->actingAs($employee)->get('/employees')->assertForbidden();
        $this->actingAs($employee)->post('/products', $this->productData())->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_home_routes_managers_to_the_dashboard_and_employees_to_their_home(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'manager@example.com');
        $employee = $this->makeUser($company, User::ROLE_EMPLOYEE, 'employee@example.com');

        $this->actingAs($manager)->get('/home')->assertRedirect('/dashboard');
        $this->actingAs($employee)->get('/home')->assertOk();
    }

    public function test_manager_sees_header_details_and_an_empty_state(): void
    {
        $company = Company::create(['name' => 'Acme Supplies']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);

        $this->actingAs($manager)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dana Manager')
            ->assertSee('Acme Supplies')
            ->assertSee('Add employee')
            ->assertSee('No products yet.')
            ->assertSee('Rp 0');
    }

    public function test_summary_cards_are_calculated_from_the_managers_company_only(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $other = Company::create(['name' => 'Other Co']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);
        $otherManager = $this->makeUser($other, User::ROLE_MANAGER, 'other@example.com');

        $laptop = $company->products()->create($this->productData([
            'stockQuantity' => 10,
            'purchasePrice' => 100,
        ]));
        $company->products()->create($this->productData([
            'name' => 'Mouse',
            'stockKeepUnit' => 'MOU-001',
            'stockQuantity' => 4,
            'purchasePrice' => 25,
        ]));
        $foreign = $other->products()->create($this->productData([
            'stockQuantity' => 100,
            'purchasePrice' => 999,
        ]));

        $this->addMovement($company, $laptop, $manager, StockMovement::TYPE_OUTBOUND, 1500);
        $this->addMovement($company, $laptop, $manager, StockMovement::TYPE_OUTBOUND, 500);
        $this->addMovement($company, $laptop, $manager, StockMovement::TYPE_INBOUND, 700);
        $this->addMovement($other, $foreign, $otherManager, StockMovement::TYPE_OUTBOUND, 88888);

        $response = $this->actingAs($manager)->get('/dashboard')->assertOk();

        $response->assertSee('Rp 1.100');
        $response->assertSee('Rp 2.000');
        $response->assertSee('Rp 700');
        $response->assertDontSee('88.888');
        $response->assertDontSee('99.900');
    }

    private function addMovement(Company $company, $product, User $user, string $type, float $total): void
    {
        StockMovement::create([
            'companyID' => $company->companyID,
            'productID' => $product->productID,
            'userID' => $user->userID,
            'skuType' => $type,
            'skuQuantity' => 1,
            'unitPrice' => $total,
            'totalAmount' => $total,
        ]);
    }

    public function test_product_search_filters_by_name_and_escapes_output(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);
        $company->products()->create($this->productData(['name' => 'Blue Laptop']));
        $company->products()->create($this->productData(['name' => 'Red Mouse', 'stockKeepUnit' => 'MOU-001']));

        $this->actingAs($manager)->get('/dashboard?q=laptop')
            ->assertOk()
            ->assertSee('Blue Laptop')
            ->assertDontSee('Red Mouse');

        $this->actingAs($manager)->get('/dashboard?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('No products match');
    }

    public function test_manager_can_add_a_product_to_their_company(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);

        $this->actingAs($manager)->post('/products', $this->productData())
            ->assertRedirect('/dashboard')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'companyID' => $company->companyID,
            'name' => 'Laptop',
            'stockKeepUnit' => 'LAP-001',
            'stockQuantity' => 0,
            'minStockLevel' => 5,
            'purchasePrice' => '1000.50',
            'sellingPrice' => '1500.00',
        ]);
    }

    public function test_product_input_is_validated_on_the_server(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);

        $this->actingAs($manager)->from('/dashboard')->post('/products', [
            'name' => '',
            'stockKeepUnit' => '',
            'minStockLevel' => -1,
            'purchasePrice' => 'free',
            'sellingPrice' => '1.999',
        ])->assertRedirect('/dashboard')
            ->assertSessionHasErrors(['name', 'stockKeepUnit', 'minStockLevel', 'purchasePrice', 'sellingPrice']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_sku_must_be_unique_within_a_company_but_not_across_companies(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $other = Company::create(['name' => 'Other Co']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER);
        $otherManager = $this->makeUser($other, User::ROLE_MANAGER, 'other@example.com');

        $this->actingAs($manager)->post('/products', $this->productData())->assertRedirect('/dashboard');

        $this->actingAs($manager)->from('/dashboard')->post('/products', $this->productData())
            ->assertSessionHasErrors('stockKeepUnit');

        $this->actingAs($otherManager)->post('/products', $this->productData())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('products', 2);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrudAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Company $company, string $role, string $email, string $name = 'Dana'): User
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => 'a-long-secure-pass']);
        $user->companyID = $company->companyID;
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function setup2(): array
    {
        $a = Company::create(['name' => 'Acme']);
        $b = Company::create(['name' => 'Other']);

        return [
            $a, $b,
            $this->makeUser($a, User::ROLE_MANAGER, 'm@a.test'),
            $this->makeUser($b, User::ROLE_MANAGER, 'm@b.test'),
        ];
    }

    private function product(Company $c, array $o = []): Product
    {
        return $c->products()->create(array_merge([
            'name' => 'Laptop', 'minStockLevel' => 5, 'sellingPrice' => 100,
        ], $o));
    }

    public function test_manager_updates_a_product_without_touching_stock(): void
    {
        [$a, , $m] = $this->setup2();
        $p = $this->product($a, ['stockQuantity' => 7]);

        $this->actingAs($m)->put("/products/{$p->productID}", [
            'name' => 'Gaming Laptop', 'minStockLevel' => 2, 'sellingPrice' => '250',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'productID' => $p->productID, 'name' => 'Gaming Laptop', 'stockQuantity' => 7,
        ]);
    }

    public function test_product_from_another_company_cannot_be_changed_or_deleted(): void
    {
        [$a, , , $mb] = $this->setup2();
        $p = $this->product($a);

        $this->actingAs($mb)->put("/products/{$p->productID}", [
            'name' => 'Hacked', 'minStockLevel' => 1, 'sellingPrice' => 1,
        ])->assertForbidden();
        $this->actingAs($mb)->delete("/products/{$p->productID}", ['password' => 'a-long-secure-pass'])
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['productID' => $p->productID, 'name' => 'Laptop']);
    }

    public function test_product_delete_needs_the_managers_password(): void
    {
        [$a, , $m] = $this->setup2();
        $p = $this->product($a);

        $this->actingAs($m)->delete("/products/{$p->productID}", ['password' => 'wrong-password-123'])
            ->assertSessionHasErrors('password', null, 'deleteProduct');
        $this->assertDatabaseHas('products', ['productID' => $p->productID]);

        $this->actingAs($m)->delete("/products/{$p->productID}", ['password' => 'a-long-secure-pass'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('products', ['productID' => $p->productID]);
    }

    public function test_product_with_movements_cannot_be_deleted(): void
    {
        [$a, , $m] = $this->setup2();
        $p = $this->product($a);
        StockMovement::create([
            'companyID' => $a->companyID, 'productID' => $p->productID, 'userID' => $m->userID,
            'skuType' => 'outbound', 'skuQuantity' => 1, 'unitPrice' => 1, 'totalAmount' => 1,
        ]);

        $this->actingAs($m)->delete("/products/{$p->productID}", ['password' => 'a-long-secure-pass'])
            ->assertSessionHasErrors('product', null, 'deleteProduct');
        $this->assertDatabaseHas('products', ['productID' => $p->productID]);
    }

    public function test_category_crud_and_scoping(): void
    {
        [$a, , $m, $mb] = $this->setup2();

        $this->actingAs($m)->post('/categories', ['name' => 'Tools', 'description' => 'Hand tools'])
            ->assertSessionHasNoErrors();
        $cat = Category::where('companyID', $a->companyID)->firstOrFail();

        $this->actingAs($m)->from('/categories')->post('/categories', ['name' => 'Tools'])
            ->assertSessionHasErrors('name', null, 'addCategory');

        $this->actingAs($mb)->post('/categories', ['name' => 'Tools'])->assertSessionHasNoErrors();

        $this->actingAs($m)->put("/categories/{$cat->categoryID}", ['name' => 'Hardware'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['categoryID' => $cat->categoryID, 'name' => 'Hardware']);

        $this->actingAs($mb)->put("/categories/{$cat->categoryID}", ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($m)->get('/categories')->assertOk()->assertSee('Hardware');
    }

    public function test_deleting_a_category_keeps_its_products(): void
    {
        [$a, , $m] = $this->setup2();
        $cat = $a->categories()->create(['name' => 'Tools']);
        $p = $this->product($a, ['categoryID' => $cat->categoryID]);

        $this->actingAs($m)->delete("/categories/{$cat->categoryID}")->assertRedirect();

        $this->assertDatabaseMissing('categories', ['categoryID' => $cat->categoryID]);
        $this->assertDatabaseHas('products', ['productID' => $p->productID, 'categoryID' => null]);
    }

    public function test_product_cannot_use_another_companys_category(): void
    {
        [$a, $b, $m] = $this->setup2();
        $foreign = $b->categories()->create(['name' => 'Secret']);

        $this->actingAs($m)->post('/products', [
            'name' => 'X', 'minStockLevel' => 1, 'sellingPrice' => 1, 'categoryID' => $foreign->categoryID,
        ])->assertSessionHasErrors('categoryID', null, 'addProduct');
    }

    public function test_manager_edits_an_employee_and_employees_cannot(): void
    {
        [$a, , $m, $mb] = $this->setup2();
        $e = $this->makeUser($a, User::ROLE_EMPLOYEE, 'e@a.test', 'Eli');

        $this->actingAs($m)->put("/employees/{$e->userID}", ['name' => 'Eli Two', 'email' => 'Eli2@A.test'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['userID' => $e->userID, 'name' => 'Eli Two', 'email' => 'eli2@a.test']);

        $this->actingAs($m)->put("/employees/{$e->userID}", ['name' => 'Eli', 'email' => 'm@a.test'])
            ->assertSessionHasErrors('email', null, 'editEmployee');

        $this->actingAs($mb)->put("/employees/{$e->userID}", ['name' => 'X', 'email' => 'x@x.test'])
            ->assertForbidden();
        $this->actingAs($e)->put("/employees/{$e->userID}", ['name' => 'Me', 'email' => 'me@a.test'])
            ->assertForbidden();
    }

    public function test_dashboard_shows_net_low_stock_and_recent_movements_with_the_author(): void
    {
        [$a, $b, $m, $mb] = $this->setup2();
        $emp = $this->makeUser($a, User::ROLE_EMPLOYEE, 'e@a.test', 'Eli Employee');
        $low = $this->product($a, ['name' => 'Low Widget', 'stockQuantity' => 1, 'minStockLevel' => 5]);
        $this->product($a, ['name' => 'Plenty Gadget', 'stockQuantity' => 50, 'minStockLevel' => 5]);
        $foreign = $this->product($b, ['name' => 'Foreign Thing', 'stockQuantity' => 0]);

        foreach ([['outbound', 300], ['inbound', 500]] as [$type, $amount]) {
            StockMovement::create([
                'companyID' => $a->companyID, 'productID' => $low->productID, 'userID' => $emp->userID,
                'skuType' => $type, 'skuQuantity' => 1, 'unitPrice' => $amount, 'totalAmount' => $amount,
            ]);
        }
        StockMovement::create([
            'companyID' => $b->companyID, 'productID' => $foreign->productID, 'userID' => $mb->userID,
            'skuType' => 'outbound', 'skuQuantity' => 1, 'unitPrice' => 1, 'totalAmount' => 1,
        ]);

        $html = $this->actingAs($m)->get('/dashboard')->assertOk()
            ->assertSee('Net profit / loss')
            ->assertSee('Rp -200')
            ->assertSee('Low-stock alerts')
            ->assertSee('by Eli Employee')
            ->getContent();

        $this->assertStringNotContainsString('Foreign Thing', $html);
    }

    public function test_restock_updates_the_latest_purchase_price(): void
    {
        [$a] = $this->setup2();
        $emp = $this->makeUser($a, User::ROLE_EMPLOYEE, 'e@a.test');
        $p = $this->product($a);

        $this->actingAs($emp)->post("/products/{$p->productID}/movements", [
            'movementProduct' => $p->productID, 'skuType' => 'inbound', 'skuQuantity' => 3, 'unitPrice' => '40',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['productID' => $p->productID, 'stockQuantity' => 3, 'purchasePrice' => '40.00']);
    }

    public function test_security_headers_are_sent_and_cors_is_not_opened(): void
    {
        $response = $this->get('/login', ['Origin' => 'https://evil.example']);

        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_markup_in_text_fields_is_rejected(): void
    {
        [, , $m] = $this->setup2();

        $this->actingAs($m)->post('/products', [
            'name' => '<script>alert(1)</script>', 'minStockLevel' => 1, 'sellingPrice' => 1,
        ])->assertSessionHasErrors('name', null, 'addProduct');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_search_input_cannot_inject_sql(): void
    {
        [$a, , $m] = $this->setup2();
        $this->product($a);

        $this->actingAs($m)->get('/dashboard?q='.urlencode("' OR 1=1 --"))
            ->assertOk()->assertSee('No products match');
        $this->actingAs($m)->get('/dashboard?q='.urlencode("'; DROP TABLE products; --"))->assertOk();

        $this->assertDatabaseCount('products', 1);
    }

    public function test_passwords_are_stored_hashed(): void
    {
        [, , $m] = $this->setup2();
        $stored = DB::table('users')->where('userID', $m->userID)->value('password');

        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertNotSame('a-long-secure-pass', $stored);
        $this->assertTrue(Hash::check('a-long-secure-pass', $stored));
    }
}

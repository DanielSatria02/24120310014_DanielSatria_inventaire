<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Company $company, string $role, string $email, string $password = 'manager-password-1'): User
    {
        $user = new User(['name' => ucfirst($role), 'email' => $email, 'password' => $password]);
        $user->companyID = $company->companyID;
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Erin Employee',
            'email' => 'Erin@Example.com',
            'password' => 'correct horse battery',
        ], $overrides);
    }

    public function test_manager_can_add_an_employee_to_their_company(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');

        $this->actingAs($manager)->post('/employees', $this->payload())
            ->assertRedirect('/employees')
            ->assertSessionHas('status');

        $employee = User::where('email', 'erin@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_EMPLOYEE, $employee->role);
        $this->assertSame($company->companyID, $employee->companyID);
        $this->assertTrue(Hash::check('correct horse battery', $employee->password));

        $this->actingAs($manager)->get('/employees')->assertOk()->assertSee('Erin Employee');
    }

    public function test_employee_can_log_in_with_the_given_credentials(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');
        $this->actingAs($manager)->post('/employees', $this->payload());
        auth()->logout();

        $this->post('/login', ['email' => 'erin@example.com', 'password' => 'correct horse battery'])
            ->assertRedirect('/home');
        $this->assertAuthenticated();
    }

    public function test_employees_cannot_add_or_delete_employees(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $employee = $this->makeUser($company, User::ROLE_EMPLOYEE, 'e@example.com');
        $other = $this->makeUser($company, User::ROLE_EMPLOYEE, 'o@example.com');

        $this->actingAs($employee)->post('/employees', $this->payload())->assertForbidden();
        $this->actingAs($employee)->delete('/employees/'.$other->userID, ['password' => 'manager-password-1'])->assertForbidden();
        $this->assertDatabaseHas('users', ['email' => 'o@example.com']);
    }

    #[DataProvider('weakPasswords')]
    public function test_passwords_must_meet_the_policy(string $password): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');

        $this->actingAs($manager)->from('/employees')
            ->post('/employees', $this->payload(['password' => $password]))
            ->assertSessionHasErrorsIn('addEmployee', 'password');

        $this->assertDatabaseMissing('users', ['email' => 'erin@example.com']);
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['short-pass1'],
            'too long' => [str_repeat('a', 65)],
            'same as email' => ['erin@example.com'],
            'leading space' => [' correct horse battery'],
        ];
    }

    public function test_email_must_be_unique_and_valid(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');

        $this->actingAs($manager)->from('/employees')
            ->post('/employees', $this->payload(['email' => 'M@example.com']))
            ->assertSessionHasErrorsIn('addEmployee', 'email');

        $this->actingAs($manager)->from('/employees')
            ->post('/employees', $this->payload(['email' => 'nope', 'name' => '']))
            ->assertSessionHasErrorsIn('addEmployee', ['email', 'name']);
    }

    public function test_registration_enforces_the_same_password_policy(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'T', 'company_name' => 'C', 'email' => 't@example.com',
            'password' => 'short-pass1', 'password_confirmation' => 'short-pass1',
        ])->assertSessionHasErrors('password');
    }

    public function test_delete_requires_the_managers_password(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');
        $employee = $this->makeUser($company, User::ROLE_EMPLOYEE, 'e@example.com');

        $this->actingAs($manager)->from('/employees')
            ->delete('/employees/'.$employee->userID, ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('deleteEmployee', 'password')
            ->assertSessionHas('deleteEmployeeID', $employee->userID);
        $this->assertDatabaseHas('users', ['email' => 'e@example.com']);

        $this->actingAs($manager)->delete('/employees/'.$employee->userID)
            ->assertSessionHasErrorsIn('deleteEmployee', 'password');
        $this->assertDatabaseHas('users', ['email' => 'e@example.com']);

        $this->actingAs($manager)->delete('/employees/'.$employee->userID, ['password' => 'manager-password-1'])
            ->assertRedirect('/employees');
        $this->assertDatabaseMissing('users', ['email' => 'e@example.com']);
    }

    public function test_manager_cannot_delete_other_companies_employees_or_managers(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $other = Company::create(['name' => 'Other']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');
        $coManager = $this->makeUser($company, User::ROLE_MANAGER, 'm2@example.com');
        $foreign = $this->makeUser($other, User::ROLE_EMPLOYEE, 'f@example.com');

        $this->actingAs($manager)->delete('/employees/'.$foreign->userID, ['password' => 'manager-password-1'])->assertNotFound();
        $this->actingAs($manager)->delete('/employees/'.$coManager->userID, ['password' => 'manager-password-1'])->assertNotFound();
        $this->assertDatabaseHas('users', ['email' => 'f@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'm2@example.com']);
    }

    public function test_employee_with_recorded_movements_cannot_be_deleted(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $manager = $this->makeUser($company, User::ROLE_MANAGER, 'm@example.com');
        $employee = $this->makeUser($company, User::ROLE_EMPLOYEE, 'e@example.com');
        $product = $company->products()->create([
            'name' => 'Laptop', 'minStockLevel' => 1,
            'purchasePrice' => 10, 'sellingPrice' => 20,
        ]);
        StockMovement::create([
            'companyID' => $company->companyID, 'productID' => $product->productID,
            'userID' => $employee->userID, 'skuType' => StockMovement::TYPE_INBOUND,
            'skuQuantity' => 1, 'unitPrice' => 10, 'totalAmount' => 10,
        ]);

        $this->actingAs($manager)->from('/employees')
            ->delete('/employees/'.$employee->userID, ['password' => 'manager-password-1'])
            ->assertSessionHasErrorsIn('deleteEmployee', 'employee');
        $this->assertDatabaseHas('users', ['email' => 'e@example.com']);
    }
}


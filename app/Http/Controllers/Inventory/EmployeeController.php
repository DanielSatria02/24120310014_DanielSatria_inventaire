<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreEmployeeRequest;
use App\Http\Requests\Inventory\UpdateEmployeeRequest;
use App\Models\User;
use App\Rules\PasswordDiffersFromEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = $request->user()->company
            ->users()
            ->where('role', User::ROLE_EMPLOYEE)
            ->orderBy('name')
            ->get();

        return view('pages.manager.employees', ['employees' => $employees]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = new User(Arr::only($request->validated(), ['name', 'email', 'password']));
        $employee->companyID = $request->user()->companyID;
        $employee->role = User::ROLE_EMPLOYEE;
        $employee->save();

        return redirect()
            ->route('employees.create')
            ->with('status', 'Employee added.');
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.create')
            ->with('status', 'Employee updated.');
    }

    public function updatePassword(Request $request, User $employee): RedirectResponse
    {
        $this->ownEmployee($request, $employee);

        $request->validateWithBag('changePassword', [
            'current_password' => ['required', 'current_password'],
            'new_password' => [
                'required',
                'string',
                Password::defaults(),
                'not_regex:/^\s|\s$/',
                'confirmed',
                new PasswordDiffersFromEmail($employee->email),
            ],
        ], [
            'current_password.current_password' => 'That password is incorrect.',
            'new_password.not_regex' => 'The password must not start or end with a space.',
        ], [
            'new_password' => 'new password',
        ]);

        $employee->password = $request->input('new_password');
        $employee->setRememberToken(Str::random(60));
        $employee->save();

        // Sign the employee out everywhere so the old password stops working.
        DB::table('sessions')->where('user_id', $employee->userID)->delete();

        return redirect()
            ->route('employees.create')
            ->with('status', 'Password updated for '.$employee->name.'.');
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        $manager = $request->user();

        $this->ownEmployee($request, $employee);
        if (! Hash::check((string) $request->input('password'), $manager->password)) {
            return back()
                ->withErrors(['password' => 'That password is incorrect.'], 'deleteEmployee')
                ->with('deleteEmployeeID', $employee->userID);
        }

        if ($employee->stockMovements()->exists()) {
            return back()->withErrors(
                ['employee' => $employee->name.' has recorded stock movements and cannot be deleted.'],
                'deleteEmployee'
            );
        }

        $employee->delete();

        return redirect()
            ->route('employees.create')
            ->with('status', 'Employee removed.');
    }

    // Managers can only manage employees of their own company.
    private function ownEmployee(Request $request, User $employee): void
    {
        abort_unless(
            $employee->companyID === $request->user()->companyID && $employee->role === User::ROLE_EMPLOYEE,
            404
        );
    }
}

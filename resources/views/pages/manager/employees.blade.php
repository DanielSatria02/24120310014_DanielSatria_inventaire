@extends('layouts.manager')

@section('title', 'Add employee')

@section('content')
    <section class="page-heading">
        <h1>Employees</h1>
        <p>Add people from {{ auth()->user()->company->name }} so they can sign in and record stock.</p>
    </section>

    <section class="panel">
        <div class="toolbar toolbar-end">
            <button type="button" class="button-primary add-button" data-dialog-open="add-employee-dialog">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Add employee
            </button>
        </div>

        @if ($errors->deleteEmployee->has('employee'))
            <div class="notice panel-error" role="alert">{{ $errors->deleteEmployee->first('employee') }}</div>
        @endif

        @if ($employees->isEmpty())
            <div class="empty-state">
                <p class="empty-title">No employees yet.</p>
                <p>Use “Add employee” to create their account.</p>
            </div>
        @else
            <table class="product-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="numeric">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td data-label="Name" class="product-name">{{ $employee->name }}</td>
                            <td data-label="Email">{{ $employee->email }}</td>
                            <td data-label="Action" class="numeric">
                                <div class="row-actions">
                                    <button type="button" class="button-outline"
                                        data-dialog-open="edit-employee-{{ $employee->userID }}">Edit</button>
                                    <button type="button" class="button-outline"
                                        data-dialog-open="password-employee-{{ $employee->userID }}">Change password</button>
                                    <button type="button" class="button-danger"
                                        data-dialog-open="delete-employee-{{ $employee->userID }}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <dialog id="add-employee-dialog" class="modal" aria-labelledby="add-employee-title"
        @if ($errors->addEmployee->any()) data-open-on-load @endif>
        <form method="POST" action="{{ route('employees.store') }}" class="modal-body" autocomplete="off">
            @csrf

            <div class="modal-head">
                <h2 id="add-employee-title">Add employee</h2>
                <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
            <p class="modal-intro">The employee signs in with this email and password.</p>

            <div class="form-grid">
                <div class="form-field span-2">
                    <label for="employee-name">Name</label>
                    <input id="employee-name" name="name" type="text" value="{{ old('name') }}" maxlength="255"
                        required class="@error('name', 'addEmployee') input-error @enderror">
                    @error('name', 'addEmployee')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field span-2">
                    <label for="employee-email">Email</label>
                    <input id="employee-email" name="email" type="email" value="{{ old('email') }}" maxlength="255"
                        required class="@error('email', 'addEmployee') input-error @enderror">
                    @error('email', 'addEmployee')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field span-2">
                    <label for="employee-password">Password</label>
                    <input id="employee-password" name="password" type="password" minlength="12" maxlength="64"
                        required autocomplete="new-password"
                        class="@error('password', 'addEmployee') input-error @enderror">
                    <p class="field-hint">12 to 64 characters. A long passphrase works best. It can't match the email.</p>
                    @error('password', 'addEmployee')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                <button type="submit" class="button-primary">Add employee</button>
            </div>
        </form>
    </dialog>

    @foreach ($employees as $employee)
        @php($pwFailed = (int) old('employeeID') === $employee->userID && $errors->changePassword->any())
        <dialog id="password-employee-{{ $employee->userID }}" class="modal"
            aria-labelledby="password-title-{{ $employee->userID }}" @if ($pwFailed) data-open-on-load @endif>
            <form method="POST" action="{{ route('employees.password', $employee) }}" class="modal-body" autocomplete="off">
                @csrf
                @method('PUT')
                <input type="hidden" name="employeeID" value="{{ $employee->userID }}">

                <div class="modal-head">
                    <h2 id="password-title-{{ $employee->userID }}">Change password</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
                <p class="modal-intro">
                    Set a new password for <strong>{{ $employee->name }}</strong>. They will be signed out everywhere.
                </p>

                <div class="form-grid">
                    <div class="form-field span-2">
                        <label for="new-password-{{ $employee->userID }}">New password</label>
                        <input id="new-password-{{ $employee->userID }}" name="new_password" type="password"
                            minlength="12" maxlength="64" required autocomplete="new-password"
                            @class(['input-error' => $pwFailed && $errors->changePassword->has('new_password')])>
                        <p class="field-hint">12 to 64 characters. It can't match the email.</p>
                        @if ($pwFailed)
                            @error('new_password', 'changePassword')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="form-field span-2">
                        <label for="new-password-confirm-{{ $employee->userID }}">Confirm new password</label>
                        <input id="new-password-confirm-{{ $employee->userID }}" name="new_password_confirmation"
                            type="password" required autocomplete="new-password">
                    </div>

                    <div class="form-field span-2">
                        <label for="manager-password-{{ $employee->userID }}">Your password</label>
                        <input id="manager-password-{{ $employee->userID }}" name="current_password" type="password"
                            required autocomplete="current-password"
                            @class(['input-error' => $pwFailed && $errors->changePassword->has('current_password')])>
                        @if ($pwFailed)
                            @error('current_password', 'changePassword')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-primary">Update password</button>
                </div>
            </form>
        </dialog>
    @endforeach

    @foreach ($employees as $employee)
        @php($failed = session('deleteEmployeeID') === $employee->userID && $errors->deleteEmployee->has('password'))
        <dialog id="delete-employee-{{ $employee->userID }}" class="modal"
            aria-labelledby="delete-title-{{ $employee->userID }}" @if ($failed) data-open-on-load @endif>
            <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="modal-body">
                @csrf
                @method('DELETE')

                <div class="modal-head">
                    <h2 id="delete-title-{{ $employee->userID }}">Delete employee</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>
                <p class="modal-intro">
                    This permanently removes <strong>{{ $employee->name }}</strong>. Enter your own password to confirm.
                </p>

                <div class="form-field">
                    <label for="confirm-password-{{ $employee->userID }}">Your password</label>
                    <input id="confirm-password-{{ $employee->userID }}" name="password" type="password" required
                        autocomplete="current-password" @class(['input-error' => $failed])>
                    @if ($failed)
                        <p class="field-error">{{ $errors->deleteEmployee->first('password') }}</p>
                    @endif
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-danger button-danger-solid">Delete employee</button>
                </div>
            </form>
        </dialog>
    @endforeach

    @foreach ($employees as $employee)
        @php($editFailed = (int) old('employeeID') === $employee->userID && $errors->editEmployee->any())
        <dialog id="edit-employee-{{ $employee->userID }}" class="modal"
            aria-labelledby="edit-title-{{ $employee->userID }}" @if ($editFailed) data-open-on-load @endif>
            <form method="POST" action="{{ route('employees.update', $employee) }}" class="modal-body">
                @csrf
                @method('PUT')
                <input type="hidden" name="employeeID" value="{{ $employee->userID }}">

                <div class="modal-head">
                    <h2 id="edit-title-{{ $employee->userID }}">Edit employee</h2>
                    <button type="button" class="icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>

                <div class="form-grid">
                    <div class="form-field span-2">
                        <label for="edit-name-{{ $employee->userID }}">Name</label>
                        <input id="edit-name-{{ $employee->userID }}" name="name" type="text" maxlength="255" required
                            value="{{ $editFailed ? old('name') : $employee->name }}"
                            @class(['input-error' => $editFailed && $errors->editEmployee->has('name')])>
                        @if ($editFailed && $errors->editEmployee->has('name'))
                            <p class="field-error">{{ $errors->editEmployee->first('name') }}</p>
                        @endif
                    </div>
                    <div class="form-field span-2">
                        <label for="edit-email-{{ $employee->userID }}">Email</label>
                        <input id="edit-email-{{ $employee->userID }}" name="email" type="email" maxlength="255" required
                            value="{{ $editFailed ? old('email') : $employee->email }}"
                            @class(['input-error' => $editFailed && $errors->editEmployee->has('email')])>
                        @if ($editFailed && $errors->editEmployee->has('email'))
                            <p class="field-error">{{ $errors->editEmployee->first('email') }}</p>
                        @endif
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="button-secondary" data-dialog-close>Cancel</button>
                    <button type="submit" class="button-primary">Save changes</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection

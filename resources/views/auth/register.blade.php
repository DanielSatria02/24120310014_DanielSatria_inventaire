@extends('layouts.auth')

@section('title', 'Create account')

@section('content')
    <section class="auth-card" aria-labelledby="form-title">
        <div class="eyebrow">GET STARTED</div>
        <h1 id="form-title">Create your account</h1>
        <p class="auth-description">Set up your access to Inventaire.</p>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf

            <div class="form-field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                    autocomplete="name" required autofocus
                    class="@error('name') input-error @enderror">
                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="email">Work email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    autocomplete="email" required
                    class="@error('email') input-error @enderror">
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password"
                    autocomplete="new-password" required
                    class="@error('password') input-error @enderror">
                <p class="field-hint">Use at least 8 characters.</p>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    autocomplete="new-password" required>
            </div>

            <button class="button-primary" type="submit">Create account</button>
        </form>

        <div class="auth-divider"><span>OR</span></div>
        <a class="google-button" href="{{ route('auth.google.redirect') }}">
            <img src="{{ asset('images/gmailLogo.png') }}" alt="" aria-hidden="true">
            <span>Continue with Google</span>
        </a>

        <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
        <p class="provisioning-note">Company setup and manager/employee roles will be added in a later step.</p>
    </section>
@endsection

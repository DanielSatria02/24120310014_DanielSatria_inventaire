@extends('layouts.auth')

@section('title', 'Log in')

@section('content')
    <section class="auth-card" aria-labelledby="form-title">
        <div class="eyebrow">WELCOME BACK</div>
        <h1 id="form-title">Log in to your account</h1>
        <p class="auth-description">Keep your inventory and operations moving.</p>

        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                    autocomplete="username" required autofocus
                    class="@error('email') input-error @enderror">
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <div class="label-row">
                    <label for="password">Password</label>
                </div>
                <input id="password" name="password" type="password"
                    autocomplete="current-password" required
                    class="@error('password') input-error @enderror">
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="checkbox-row" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                <span>Remember me</span>
            </label>

            <button class="button-primary" type="submit">Log in</button>
        </form>

        <p class="auth-switch">New to Inventaire? <a href="{{ route('register') }}">Create an account</a></p>
    </section>
@endsection

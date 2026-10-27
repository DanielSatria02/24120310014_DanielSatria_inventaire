@extends('layouts.auth')

@section('title', 'Home')

@section('content')
    <section class="auth-card home-card" aria-labelledby="home-title">
        <div class="eyebrow">YOUR ACCOUNT</div>
        <h1 id="home-title">Welcome, {{ auth()->user()->name }}</h1>
        <p class="auth-description">You are signed in to Inventaire.</p>
        <p class="provisioning-note">Your inventory workspace and company role are not set up yet.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="button-secondary" type="submit">Log out</button>
        </form>
    </section>
@endsection

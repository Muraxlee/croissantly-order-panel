@extends('layouts.app')

@section('content')
    <section class="login-wrap">
        <div class="login-panel">
            <div>
                <p class="eyebrow">Bakery operations</p>
                <h1><span class="brand-wordmark login-wordmark">Croissantly</span> order panel</h1>
                <p class="muted">Use the ID and password shared by admin to open your exact dashboard.</p>
            </div>

            <form method="post" action="{{ route('login.attempt') }}" class="form-stack">
                @csrf
                <label>
                    <span>Login ID</span>
                    <input name="username" value="{{ old('username') }}" autofocus required placeholder="admin">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required placeholder="Password">
                </label>
                <label class="check-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in</span>
                </label>
                <button class="primary-button">Open dashboard</button>
            </form>
        </div>
    </section>
@endsection

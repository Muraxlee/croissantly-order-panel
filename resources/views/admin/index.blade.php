@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Admin control room</p>
            <h1>Choose a workspace</h1>
        </div>
        <a class="secondary-button" href="{{ route('client.place') }}">Open client view</a>
    </header>

    <section class="metric-grid">
        <div><strong>{{ $counts['pending'] }}</strong><span>Pending approval</span></div>
        <div><strong>{{ $counts['approved'] }}</strong><span>Approved</span></div>
        <div><strong>{{ $counts['cooking'] }}</strong><span>Cooking</span></div>
        <div><strong>{{ $counts['completed'] }}</strong><span>Completed today</span></div>
        <div><strong>{{ $counts['menuItems'] }}</strong><span>Menu items</span></div>
        <div><strong>{{ $counts['staffToday'] }}</strong><span>Staff today</span></div>
    </section>

    <section class="workspace-grid">
        <a class="workspace-card" href="{{ route('admin.orders.index') }}">
            <span>Orders</span>
            <strong>Approve and track orders</strong>
            <small>Order board, status filters, and kitchen locks.</small>
        </a>
        <a class="workspace-card" href="{{ route('admin.accounts.index') }}">
            <span>Accounts</span>
            <strong>Create logins</strong>
            <small>Client, employee, and kitchen ID/password accounts.</small>
        </a>
        <a class="workspace-card" href="{{ route('admin.slots.index') }}">
            <span>Staff Calendar</span>
            <strong>Schedule employees</strong>
            <small>Plan shifts, update actual time, and keep staff dashboards live.</small>
        </a>
        <a class="workspace-card" href="{{ route('admin.menu.index') }}">
            <span>Menu</span>
            <strong>Products and prices</strong>
            <small>Add, update, or remove menu items.</small>
        </a>
    </section>
@endsection

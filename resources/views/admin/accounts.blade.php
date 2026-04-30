@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Admin accounts</p>
            <h1>Accounts</h1>
        </div>
    </header>

    <section class="panel page-section">
        <div class="section-head">
            <div>
                <h2>New login</h2>
                <p>Make ID/password logins for clients, employees, and kitchen staff.</p>
            </div>
        </div>
        <form method="post" action="{{ route('admin.accounts.store') }}" class="form-grid">
            @csrf
            <label><span>Name</span><input name="name" required></label>
            <label><span>Login ID</span><input name="username" required></label>
            <label><span>Password</span><input name="password" required minlength="6"></label>
            <label><span>Role</span>
                <select name="role" required>
                    <option value="client">Client</option>
                    <option value="employee">Employee</option>
                    <option value="kitchen">Kitchen</option>
                </select>
            </label>
            <label><span>Email</span><input type="email" name="email"></label>
            <label><span>Phone</span><input name="phone"></label>
            <label><span>Hourly cost</span><input type="number" step="0.01" name="hourly_rate" value="0"></label>
            <label><span>Business</span><input name="business_name"></label>
            <label><span>Kitchen station</span><input name="station"></label>
            <button class="primary-button">Create login</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-head"><div><h2>Clients</h2><p>Open an account to edit or delete it.</p></div></div>
        <div class="account-card-list">
            @forelse($clients as $client)
                <article class="account-card">
                    <div>
                        <strong>{{ $client->name }}</strong>
                        <small>{{ $client->username }} &middot; {{ $client->clientProfile?->phone ?: 'No phone' }}</small>
                    </div>
                    <span class="pill">{{ $client->is_active ? 'Active' : 'Inactive' }}</span>
                    <button class="secondary-button" type="button" data-open-modal="account-modal-{{ $client->id }}">Edit</button>
                    <button class="danger-button" form="delete-account-{{ $client->id }}">Delete</button>
                </article>

                <dialog class="order-modal" id="account-modal-{{ $client->id }}">
                    <div class="modal-shell">
                        <div class="modal-head">
                            <div><p class="eyebrow">Client account</p><h2>{{ $client->name }}</h2></div>
                            <button class="icon-button" type="button" data-close-modal aria-label="Close">&times;</button>
                        </div>
                        <form method="post" action="{{ route('admin.accounts.update', $client) }}" class="form-grid">
                            @csrf
                            @method('put')
                            <input type="hidden" name="is_active" value="0">
                            <label><span>Name</span><input name="name" value="{{ $client->name }}" required></label>
                            <label><span>Login ID</span><input name="username" value="{{ $client->username }}" required></label>
                            <label><span>Email</span><input type="email" name="email" value="{{ $client->email }}"></label>
                            <label><span>Phone</span><input name="phone" value="{{ $client->clientProfile?->phone }}"></label>
                            <label><span>Business</span><input name="business_name" value="{{ $client->clientProfile?->business_name }}"></label>
                            <label><span>New password</span><input name="password" placeholder="Leave blank"></label>
                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($client->is_active)><span>Active</span></label>
                            <button class="primary-button">Update account</button>
                        </form>
                    </div>
                </dialog>

                <form id="delete-account-{{ $client->id }}" method="post" action="{{ route('admin.accounts.destroy', $client) }}" data-confirm="Delete {{ $client->name }}? This cannot be undone.">
                    @csrf
                    @method('delete')
                </form>
            @empty
                <div class="empty">No clients yet.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <div class="section-head"><div><h2>Employees</h2><p>Open an account to edit cost, phone, login, or delete it.</p></div></div>
        <div class="account-card-list">
            @forelse($employees as $employee)
                <article class="account-card">
                    <div>
                        <strong>{{ $employee->name }}</strong>
                        <small>{{ $employee->username }} &middot; &pound;{{ number_format((float) ($employee->employeeProfile?->hourly_rate ?? 0), 2) }}/h</small>
                    </div>
                    <span class="pill">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                    <button class="secondary-button" type="button" data-open-modal="account-modal-{{ $employee->id }}">Edit</button>
                    <button class="danger-button" form="delete-account-{{ $employee->id }}">Delete</button>
                </article>

                <dialog class="order-modal" id="account-modal-{{ $employee->id }}">
                    <div class="modal-shell">
                        <div class="modal-head">
                            <div><p class="eyebrow">Employee account</p><h2>{{ $employee->name }}</h2></div>
                            <button class="icon-button" type="button" data-close-modal aria-label="Close">&times;</button>
                        </div>
                        <form method="post" action="{{ route('admin.accounts.update', $employee) }}" class="form-grid">
                            @csrf
                            @method('put')
                            <input type="hidden" name="is_active" value="0">
                            <label><span>Name</span><input name="name" value="{{ $employee->name }}" required></label>
                            <label><span>Login ID</span><input name="username" value="{{ $employee->username }}" required></label>
                            <label><span>Email</span><input type="email" name="email" value="{{ $employee->email }}"></label>
                            <label><span>Phone</span><input name="phone" value="{{ $employee->employeeProfile?->phone }}"></label>
                            <label><span>Hourly cost</span><input type="number" step="0.01" name="hourly_rate" value="{{ $employee->employeeProfile?->hourly_rate ?? 0 }}"></label>
                            <label><span>New password</span><input name="password" placeholder="Leave blank"></label>
                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($employee->is_active)><span>Active</span></label>
                            <button class="primary-button">Update account</button>
                        </form>
                    </div>
                </dialog>

                <form id="delete-account-{{ $employee->id }}" method="post" action="{{ route('admin.accounts.destroy', $employee) }}" data-confirm="Delete {{ $employee->name }}? This cannot be undone.">
                    @csrf
                    @method('delete')
                </form>
            @empty
                <div class="empty">No employees yet.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <div class="section-head"><div><h2>Kitchen</h2><p>Open an account to edit station details or delete it.</p></div></div>
        <div class="account-card-list">
            @forelse($kitchenUsers as $kitchen)
                <article class="account-card">
                    <div>
                        <strong>{{ $kitchen->name }}</strong>
                        <small>{{ $kitchen->username }} &middot; {{ $kitchen->kitchenProfile?->station ?: 'Kitchen' }}</small>
                    </div>
                    <span class="pill">{{ $kitchen->is_active ? 'Active' : 'Inactive' }}</span>
                    <button class="secondary-button" type="button" data-open-modal="account-modal-{{ $kitchen->id }}">Edit</button>
                    <button class="danger-button" form="delete-account-{{ $kitchen->id }}">Delete</button>
                </article>

                <dialog class="order-modal" id="account-modal-{{ $kitchen->id }}">
                    <div class="modal-shell">
                        <div class="modal-head">
                            <div><p class="eyebrow">Kitchen account</p><h2>{{ $kitchen->name }}</h2></div>
                            <button class="icon-button" type="button" data-close-modal aria-label="Close">&times;</button>
                        </div>
                        <form method="post" action="{{ route('admin.accounts.update', $kitchen) }}" class="form-grid">
                            @csrf
                            @method('put')
                            <input type="hidden" name="is_active" value="0">
                            <label><span>Name</span><input name="name" value="{{ $kitchen->name }}" required></label>
                            <label><span>Login ID</span><input name="username" value="{{ $kitchen->username }}" required></label>
                            <label><span>Email</span><input type="email" name="email" value="{{ $kitchen->email }}"></label>
                            <label><span>Kitchen station</span><input name="station" value="{{ $kitchen->kitchenProfile?->station }}"></label>
                            <label><span>New password</span><input name="password" placeholder="Leave blank"></label>
                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($kitchen->is_active)><span>Active</span></label>
                            <button class="primary-button">Update account</button>
                        </form>
                    </div>
                </dialog>

                <form id="delete-account-{{ $kitchen->id }}" method="post" action="{{ route('admin.accounts.destroy', $kitchen) }}" data-confirm="Delete {{ $kitchen->name }}? This cannot be undone.">
                    @csrf
                    @method('delete')
                </form>
            @empty
                <div class="empty">No kitchen users yet.</div>
            @endforelse
        </div>
    </section>
@endsection

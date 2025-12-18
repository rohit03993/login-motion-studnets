<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Attendance CRM' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-bg: #f3f4f6;
            --brand-card: #ffffff;
            --brand-accent: #f1c40f;
            --brand-accent-2: #e74c3c;
            --brand-text: #0f172a;
            --brand-muted: #6b7280;
            --brand-border: #e5e7eb;
        }
        body {
            padding: 24px 16px;
            background: var(--brand-bg);
            color: var(--brand-text);
            min-height: 100vh;
        }
        .container-wide {
            max-width: 1200px;
            margin: 0 auto 32px auto;
        }
        a { color: var(--brand-accent-2); }
        a:hover { color: #c0392b; }
        .brand-card {
            background: var(--brand-card);
            border: 1px solid var(--brand-border);
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
            padding: 16px;
            color: var(--brand-text);
            margin-bottom: 16px;
        }
        .brand-chip {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(241,196,15,0.18);
            color: #8a6b00;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .muted { color: var(--brand-muted); }
        .table {
            color: var(--brand-text);
            border-color: var(--brand-border);
            font-size: 14px;
        }
        .table thead th {
            white-space: nowrap;
            border-color: var(--brand-border);
            background: #f9fafb;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .table td, .table th {
            border-color: var(--brand-border);
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            --bs-table-accent-bg: #f8fafc;
        }
        .table-hover tbody tr:hover {
            background: rgba(241,196,15,0.12);
            box-shadow: inset 0 1px 0 rgba(0,0,0,0.03);
        }
        .btn-primary {
            background: var(--brand-accent-2);
            border: none;
            color: #fff;
        }
        .btn-primary:hover {
            background: #c0392b;
            color: #fff;
        }
        .btn-outline-secondary {
            border-color: var(--brand-border);
            color: var(--brand-text);
        }
        .btn-outline-secondary:hover {
            background: rgba(0,0,0,0.04);
        }
        .btn-success {
            background: var(--brand-accent);
            border: none;
            color: #0f172a;
        }
        .btn-success:hover {
            background: #ffda3a;
            color: #0f172a;
        }
        .page-link {
            background: transparent;
            border-color: var(--brand-border);
            color: var(--brand-text);
        }
        .page-link:hover {
            background: rgba(0,0,0,0.04);
        }
        .page-item.active .page-link {
            background: var(--brand-accent-2);
            color: #fff;
            border-color: var(--brand-accent-2);
        }
        .pagination svg {
            width: 16px;
            height: 16px;
        }
        .nav-links {
            flex-wrap: wrap;
        }
        .mobile-nav-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            body { padding: 16px 12px; }
            .container-wide { max-width: 100%; margin: 0 auto 24px auto; }
            .nav-links {
                gap: 8px;
                flex-direction: column;
                display: none;
                width: 100%;
                margin-top: 8px;
            }
            .nav-links.show {
                display: flex !important;
            }
            .nav-links a, .nav-links .dropdown > a {
                padding: 8px 4px;
            }
            .brand-card {
                padding: 12px;
            }
            .mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 10px;
            }
        }
        .form-control {
            background: #fff;
            border: 1px solid var(--brand-border);
            color: var(--brand-text);
        }
        .form-control:focus {
            border-color: var(--brand-accent-2);
            box-shadow: 0 0 0 0.15rem rgba(231,76,60,0.15);
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .filters-grid .form-label {
            font-size: 13px;
            color: var(--brand-muted);
        }
        /* Mobile cards for punch list */
        .card-list {
            display: none;
        }
        .punch-card {
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .punch-card .title {
            font-weight: 700;
            margin-bottom: 6px;
            color: #111827;
        }
        .punch-card .meta {
            font-size: 13px;
            color: var(--brand-muted);
            margin-bottom: 4px;
        }
        .punch-card .row-line {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .punch-card .row-line span:first-child {
            color: var(--brand-muted);
        }
        .navbar-brand {
            padding: 16px 0;
            border-bottom: 2px solid var(--brand-border);
            margin-bottom: 24px;
        }
        .brand-logo {
            font-size: 20px;
            font-weight: 700;
            color: var(--brand-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .brand-logo:hover {
            color: var(--brand-accent-2);
        }
        .nav-links {
            margin-left: 24px;
        }
        .nav-link {
            color: var(--brand-muted);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-link:hover {
            color: var(--brand-accent-2);
            background: rgba(231,76,60,0.1);
        }
        .nav-link.active {
            color: var(--brand-accent-2);
            background: rgba(231,76,60,0.15);
            font-weight: 600;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--brand-card) 0%, #f8fafc 100%);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--brand-accent-2);
            margin: 8px 0 4px;
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: var(--brand-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .timeline-item {
            position: relative;
            padding-left: 32px;
            margin-bottom: 16px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: -16px;
            width: 2px;
            background: var(--brand-border);
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-marker {
            position: absolute;
            left: 0;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid var(--brand-card);
            z-index: 1;
        }
        .timeline-marker.in {
            background: #10b981;
        }
        .timeline-marker.out {
            background: #ef4444;
        }
        .punch-row-single {
            background: #fff;
            transition: all 0.2s;
        }
        .punch-row-single:hover {
            background: rgba(241,196,15,0.08);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        @media (max-width: 768px) {
            body { padding: 12px; }
            .table-responsive { margin-top: 8px; }
            .table-wrap { display: none; }
            .card-list { display: block; }
            .navbar-brand { margin-bottom: 16px; }
            .nav-links { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container-wide">
    <nav class="navbar-brand mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('/attendance') }}" class="brand-logo">
                    <i class="bi bi-clock-history"></i>
                    <span>Attendance CRM</span>
                </a>
                @php
                    $canViewEmployees = auth()->user()->isSuperAdmin() || (auth()->user()->can_view_employees ?? false);
                @endphp
                <div class="nav-links d-none d-md-flex gap-3">
                    <a href="{{ url('/attendance') }}" class="nav-link {{ request()->is('attendance*') && !request()->is('students*') ? 'active' : '' }}">
                        <i class="bi bi-list-ul"></i> Live Attendance
                    </a>
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle {{ request()->is('students*') ? 'active' : '' }}" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> Students
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('students.index') }}">
                                <i class="bi bi-list-ul"></i> All Students
                            </a></li>
                            @if(auth()->user()->isSuperAdmin())
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('courses.index') }}">
                                    <i class="bi bi-book"></i> Manage Classes
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('employees.index') }}">
                                    <i class="bi bi-people-fill"></i> Manage Employees
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('permissions.edit') }}">
                                    <i class="bi bi-shield-lock"></i> Staff Permissions
                                </a></li>
                                {{-- Batches menu hidden; batches created via class flow --}}
                            @endif
                        </ul>
                    </div>
                    @if($canViewEmployees)
                        <a href="{{ route('employees.attendance') }}" class="nav-link {{ request()->routeIs('employees.attendance') ? 'active' : '' }}">
                            <i class="bi bi-briefcase"></i> Employee Attendance
                        </a>
                    @endif
                    <a href="{{ route('manual-attendance.index') }}" class="nav-link {{ request()->is('manual-attendance*') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> Manual Attendance
                    </a>
                    <a href="{{ url('/settings') }}" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </div>
                <button class="btn btn-outline-secondary btn-sm mobile-nav-toggle d-md-none" id="mobileNavToggle">
                    <i class="bi bi-list"></i> Menu
                </button>
                <div class="nav-links gap-3 d-md-none" id="mobileNavLinks">
                    <a href="{{ url('/attendance') }}" class="nav-link {{ request()->is('attendance*') && !request()->is('students*') ? 'active' : '' }}">
                        <i class="bi bi-list-ul"></i> Live Attendance
                    </a>
                    <a href="{{ route('students.index') }}" class="nav-link {{ request()->is('students') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> All Students
                    </a>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('courses.index') }}" class="nav-link {{ request()->is('students/courses*') ? 'active' : '' }}">
                            <i class="bi bi-book"></i> Manage Classes
                        </a>
                        <a href="{{ route('employees.index') }}" class="nav-link {{ request()->is('employees*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i> Manage Employees
                        </a>
                        <a href="{{ route('permissions.edit') }}" class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                            <i class="bi bi-shield-lock"></i> Staff Permissions
                        </a>
                    @endif
                    @if($canViewEmployees)
                        <a href="{{ route('employees.attendance') }}" class="nav-link {{ request()->routeIs('employees.attendance') ? 'active' : '' }}">
                            <i class="bi bi-briefcase"></i> Employee Attendance
                        </a>
                    @endif
                    <a href="{{ route('manual-attendance.index') }}" class="nav-link {{ request()->is('manual-attendance*') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> Manual Attendance
                    </a>
                    <a href="{{ url('/settings') }}" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @auth
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                            @if(auth()->user()->isSuperAdmin())
                                <span class="badge bg-warning text-dark ms-1">Admin</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isSuperAdmin())
                                <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="bi bi-people"></i> Manage Users</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('profile.change-password') }}"><i class="bi bi-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
                <div class="text-muted small d-none d-md-block">
                    <i class="bi bi-database"></i> Real-time sync
                </div>
            </div>
        </div>
    </nav>
    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('mobileNavToggle');
        const links = document.getElementById('mobileNavLinks');
        if (toggle && links) {
            toggle.addEventListener('click', function() {
                links.classList.toggle('show');
            });
        }
    });
</script>
@stack('scripts')
</body>
</html>


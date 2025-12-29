<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
        @media (max-width: 768px) {
            .table thead th {
                white-space: normal;
            }
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
            * {
                box-sizing: border-box;
            }
            html {
                width: 100%;
                margin: 0;
                padding: 0;
                overflow-x: hidden;
            }
            body {
                width: 100%;
                margin: 0;
                padding: 12px 8px;
                overflow-x: hidden;
            }
            .container-wide { 
                max-width: 100%;
                width: 100%;
                margin: 0 auto;
                padding: 0;
            }
            .navbar-brand {
                width: 100%;
                margin: 0 0 16px 0;
                padding: 12px 8px;
            }
            .navbar-brand > div {
                width: 100%;
                flex-wrap: wrap;
            }
            .navbar-brand .d-flex {
                width: 100%;
            }
            .nav-links {
                gap: 4px;
                flex-direction: column;
                display: none !important;
                width: 100%;
                margin-top: 8px;
                max-height: calc(100vh - 120px);
                overflow-y: auto;
            }
            .nav-links.show {
                display: flex !important;
            }
            .nav-links a, .nav-links .dropdown > a {
                padding: 12px 16px;
                border-radius: 8px;
                width: 100%;
                white-space: normal;
                word-wrap: break-word;
                display: flex;
                align-items: center;
                gap: 8px;
                min-height: 44px;
            }
            .nav-links a i {
                flex-shrink: 0;
                width: 20px;
                text-align: center;
            }
            .nav-links a span, .nav-links a {
                flex: 1;
                overflow: visible;
                text-overflow: clip;
            }
            .nav-links a.ps-4 {
                padding-left: 40px;
                font-size: 0.95rem;
            }
            .nav-links .nav-link.fw-bold {
                white-space: normal;
                padding: 12px 16px;
            }
            .brand-card {
                padding: 12px;
                width: 100%;
                margin-left: 0;
                margin-right: 0;
            }
            .mobile-nav-toggle {
                display: none !important;
            }
            .table-responsive { 
                margin-top: 8px; 
            }
            .table-wrap { 
                display: none; 
            }
            .card-list { 
                display: block; 
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
            padding: 16px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 300% 300%;
            animation: gradientShift 15s ease infinite;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            margin-bottom: 24px;
            border: none;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .navbar-brand > div {
            flex-wrap: nowrap !important;
            overflow-x: auto;
            position: relative;
        }
        .navbar-brand > div::-webkit-scrollbar {
            display: none;
        }
        .navbar-brand > div {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .dropdown {
            position: relative;
            z-index: 1000;
        }
        .dropdown-menu {
            z-index: 1050 !important;
            position: absolute !important;
            display: none !important;
            visibility: hidden;
            min-width: 200px;
            background: var(--brand-card) !important;
            border: 1px solid var(--brand-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 4px 0;
            margin-top: 4px;
            top: 100%;
            left: 0;
        }
        .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .dropdown-menu-end {
            right: 0 !important;
            left: auto !important;
        }
        /* Force override Bootstrap's inline styles when shown */
        .dropdown-menu.show[style] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .dropdown-item {
            padding: 8px 16px;
            color: var(--brand-text);
            text-decoration: none;
            display: block;
            white-space: nowrap;
        }
        .dropdown-item:hover {
            background: rgba(231,76,60,0.1);
            color: var(--brand-accent-2);
        }
        .dropdown-item i {
            margin-right: 8px;
        }
        .dropdown-toggle {
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        .dropdown-toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 16px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        .dropdown-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
        .brand-logo img {
            height: 40px;
            max-width: 200px;
            object-fit: contain;
        }
        .nav-links {
            margin-left: 0;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            font-weight: 500;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        @media (max-width: 768px) {
            .nav-link {
                white-space: normal;
            }
        }
        .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .nav-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.3);
            font-weight: 700;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            transform: translateY(-1px);
        }
        /* Specific colors for each nav item */
        .nav-link[href*="attendance"]:not([href*="employees"]):not([href*="manual"]):not([href*="students"]).active {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.4), rgba(5, 150, 105, 0.5));
        }
        .nav-link[href*="students"].active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.4), rgba(37, 99, 235, 0.5));
        }
        .nav-link[href*="employees"].active {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.4), rgba(249, 115, 22, 0.5));
        }
        .nav-link[href*="manual-attendance"].active {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.4), rgba(147, 51, 234, 0.5));
        }
        .dropdown-toggle-btn.active {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.4), rgba(219, 39, 119, 0.5));
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.5);
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
            .table-responsive { margin-top: 8px; }
            .table-wrap { display: none; }
            .card-list { display: block; }
        }
    </style>
</head>
<body>
<div class="container-wide">
    <nav class="navbar-brand mb-4">
        <div class="d-flex align-items-center gap-2" style="flex-wrap: nowrap !important; white-space: nowrap;">
            @php
                $companyLogo = \App\Models\Setting::get('company_logo');
                $canViewEmployees = auth()->user()->isSuperAdmin() || (auth()->user()->can_view_employees ?? false);
            @endphp
            @if($companyLogo)
                <a href="{{ url('/attendance') }}" class="brand-logo" style="flex-shrink: 0; margin-right: 8px;">
                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="Company Logo" style="max-height: 35px; max-width: 150px; object-fit: contain;">
                </a>
            @endif
            <div class="nav-links d-none d-md-flex gap-1" style="flex-shrink: 0;">
                <a href="{{ url('/attendance') }}" class="nav-link {{ request()->is('attendance*') && !request()->is('students*') ? 'active' : '' }}">
                    <i class="bi bi-list-ul"></i> Live Attendance
                </a>
                <a href="{{ route('students.index') }}" class="nav-link {{ request()->is('students*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Students
                </a>
                @if($canViewEmployees)
                    <a href="{{ route('employees.attendance') }}" class="nav-link {{ request()->routeIs('employees.attendance') ? 'active' : '' }}">
                        <i class="bi bi-briefcase"></i> Employee Attendance
                    </a>
                @endif
                <a href="{{ route('manual-attendance.index') }}" class="nav-link {{ request()->is('manual-attendance*') ? 'active' : '' }}">
                    <i class="bi bi-pencil-square"></i> Manual Attendance
                </a>
                @if(auth()->user()->isSuperAdmin())
                <div class="dropdown" id="settingsDropdownContainer">
                    <button type="button" class="nav-link dropdown-toggle-btn {{ request()->is('settings*') || request()->is('students/courses*') || (request()->is('employees*') && !request()->routeIs('employees.attendance')) || request()->routeIs('permissions.*') ? 'active' : '' }}" id="settingsDropdownBtn">
                        <i class="bi bi-gear"></i> Settings
                    </button>
                    <ul class="dropdown-menu" id="settingsDropdownMenu">
                        <li><a class="dropdown-item" href="{{ url('/settings') }}">
                            <i class="bi bi-gear"></i> System Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('courses.index') }}">
                            <i class="bi bi-book"></i> Manage Classes
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('employees.index') }}">
                            <i class="bi bi-people-fill"></i> Manage Employees
                        </a></li>
                    </ul>
                </div>
                @endif
            </div>
            @auth
                <div class="dropdown ms-auto" id="userDropdownContainer" style="flex-shrink: 0;">
                    <button type="button" class="dropdown-toggle-btn d-flex align-items-center gap-1" id="userDropdownBtn" style="white-space: nowrap;">
                        <i class="bi bi-person-circle"></i> <span style="white-space: nowrap;">{{ auth()->user()->name }}</span>
                        @if(auth()->user()->isSuperAdmin())
                            <span class="badge" style="background: rgba(255, 193, 7, 0.9); color: #000; font-weight: 600;">Admin</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenu">
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
        </div>
    </nav>
    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Dropdown fix v2 - Cache bust: 2025-12-22 -->
<script>
    // SIMPLE DROPDOWN SOLUTION - No Bootstrap interference
    console.log('=== DROPDOWN DEBUG START ===');
    
    function showMenu(menu, button) {
        console.log('Showing menu:', menu.id);
        
        // Get button position
        var buttonRect = button.getBoundingClientRect();
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        // Calculate position relative to viewport
        var top = buttonRect.bottom + scrollTop;
        var left = buttonRect.left + scrollLeft;
        
        console.log('Button rect:', buttonRect);
        console.log('Calculated position - top:', top, 'left:', left);
        
        // Move menu to body temporarily to avoid overflow clipping
        var originalParent = menu.parentElement;
        if (menu.parentElement !== document.body) {
            menu.setAttribute('data-original-parent', 'true');
            document.body.appendChild(menu);
        }
        
        menu.classList.add('show');
        // Use fixed positioning to position relative to viewport
        menu.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; position: fixed !important; z-index: 99999 !important; top: ' + (buttonRect.bottom + 4) + 'px !important; left: ' + left + 'px !important; transform: none !important; margin: 0 !important; min-width: 200px !important;';
        
        // For dropdown-menu-end, align to right edge of button
        if (menu.classList.contains('dropdown-menu-end')) {
            menu.style.left = 'auto !important';
            menu.style.right = (window.innerWidth - buttonRect.right) + 'px !important';
        }
        
        console.log('Menu style after show:', menu.style.cssText);
        console.log('Menu now in body:', menu.parentElement === document.body);
    }
    
    function hideMenu(menu) {
        console.log('Hiding menu:', menu.id);
        menu.classList.remove('show');
        menu.style.cssText = 'display: none !important; visibility: hidden !important;';
    }
    
    function initDropdowns() {
        console.log('=== INITIALIZING DROPDOWNS ===');
        
        var settingsBtn = document.getElementById('settingsDropdownBtn');
        var settingsMenu = document.getElementById('settingsDropdownMenu');
        var userBtn = document.getElementById('userDropdownBtn');
        var userMenu = document.getElementById('userDropdownMenu');
        
        console.log('Settings button found:', !!settingsBtn);
        console.log('Settings menu found:', !!settingsMenu);
        console.log('User button found:', !!userBtn);
        console.log('User menu found:', !!userMenu);
        
        if (!settingsBtn || !settingsMenu) {
            console.error('SETTINGS DROPDOWN ELEMENTS MISSING!');
        }
        if (!userBtn || !userMenu) {
            console.error('USER DROPDOWN ELEMENTS MISSING!');
        }
        
        // Hide menus initially
        if (settingsMenu) {
            hideMenu(settingsMenu);
            console.log('Settings menu hidden');
        }
        if (userMenu) {
            hideMenu(userMenu);
            console.log('User menu hidden');
        }
        
        // Settings dropdown
        if (settingsBtn && settingsMenu) {
            settingsBtn.addEventListener('click', function(e) {
                console.log('SETTINGS BUTTON CLICKED!');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var isShown = settingsMenu.classList.contains('show');
                console.log('Settings menu currently shown:', isShown);
                
                // Hide user menu
                if (userMenu) hideMenu(userMenu);
                
                // Toggle settings menu
                if (isShown) {
                    hideMenu(settingsMenu);
                } else {
                    showMenu(settingsMenu, settingsBtn);
                    console.log('Menu should be visible now. Checking position...');
                    console.log('Menu computed display:', window.getComputedStyle(settingsMenu).display);
                    console.log('Menu computed visibility:', window.getComputedStyle(settingsMenu).visibility);
                    console.log('Menu getBoundingClientRect:', settingsMenu.getBoundingClientRect());
                }
            }, true); // Use capture phase to run before document listener
            console.log('Settings dropdown handler attached');
        }
        
        // User dropdown
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', function(e) {
                console.log('USER BUTTON CLICKED!');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                var isShown = userMenu.classList.contains('show');
                console.log('User menu currently shown:', isShown);
                
                // Hide settings menu
                if (settingsMenu) hideMenu(settingsMenu);
                
                // Toggle user menu
                if (isShown) {
                    hideMenu(userMenu);
                } else {
                    showMenu(userMenu, userBtn);
                    console.log('Menu should be visible now. Checking position...');
                    console.log('Menu computed display:', window.getComputedStyle(userMenu).display);
                    console.log('Menu computed visibility:', window.getComputedStyle(userMenu).visibility);
                    console.log('Menu getBoundingClientRect:', userMenu.getBoundingClientRect());
                }
            }, true); // Use capture phase to run before document listener
            console.log('User dropdown handler attached');
        }
        
        // Close on outside click - but ONLY after button handlers have run
        // Use a flag to prevent immediate closing
        var dropdownClickInProgress = false;
        
        // Set flag when button is clicked
        if (settingsBtn) {
            settingsBtn.addEventListener('mousedown', function() {
                dropdownClickInProgress = true;
                setTimeout(function() { dropdownClickInProgress = false; }, 200);
            });
        }
        if (userBtn) {
            userBtn.addEventListener('mousedown', function() {
                dropdownClickInProgress = true;
                setTimeout(function() { dropdownClickInProgress = false; }, 200);
            });
        }
        
        // Close on outside click - with delay to allow button click to complete
        document.addEventListener('click', function(e) {
            // Don't process if a dropdown click is in progress
            if (dropdownClickInProgress) {
                console.log('Ignoring click - dropdown click in progress');
                return;
            }
            
            // Don't close if clicking inside dropdown
            var clickedDropdown = e.target.closest('.dropdown');
            if (!clickedDropdown) {
                // Small delay to ensure button click handler has finished
                setTimeout(function() {
                    if (settingsMenu && settingsMenu.classList.contains('show')) {
                        console.log('Closing settings menu (outside click)');
                        hideMenu(settingsMenu);
                    }
                    if (userMenu && userMenu.classList.contains('show')) {
                        console.log('Closing user menu (outside click)');
                        hideMenu(userMenu);
                    }
                }, 50);
            }
        });
        
        console.log('=== DROPDOWNS INITIALIZED ===');
    }
    
    // Run multiple times to ensure it works
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing dropdowns');
            setTimeout(initDropdowns, 50);
            setTimeout(initDropdowns, 200);
            setTimeout(initDropdowns, 500);
        });
    } else {
        console.log('DOM already loaded, initializing dropdowns');
        initDropdowns();
        setTimeout(initDropdowns, 100);
        setTimeout(initDropdowns, 300);
    }
    
    console.log('=== DROPDOWN DEBUG END ===');
</script>
@stack('scripts')
</body>
</html>


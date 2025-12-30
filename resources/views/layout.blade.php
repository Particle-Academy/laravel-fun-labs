<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Laravel Fun Lab')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        nav {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        nav a {
            color: #666;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        nav a:hover {
            background: #f0f0f0;
        }
        nav a.active {
            background: #007bff;
            color: #fff;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { margin-bottom: 20px; color: #333; }
        h2 { margin-bottom: 15px; color: #555; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-primary { background: #007bff; color: #fff; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-warning { background: #ffc107; color: #333; }
        .pagination {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        .pagination a:hover {
            background: #f0f0f0;
        }
        .pagination .active {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container">
        <header>
            <h1>🎮 Laravel Fun Lab</h1>
            <nav>
                <a href="{{ route('lfl.leaderboards.index', ['type' => 'App\\Models\\User']) }}">Leaderboards</a>
                <a href="{{ route('lfl.admin.index') }}">Admin Dashboard</a>
            </nav>
        </header>

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>


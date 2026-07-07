<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — ERP Classico</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 .5rem 2rem rgba(0,0,0,.12);
        }
        .login-header {
            background: #1a3c5e;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container" style="max-width:420px">
    <div class="card login-card overflow-hidden">
        <div class="login-header">
            <i class="bi bi-scissors fs-1 d-block mb-2"></i>
            <h1 class="h4 fw-bold mb-0">ERP Classico</h1>
            <p class="mb-0 opacity-75 small">Production Planning System</p>
        </div>
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ $errors->first() }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="you@company.com"
                               autocomplete="email"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               required>
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label text-muted" for="remember">Keep me signed in</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('password.forgot') }}" class="text-muted small">
                    <i class="bi bi-key me-1"></i>Forgot password?
                </a>
            </div>

        </div>
        <div class="card-footer bg-white text-center text-muted border-0 pb-4" style="font-size:.8rem">
            ERP Classico &copy; {{ date('Y') }}
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmzGVCcWhNxjnbEiP0UtzHnpUj15"
        crossorigin="anonymous"></script>
</body>
</html>

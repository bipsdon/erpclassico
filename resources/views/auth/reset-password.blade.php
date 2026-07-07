<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — ERP Classico</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background:#f0f4f8; min-height:100vh; display:flex; align-items:center; }
        .login-card { border:none; border-radius:1rem; box-shadow:0 .5rem 2rem rgba(0,0,0,.12); }
        .login-header { background:#1a3c5e; border-radius:1rem 1rem 0 0; padding:2rem; text-align:center; color:#fff; }
    </style>
</head>
<body>
<div class="container" style="max-width:440px">
    <div class="card login-card overflow-hidden">
        <div class="login-header">
            <i class="bi bi-shield-lock fs-1 d-block mb-2"></i>
            <h1 class="h4 fw-bold mb-0">Set New Password</h1>
            <p class="mb-0 opacity-75 small">ERP Classico</p>
        </div>
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="text"
                           class="form-control bg-light"
                           value="{{ $email }}"
                           readonly>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">
                        New Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min. 8 characters"
                               required autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold">
                        Confirm Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="form-control"
                               placeholder="Repeat new password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-check-circle me-2"></i>Reset Password
                </button>
            </form>

        </div>
        <div class="card-footer bg-white text-center border-0 pb-3">
            <a href="{{ route('login') }}" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Back to Sign In
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmzGVCcWhNxjnbEiP0UtzHnpUj15"
        crossorigin="anonymous"></script>
</body>
</html>

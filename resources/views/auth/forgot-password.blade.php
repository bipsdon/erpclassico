<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password — ERP Classico</title>
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
<div class="container" style="max-width:460px">
    <div class="card login-card overflow-hidden">
        <div class="login-header">
            <i class="bi bi-key fs-1 d-block mb-2"></i>
            <h1 class="h4 fw-bold mb-0">Reset Password</h1>
            <p class="mb-0 opacity-75 small">ERP Classico</p>
        </div>
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Show token to admin once generated --}}
            @if(session('reset_token'))
                <div class="alert alert-success">
                    <div class="fw-semibold mb-2">
                        <i class="bi bi-check-circle-fill me-1"></i>Reset token generated
                    </div>
                    <p class="mb-2 small">
                        Share this token with <strong>{{ session('reset_email') }}</strong>
                        so they can reset their password. It expires in <strong>60 minutes</strong>.
                    </p>
                    <div class="d-flex align-items-center gap-2">
                        <code class="bg-white border rounded px-2 py-1 flex-grow-1 text-break"
                              id="reset-token-display"
                              style="font-size:.8rem;word-break:break-all">
                            {{ session('reset_token') }}
                        </code>
                        <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                onclick="navigator.clipboard.writeText(document.getElementById('reset-token-display').textContent.trim())
                                         .then(() => this.innerHTML = '<i class=\'bi bi-check2\'></i>')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <hr class="my-3">
                    <p class="mb-1 small text-muted">Direct reset link for the user:</p>
                    <code class="small text-break">
                        {{ route('password.reset.form') }}?token={{ session('reset_token') }}&email={{ urlencode(session('reset_email')) }}
                    </code>
                </div>
            @endif

            <p class="text-muted small mb-3">
                Enter the user's email address. A reset token will be generated for you to share with them.
            </p>

            <form method="POST" action="{{ route('password.send-token') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="user@company.com"
                               required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-key me-2"></i>Generate Reset Token
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

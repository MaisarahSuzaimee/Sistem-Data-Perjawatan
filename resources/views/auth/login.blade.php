@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
            <div class="card px-sm-6 px-0">
                <div class="card-body">
                    <!-- Logo -->
                    <div class="app-brand justify-content-center mb-4">
                        <span class="app-brand-text demo text-heading fw-bold fs-4">
                            {{ config('app.name') }}
                        </span>
                    </div>

                    <h4 class="mb-1">Selamat Datang! 👋</h4>
                    <p class="mb-6">Sila log masuk untuk meneruskan</p>

                    <!-- <form method="POST" action="{{ route('login') }}" class="mb-6"> -->
                    <form method="POST" action="{{ route('login.post') }}" class="mb-6">
                        @csrf

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email') }}"
                                placeholder="Masukkan email anda" autofocus />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4 form-password-toggle">
                            <label class="form-label" for="password">Kata Laluan</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    name="password" placeholder="············" />
                                <span class="input-group-text cursor-pointer">
                                    <i class="icon-base bx bx-hide"></i>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <div class="mb-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                                <label class="form-check-label" for="remember">Ingat Saya</label>
                            </div>
                        </div>

                        <button class="btn btn-primary d-grid w-100" type="submit">Log Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
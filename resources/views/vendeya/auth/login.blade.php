@extends('vendeya.layouts.app')

@section('title', 'Vendeya - Iniciar Sesión')
@section('body-class', 'login-page')

@section('content')
<div class="login-container">
    <div class="login-left">
        <div class="logo-container">
            <img src="{{ $logo ?? 'https://factreadylite.fe-estudioscreativos.com/storage/uploads/logos/logo_44444444444.png' }}" alt="FactReady Lite" class="login-logo">
        </div>
    </div>

    <div class="login-right">
        <div class="theme-toggle-login">
            <button class="theme-btn" id="themeToggleLogin" title="Cambiar tema">
                <i class="fas fa-moon"></i>
            </button>
        </div>

        <div class="login-form-container">
            <div class="login-header">
                <h2 class="login-title">Bienvenido.</h2>
                <p class="login-subtitle">Por favor inicia sesión en tu cuenta</p>
            </div>

            <form class="login-form" id="loginForm">
                @csrf
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="email" name="email" placeholder="Correo electrónico" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <button type="button" class="password-toggle" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Ingresar</span>
                    <span class="btn-spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p>Ingresa con tus credenciales de FactReady Lite</p>
            </div>
        </div>

        <div class="toast-notification" id="syncToast">
            <i class="fas fa-sync-alt toast-icon"></i>
            <span>Sincronizando información</span>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #1f7a83;
    --primary-hover: #2a9aa8;
    --text-primary: #ffffff;
    --text-secondary: #a0b3b8;
    --bg-left: #1c2b2f;
    --bg-right: #0f2027;
    --input-bg: #1a2d32;
    --input-border: #2d4a50;
}

[data-theme="light"] {
    --primary: #1f6f78;
    --primary-hover: #2a8a94;
    --text-primary: #0f4c5c;
    --text-secondary: #7a8a91;
    --bg-left: #f1f1f1;
    --bg-right: #ffffff;
    --input-bg: #ffffff;
    --input-border: #d1d5db;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body.login-page { font-family: 'Inter', sans-serif; min-height: 100vh; }
.login-container { display: flex; min-height: 100vh; }
.login-left { width: 50%; background: var(--bg-left); display: flex; align-items: center; justify-content: center; padding: 40px; }
.logo-container { text-align: center; }
.logo-container { text-align: center; }
.login-logo { max-width: 400px; width: 100%; height: auto; margin: 0 auto 24px; display: block; border-radius: 0; }
.brand-name { font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
.brand-tagline { font-size: 14px; color: var(--text-secondary); }
.login-right { width: 50%; background: var(--bg-right); display: flex; align-items: center; justify-content: center; padding: 40px; position: relative; }
.theme-toggle-login { position: absolute; top: 20px; right: 20px; }
.theme-btn { width: 44px; height: 44px; border-radius: 12px; border: none; background: var(--input-bg); color: var(--text-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; font-size: 18px; }
.theme-btn:hover { background: var(--primary); color: white; }
.login-form-container { width: 100%; max-width: 400px; }
.login-header { margin-bottom: 40px; }
.login-title { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
.login-subtitle { font-size: 16px; color: var(--text-secondary); }
.form-group { margin-bottom: 20px; }
.input-wrapper { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 16px; color: var(--text-secondary); font-size: 16px; }
.input-wrapper input { width: 100%; padding: 16px 48px; border: 2px solid var(--input-border); border-radius: 12px; background: var(--input-bg); color: var(--text-primary); font-size: 16px; transition: all 0.3s ease; }
.input-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(31, 122, 131, 0.15); }
.input-wrapper input::placeholder { color: var(--text-secondary); }
.password-toggle { position: absolute; right: 16px; background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0; }
.password-toggle:hover { color: var(--primary); }
.btn-login { width: 100%; padding: 16px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-login:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(31, 122, 131, 0.4); }
.btn-login:disabled { opacity: 0.7; cursor: not-allowed; }
.login-footer { margin-top: 24px; text-align: center; }
.login-footer p { font-size: 13px; color: var(--text-secondary); }
.toast-notification { position: fixed; bottom: 24px; right: 24px; background: var(--primary); color: white; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); transform: translateX(calc(100% + 24px)); transition: transform 0.4s ease; z-index: 1000; }
.toast-notification.show { transform: translateX(0); }
.toast-icon { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
@media (max-width: 768px) { .login-container { flex-direction: column; } .login-left, .login-right { width: 100%; } .login-left { padding: 60px 40px; } }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('vendeya-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    document.getElementById('themeToggleLogin').addEventListener('click', function() {
        const current = document.documentElement.getAttribute('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('vendeya-theme', newTheme);
        updateThemeIcon(newTheme);
    });

    function updateThemeIcon(theme) {
        const icon = document.querySelector('#themeToggleLogin i');
        icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
    }

    window.togglePassword = function(btn) {
        const input = btn.closest('.input-wrapper').querySelector('input');
        const icon = btn.querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
        else { input.type = 'password'; icon.className = 'fas fa-eye'; }
    };

    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnSpinner = btn.querySelector('.btn-spinner');
    const toast = document.getElementById('syncToast');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline';
        toast.classList.add('show');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch('{{ route("vendeya.login") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            toast.classList.remove('show');

            if (result.success) { window.location.href = result.redirect; }
            else {
                console.log('Login error:', result);
                Swal.fire({ icon: 'error', title: 'Error', text: result.message + (result.debug ? '\n\nDebug: ' + JSON.stringify(result.debug) : ''), confirmButtonColor: '#1f7a83' });
                btn.disabled = false; btnText.style.display = 'inline'; btnSpinner.style.display = 'none';
            }
        } catch (error) {
            toast.classList.remove('show');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error', confirmButtonColor: '#1f7a83' });
            btn.disabled = false; btnText.style.display = 'inline'; btnSpinner.style.display = 'none';
        }
    });
});
</script>
@endpush

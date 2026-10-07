<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · {{ config('salon.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nail-master-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="flex min-h-dvh flex-col px-4 py-6 sm:p-8">
        <section aria-labelledby="login-title" class="panel mx-auto my-auto w-full max-w-md p-6 shadow-sm sm:p-8">
            <header class="mb-7 flex flex-col items-center gap-3 text-center">
                <a href="{{ route('home') }}"><x-brand-logo class="w-44"/></a>
                <div>
                    <h1 id="login-title" class="text-2xl font-semibold">Connexion</h1>
                    <p class="mt-2 text-sm text-muted">Bienvenue dans votre espace salon.</p>
                </div>
            </header>

            @if($errors->any())
                <div role="alert" class="mb-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
                @csrf
                <x-field name="email" label="Adresse e-mail" type="email" required autocomplete="username" class="text-base sm:text-sm"/>
                <x-field name="password" label="Mot de passe" type="password" required autocomplete="current-password" class="text-base sm:text-sm"/>
                <label class="flex min-h-11 w-fit items-center gap-2 text-muted">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Rester connecté
                </label>
                <button class="btn min-h-12 w-full">Se connecter</button>
            </form>

            <a href="{{ route('home') }}" class="link mt-6 block text-center text-sm">Retour au site du salon</a>
            <p class="mt-6 text-center text-xs leading-5 text-muted">
                Besoin d’un accès ? Contactez l’administrateur du salon.
            </p>
        </section>
    </main>
</body>
</html>

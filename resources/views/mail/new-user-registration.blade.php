<x-mail::message>
    # Welcome to MeemMark!

    Hello {{ $user->display_name ?? $user->name }},

    Your account has been created successfully. Here are your login credentials:

    **Username:** {{ $user->login }}<br>
    **Password:** {{ $plainPassword }}

    Please log in and change your password immediately.

    <x-mail::button :url="config('app.url') . '/login'">
        Log In
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
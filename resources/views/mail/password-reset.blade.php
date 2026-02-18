<x-mail::message>
    # Password Reset Request

    Hello {{ $user->display_name ?? $user->name }},

    Someone requested a password reset for your account. Use the token below to reset your password:

    **Reset Token:** `{{ $token }}`

    This token will expire in 24 hours.

    If you did not request a password reset, please ignore this email.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
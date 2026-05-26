<!doctype html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.staff_welcome_subject') }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;direction: {{ ($locale ?? 'en') === 'ar' ? 'rtl' : 'ltr' }};text-align: {{ ($locale ?? 'en') === 'ar' ? 'right' : 'left' }};">
    <div style="max-width:640px;margin:0 auto;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#065f46,#10b981);padding:28px 24px;color:#ffffff;">
            <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.9;">BookMyPitch</p>
            <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ __('emails.staff_welcome_greeting') }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px 0;font-size:16px;">{{ __('emails.staff_welcome_greeting') }} <strong>{{ e($firstName) }}</strong>,</p>
            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.7;color:#4b5563;">
                {{ __('emails.staff_welcome_msg') }} <strong>{{ e($groundName) }}</strong>.
            </p>

            <div style="border:1px solid #e5e7eb;border-radius:14px;padding:18px;background-color:#f9fafb;">
                <h2 style="margin:0 0 14px 0;font-size:18px;color:#111827;">{{ __('emails.login_credentials') }}</h2>

                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;width:42%;">{{ __('emails.email') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;font-family:'Courier New',monospace;">{{ e($email) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ __('emails.password') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;font-family:'Courier New',monospace;">{{ e($password) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;">{{ __('emails.assigned_ground') }}</td>
                        <td style="padding:10px 0;font-weight:600;">{{ e($groundName) }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-top:24px;text-align:center;">
                <a href="{{ e($loginUrl) }}"
                   style="display:inline-block;padding:14px 32px;background-color:#10b981;color:#ffffff;text-decoration:none;border-radius:10px;font-size:16px;font-weight:600;">
                    {{ __('emails.staff_login_url') }}
                </a>
            </div>

            <div style="margin-top:20px;border:1px solid #fef3c7;border-radius:14px;padding:18px;background-color:#fffbeb;">
                <h2 style="margin:0 0 12px 0;font-size:17px;color:#92400e;">{{ __('emails.important') }}</h2>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#92400e;">{{ __('emails.change_password') }}</p>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#92400e;">{{ __('emails.use_staff_login') }}</p>
                <p style="margin:0;font-size:14px;line-height:1.7;color:#92400e;">{{ __('emails.contact_admin') }}</p>
            </div>
        </div>

        <div style="padding:18px 24px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
            <p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">
                BookMyPitch — {{ __('emails.staff_portal') }}
            </p>
        </div>
    </div>
</body>
</html>

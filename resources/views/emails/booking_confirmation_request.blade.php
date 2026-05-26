<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.booking_confirmation_request_subject') }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;direction: {{ $locale === 'ar' ? 'rtl' : 'ltr' }};text-align: {{ $locale === 'ar' ? 'right' : 'left' }};">
    <div style="max-width:640px;margin:0 auto;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#065f46,#10b981);padding:28px 24px;color:#ffffff;">
            <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.9;">BookMyPitch</p>
            <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ __('emails.booking_confirmation_request_subject') }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 20px 0;font-size:16px;line-height:1.7;color:#4b5563;">
                {{ __('emails.booking_greeting') }} <strong>{{ e($clientName) }}</strong>,
            </p>
            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.7;color:#4b5563;">
                {{ __('emails.booking_confirmation_request_msg') }}
            </p>

            <div style="border:1px solid #e5e7eb;border-radius:14px;padding:18px 18px 8px 18px;background-color:#f9fafb;">
                <h2 style="margin:0 0 14px 0;font-size:18px;color:#111827;">{{ __('emails.booking_details') }}</h2>

                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;width:42%;">{{ __('emails.ground') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;">{{ e($groundName) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ __('emails.terrain') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;">{{ e($terrainName) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ __('emails.date') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;">{{ e($bookingDate) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;">{{ __('emails.time') }}</td>
                        <td style="padding:10px 0;font-weight:600;">{{ e($timeSlot) }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-top:20px;border:1px solid #d1fae5;border-radius:14px;padding:18px;background-color:#ecfdf5;">
                <h2 style="margin:0 0 12px 0;font-size:17px;color:#065f46;">{{ __('emails.action_required') }}</h2>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#065f46;">{{ __('emails.slot_pending') }}</p>
                <p style="margin:0 0 12px 0;font-size:14px;line-height:1.7;color:#065f46;">{{ __('emails.enter_code') }}</p>
                <div style="text-align:center;margin:24px 0;">

                    <table align="center" cellpadding="0" cellspacing="0" style="margin:auto;">
                        <tr>
                            <td style="background:#ecfdf5;border:2px dashed #10b981;border-radius:12px;padding:16px 24px;text-align:center;font-family:monospace;font-size:32px;font-weight:bold;color:#065f46;letter-spacing:8px;">
                                {{ $confirmationCode }}
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top:10px;font-size:13px;color:#6b7280;line-height:1.4;">
                        {{ __('emails.code_expires') }}<br>
                        {{ $expiryFormatted }} ({{ __('emails.morocco_time') }})
                    </p>

                </div>
            </div>
        </div>

        <div style="padding:18px 24px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
            <p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">
                {{ __('emails.auto_generated') }}
            </p>
        </div>
    </div>
</body>
</html>

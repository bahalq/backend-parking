<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.booking_subject') }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;direction: {{ $locale === 'ar' ? 'rtl' : 'ltr' }};text-align: {{ $locale === 'ar' ? 'right' : 'left' }};">
    <div style="max-width:640px;margin:0 auto;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#0e1726,#0891b2);padding:28px 24px;color:#ffffff;">
            <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.9;">Parkova</p>
            <h1 style="margin:0;font-size:28px;line-height:1.2;">{{ __('emails.booking_confirmed_msg') }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px 0;font-size:16px;">{{ __('emails.booking_greeting') }} <strong>{{ e($clientName) }}</strong>,</p>
            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.7;color:#4b5563;">
                {{ __('emails.booking_confirmed_msg') }}
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
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ __('emails.time') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:600;">{{ e($timeSlot) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;">{{ __('emails.price') }}</td>
                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-weight:700;color:#0891b2;">{{ e($totalPrice) }} DH</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;">{{ __('emails.confirmation_code') }}</td>
                        <td style="padding:10px 0;font-weight:700;font-family:'Courier New',monospace;">{{ e($reference) }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin-top:24px;text-align:center;">
                <div style="display:inline-block;padding:16px;border:1px solid #e5e7eb;border-radius:18px;background-color:#ffffff;">
                    <img
                        src="{{ $qrImageUrl }}"
                        alt="QR Code"
                        width="200"
                        height="200"
                        style="display:block;width:200px;height:200px;"
                    >
                </div>

                <p style="margin:18px 0 0 0;font-size:28px;line-height:1.2;color:#111827;font-weight:700;font-family:'Courier New',monospace;">
                    {{ __('emails.confirmation_code') }} : {{ e($reference) }}
                </p>
            </div>

            <div style="margin-top:20px;border:1px solid #cffafe;border-radius:14px;padding:18px;background-color:#ecfeff;">
                <h2 style="margin:0 0 12px 0;font-size:17px;color:#0e7490;">{{ __('emails.how_it_works') }}</h2>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#164e63;">{{ __('emails.present_email') }}</p>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#164e63;">{{ __('emails.staff_scan_qr') }}</p>
                <p style="margin:0 0 8px 0;font-size:14px;line-height:1.7;color:#164e63;">{{ __('emails.reference_sufficient') }}</p>
                <p style="margin:0;font-size:14px;line-height:1.7;color:#164e63;">{{ __('emails.arrive_early') }}</p>
            </div>
        </div>

        <div style="padding:18px 24px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
            <p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">
                {{ __('emails.thank_you') }}
            </p>
        </div>
    </div>
</body>
</html>

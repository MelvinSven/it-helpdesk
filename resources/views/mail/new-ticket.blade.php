<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $ticketCode }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:24px 0;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; overflow:hidden; border:1px solid #e5e7eb;">
<tr>
<td style="background-color:#2563eb; padding:20px 32px; text-align:center; color:#ffffff; font-size:25px;">Notifikasi dari IT Helpdesk Lixicon</td>
</tr>
<tr>
<td style="padding:24px 32px;">
<p style="margin:0 0 16px; font-size:16px;">{{ $leadLine }}</p>
<p style="margin:0 0 16px; font-size:16px; color: #2563eb; font-weight:bold;">{{ $requestorName }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:24px; font-size:14px;">
<tr>
<td style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold; width:120px;">Tanggal</td>
<td style="border:1px solid #d1d5db; padding:10px 12px;">{{ $ticketDate }}</td>
</tr>
<tr>
<td style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold; width:120px;">Kode Tiket</td>
<td style="border:1px solid #d1d5db; padding:10px 12px;">{{ $ticketCode }}</td>
</tr>
<tr>
<td style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Nama</td>
<td style="border:1px solid #d1d5db; padding:10px 12px;">{{ $requestorName }}</td>
</tr>
<tr>
<td style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Email</td>
<td style="border:1px solid #d1d5db; padding:10px 12px;">{{ $requestorEmail }}</td>
</tr>
<tr>
<td style="border:1px solid #d1d5db; padding:10px 12px; background-color:#f9fafb; font-weight:bold;">Deskripsi</td>
<td style="border:1px solid #d1d5db; padding:10px 12px;">{{ $ticketDescription }}</td>
</tr>
</table>

@if (! empty($noticeLine))
<p style="margin:0 0 24px; font-size:14px; line-height:1.5;">{{ $noticeLine }}</p>
@endif

<table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
<tr>
<td style="background-color:#2563eb;">
<a href="{{ $ticketUrl }}" style="display:inline-block; padding:12px 24px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">{{ $ctaLabel ?? 'Lihat Tiket' }}</a>
</td>
</tr>
</table>

<p style="margin:0; font-size:14px; color:#6b7280;">Terima kasih,<br>{{ config('app.name') }}</p>
</td>
</tr>
</table>
<p style="margin:16px 0 0; font-size:12px; color:#9ca3af; text-align:center;">&copy; {{ date('Y') }} IT Helpdesk Lixicon. All rights reserved.</p>
</td>
</tr>
</table>
</body>
</html>

@component('mail::message')
# Complaint Updated

Hello {{ $complaint->user->first_name }},

Your complaint **{{ $ticketId }}** has been updated.

## New Status: {{ $newStatus }}

@if($note)
**Staff Response:**
> {{ $note }}
@endif

@component('mail::button', ['url' => $complaintUrl, 'color' => 'primary'])
View Complaint Details
@endcomponent

Thank you for using {{ $appName }}.

Regards,  
Daet LGU
@endcomponent

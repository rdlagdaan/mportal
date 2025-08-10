

@component('mail::message')
# New Microcredentials Applicant

**Name:** {{ $user->name }}

**Email:** {{ $user->email }}

**Mobile:** {{ $payload['mobile'] }}

**Consent:** {{ $payload['consent'] ? 'Yes' : 'No' }}

Thanks,
{{ config('app.name') }}
@endcomponent
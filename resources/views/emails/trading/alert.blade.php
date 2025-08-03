@component('mail::message')
# {{ $title }}

{{ $message }}

@if(isset($data['action']))
**Action:** {{ ucfirst($data['action']) }}
@endif

@if(isset($data['symbol']))
**Symbol:** {{ $data['symbol'] }}
@endif

@if(isset($data['price']))
**Price:** {{ number_format($data['price'], 5) }}
@endif

@if(isset($data['profit']))
**Profit:** {{ $data['profit'] >= 0 ? '+' : '' }}{{ number_format($data['profit'], 2) }}
@endif

@if(isset($data['time']))
**Time:** {{ $data['time'] }}
@endif

@component('mail::button', ['url' => config('app.url')])
View Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

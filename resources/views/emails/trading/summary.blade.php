@component('mail::message')
# {{ ucfirst($data['period']) }} Trading Summary

Dear {{ $data['user']->name }},

Here is your {{ $data['period'] }} trading summary from {{ $data['startDate'] }} to {{ $data['endDate'] }}.

## Trading Performance

@component('mail::table')
| Metric | Value |
|:-------|------:|
| Closed Trades | {{ $data['closedTrades'] }} |
| Open Trades | {{ $data['openTrades'] }} |
| Winning Trades | {{ $data['winningTrades'] }} |
| Losing Trades | {{ $data['losingTrades'] }} |
| Win Rate | {{ $data['winRate'] }}% |
| Total Profit | {{ $data['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($data['totalProfit'], 2) }} |
@endcomponent

## Activity Summary

@component('mail::table')
| Metric | Value |
|:-------|------:|
| Logins | {{ $data['activitySummary']['logins'] }} |
| Transactions | {{ $data['activitySummary']['transactions'] }} |
| Deposits | {{ number_format($data['activitySummary']['depositVolume'], 2) }} |
| Withdrawals | {{ number_format($data['activitySummary']['withdrawalVolume'], 2) }} |
@endcomponent

@component('mail::button', ['url' => config('app.url')])
View Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

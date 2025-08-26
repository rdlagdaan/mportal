<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Depreciation Schedule</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 6px; }
    .meta { margin-bottom: 10px; }
    .meta td { padding: 3px 6px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #e8f5e9; text-align: left; }
    tfoot td { font-weight: bold; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h2>Depreciation Schedule — {{ $asset->asset_no }} ({{ $asset->description }})</h2>
  <table class="meta">
    <tr>
      <td><strong>Method:</strong> {{ $schedule['input']['method'] }}</td>
      <td><strong>Cost:</strong> {{ number_format($schedule['input']['cost'], 2) }}</td>
      <td><strong>Life (months):</strong> {{ $schedule['input']['life_months'] }}</td>
      <td><strong>Start:</strong> {{ $schedule['input']['start_date'] }}</td>
    </tr>
    <tr>
      <td><strong>Residual:</strong> {{ number_format($schedule['input']['residual_value'], 2) }}</td>
      <td><strong>Total Depreciation:</strong> {{ number_format($schedule['summary']['total_depreciation'], 2) }}</td>
      <td><strong>Ending NBV:</strong> {{ number_format($schedule['summary']['ending_nbv'], 2) }}</td>
      <td><strong>Generated:</strong> {{ now()->toDateTimeString() }}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Period</th>
        <th>Period Start</th>
        @if(isset($schedule['rows'][0]['units']))<th class="right">Units</th>@endif
        <th class="right">Depreciation</th>
        <th class="right">Accumulated</th>
        <th class="right">Net Book Value</th>
      </tr>
    </thead>
    <tbody>
      @foreach($schedule['rows'] as $r)
        <tr>
          <td>{{ $r['period'] }}</td>
          <td>{{ $r['period_start'] }}</td>
          @if(isset($r['units']))<td class="right">{{ number_format($r['units'], 2) }}</td>@endif
          <td class="right">{{ number_format($r['depreciation'], 2) }}</td>
          <td class="right">{{ number_format($r['accumulated'], 2) }}</td>
          <td class="right">{{ number_format($r['net_book_value'], 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>

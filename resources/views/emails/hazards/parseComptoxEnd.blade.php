<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hazards COMPTox Parse Finished</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 20px; border-radius: 6px; color: #333; line-height: 1.6;">
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal;">
      Hazards COMPTox Parse Finished
    </h2>

    <div style="background-color: #e9f7f6; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
      <p style="margin: 0 0 8px 0;"><strong>Parse Run ID:</strong> {{ $run->id }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Status:</strong> {{ $run->status }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Source API Run ID:</strong> {{ $run->source_api_run_id ?? 'N/A' }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Total payloads:</strong> {{ number_format($run->total_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Processed payloads:</strong> {{ number_format($run->processed_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Successful payloads:</strong> {{ number_format($run->successful_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Failed payloads:</strong> {{ number_format($run->failed_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>New records:</strong> {{ number_format($run->new_records ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Updated records:</strong> {{ number_format($run->updated_records ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Unchanged records:</strong> {{ number_format($run->unchanged_records ?? 0) }}</p>
      <p style="margin: 0;"><strong>Duration:</strong> {{ $run->duration_seconds ?? 0 }} seconds</p>
    </div>

    @php($counts = $run->counts_by_type ?? [])
    @if(!empty($counts))
      <div style="margin-bottom: 20px;">
        <p style="margin: 0 0 8px 0;"><strong>Counts by type:</strong></p>
        <ul style="margin: 0; padding-left: 20px;">
          @foreach($counts as $type => $stats)
            <li style="margin-bottom: 6px;">
              {{ ucfirst($type) }}:
              new {{ number_format($stats['new'] ?? 0) }},
              updated {{ number_format($stats['updated'] ?? 0) }},
              unchanged {{ number_format($stats['unchanged'] ?? 0) }}
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    @if(!empty($run->notes))
      <p style="margin: 0 0 16px 0;"><strong>Notes:</strong> {{ $run->notes }}</p>
    @endif

    <p style="margin-bottom: 0; border-top: 1px solid #eee; padding-top: 20px;">
      NORMAN Database System
    </p>
  </div>
</body>
</html>


<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hazards API Fetch Finished</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 20px; border-radius: 6px; color: #333; line-height: 1.6;">
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal;">
      Hazards API Fetch Finished
    </h2>

    <div style="background-color: #e9f7f6; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
      <p style="margin: 0 0 8px 0;"><strong>Run ID:</strong> {{ $run->id }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Status:</strong> {{ $run->status }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Total DTXIDs:</strong> {{ number_format($run->total_dtxids ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Processed:</strong> {{ number_format($run->processed_dtxids ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>New payloads:</strong> {{ number_format($run->new_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Updated payloads:</strong> {{ number_format($run->updated_payloads ?? 0) }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Failed DTXIDs:</strong> {{ number_format($run->failed_dtxids ?? 0) }}</p>
      <p style="margin: 0;"><strong>Duration:</strong> {{ $run->duration_seconds ?? 0 }} seconds</p>
    </div>

    @if(!empty($run->notes))
      <p style="margin: 0 0 16px 0;"><strong>Notes:</strong> {{ $run->notes }}</p>
    @endif

    <p style="margin-bottom: 0; border-top: 1px solid #eee; padding-top: 20px;">
      NORMAN Database System
    </p>
  </div>
</body>
</html>


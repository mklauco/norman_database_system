<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hazards COMPTox Parse Started</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 20px; border-radius: 6px; color: #333; line-height: 1.6;">
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal;">
      Hazards COMPTox Parse Started
    </h2>

    <div style="background-color: #e9f7f6; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
      <p style="margin: 0 0 8px 0;"><strong>Parse Run ID:</strong> {{ $run->id }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Trigger:</strong> {{ $run->trigger }}</p>
      <p style="margin: 0 0 8px 0;"><strong>Source API Run ID:</strong> {{ $run->source_api_run_id ?? 'N/A' }}</p>
      <p style="margin: 0;"><strong>Started:</strong> {{ optional($run->started_at)->format('Y-m-d H:i:s') }}</p>
    </div>

    <p style="margin-bottom: 0; border-top: 1px solid #eee; padding-top: 20px;">
      NORMAN Database System
    </p>
  </div>
</body>
</html>


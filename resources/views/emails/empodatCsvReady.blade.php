<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Your CSV Export Is Ready</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <!-- Container -->
  <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 20px; border-radius: 6px; color: #333; line-height: 1.6;">
    
    <!-- Heading -->
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal;">
      Your CSV Export Is Ready!
    </h2>
    
    <!-- Intro Paragraph -->
    <p style="margin-bottom: 16px;">
      We’re pleased to let you know that your requested CSV export is now available.
      Click the button below to download your file:
    </p>

    <!-- Download Button -->
    <p style="margin-bottom: 24px;">
      <a href="{{ $messageContent['download_link'] }}"
         style="display: inline-block; padding: 12px 24px; background-color: #2A9D8F; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 14px;">
        Download CSV
      </a>
    </p>

    <!-- Footer / Signature -->
    <p style="margin-bottom: 0;">
      Thank you,<br>
      <strong>Your Team</strong>
    </p>
  </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Залиште відгук про квест</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; background-color: #f4f4f5; padding: 20px; color: #333; }
        .ticket-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px solid #8B5CF6; }
        .header { background-color: #8B5CF6; color: #ffffff; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0 0; font-size: 16px; opacity: 0.9; }
        .content { padding: 30px; text-align: center; }
        .greeting { font-size: 18px; margin-bottom: 20px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-top: 1px dashed #d1d5db; }
    </style>
</head>
<body>
<div class="ticket-container">
    <div class="header">
        <h1>ONEAQUESTS</h1>
        <p>Як вам гра у "{{ $booking->room->name }}"?</p>
    </div>
    <div class="content">
        <div class="greeting">
            Вітаємо, <strong>{{ $booking->guest_name ?? $booking->user?->name ?? 'Гість' }}</strong>!<br><br>
            Сподіваємося, вам сподобався квест. Ми завжди прагнемо стати кращими, тому ваш відгук дуже важливий для нас!
        </div>
        
        <div style="margin-top: 40px; margin-bottom: 20px;">
            <a href="{{ $frontendLink }}"
               style="background-color: #8B5CF6; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;">
                Залишити відгук
            </a>
        </div>
        <p style="font-size: 12px; color: #9ca3af;">Це посилання буде дійсним протягом 7 днів.</p>
    </div>
    <div class="footer">
        Чекаємо на вас знову!<br>
        Команда OneaQuests.
    </div>
</div>
</body>
</html>
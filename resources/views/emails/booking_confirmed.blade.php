<!DOCTYPE html>
<html>
<head>
    <title>Підтвердження бронювання</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px;">
<div style="text-align: center; margin-bottom: 30px;">
    <h1 style="color: #8B5CF6;">OneaQuests</h1>
    <h2>Оплата успішна!</h2>
</div>
<p>Вітаємо, <strong>{{ $booking->guest_name ?? $booking->user?->name ?? 'Гість' }}</strong>!</p>
<p>Ми успішно отримали вашу оплату. Ваше бронювання підтверджено.</p>
<div style="background-color: #f3f4f6; padding: 20px; border-radius: 10px; margin: 20px 0;">
    <h3 style="margin-top: 0;">Деталі гри:</h3>
    <ul style="list-style: none; padding: 0;">
        <li><strong>Квест-кімната:</strong> {{ $booking->room->name }}</li>
        <li><strong>Дата та час:</strong> {{ \Carbon\Carbon::parse($booking->start_time)->format('d.m.Y H:i') }}</li>
        <li><strong>Кількість гравців:</strong> {{ $booking->players_count }}</li>
        <li><strong>Оплачено:</strong> {{ $booking->total_price / 100 }} грн</li>
    </ul>
</div>
<p>Чекаємо на вас за 10 хвилин до початку гри. Бажаємо незабутніх емоцій!</p>
</body>
</html>

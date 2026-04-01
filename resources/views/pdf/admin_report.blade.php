<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Звіт з аналітики</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 14px; color: #000; }
        h1 { color: #000; text-align: center; }
        .box { border: 1px solid #000; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .highlight { font-weight: bold; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        th { background-color: #fff; }
        .signature-section { margin-top: 60px; page-break-inside: avoid; }
        .sign-table { width: 100%; border: none; margin-top: 10px; }
        .sign-table td { border: none; padding: 0; vertical-align: top; }
        .line { border-bottom: 1px solid #000; width: 100%; display: block; height: 30px; }
        .subtext { font-size: 10px; text-align: center; display: block; margin-top: 5px; }
    </style>
</head>
<body>
<h1>Аналітичний звіт OneaRoom</h1>
<p>Дата формування: <strong>{{ $date }}</strong></p>

<div class="box">
    <h3>Загальні показники:</h3>
    <p>Загальний дохід (за весь час): <span class="highlight">{{ number_format($totalRevenue, 0, '.', ' ') }} ₴</span></p>
    <p>Дохід за останні 30 днів: <span class="highlight">{{ number_format($revenueMonth, 0, '.', ' ') }} ₴</span></p>
    <p>Дохід за останні 7 днів: <span class="highlight">{{ number_format($revenueWeek, 0, '.', ' ') }} ₴</span></p>
    <p>Кількість успішних ігор: <span class="highlight">{{ $totalBookings }}</span></p>
</div>

<div class="box">
    <h3>Аналіз кімнат:</h3>
    <p>Найпопулярніша: <strong>{{ $mostBooked->name ?? '—' }}</strong> ({{ $mostBooked->bookings_count ?? 0 }} ігор)</p>
    <p>Найменш популярна: <strong>{{ $leastBooked->name ?? '—' }}</strong> ({{ $leastBooked->bookings_count ?? 0 }} ігор)</p>
    <p>Найкращий рейтинг: <strong>{{ $bestRated->name ?? '—' }}</strong> ({{ number_format($bestRated->reviews_avg_rating ?? 0, 1) }}/5)</p>
    <p>Найнижчий рейтинг: <strong>{{ $worstRated->name ?? '—' }}</strong> ({{ number_format($worstRated->reviews_avg_rating ?? 0, 1) }}/5)</p>
</div>

<h3>Деталізація по кімнатах:</h3>
<table>
    <thead>
    <tr>
        <th>Назва кімнати</th>
        <th>Проведено ігор</th>
        <th>Сер. рейтинг</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rooms as $room)
        <tr>
            <td>{{ $room->name }}</td>
            <td>{{ $room->bookings_count }}</td>
            <td>{{ number_format($room->reviews_avg_rating ?? 0, 1) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="signature-section">
    <table class="sign-table">
        <tr>
            <td style="width: 40%; vertical-align: bottom;">
                <strong>Звіт підготував(-ла):</strong>
            </td>
            <td style="width: 25%; padding-right: 20px;">
                <span class="line"></span>
                <span class="subtext">(підпис)</span>
            </td>
            <td style="width: 35%;">
                <span class="line"></span>
                <span class="subtext">(Прізвище, ініціали)</span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>

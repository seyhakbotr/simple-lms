<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Book Notification</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .book-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .book-list th, .book-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .book-list th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Overdue Book Notice</div>
        <p>Dear {{ $user->name }},</p>
        <p>This is a friendly reminder that the following book(s) are overdue:</p>

        <table class="book-list">
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($overdueTransactions as $transaction)
                    @foreach ($transaction->items as $item)
                        <tr>
                            <td>{{ $item->book->title }}</td>
                            <td>{{ $transaction->due_date->format('Y-m-d') }}</td>
                            <td>{{ $transaction->getDaysOverdue() }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <p>Please return them as soon as possible to avoid further fines.</p>
        <p>Thank you!</p>
        
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

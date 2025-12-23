<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $data['invoice_number'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        .logo {
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #000;
        }
        .invoice-info div {
            margin-bottom: 2px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 140px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .items-table th {
            background-color: #f8f8f8;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            border-bottom: 2px solid #000;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .right { text-align: right; }
        .book-title { font-weight: bold; display: block; }
        .book-isbn { color: #666; font-size: 10px; }

        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid { background-color: #dff0d8; color: #3c763d; }
        .status-unpaid { background-color: #f2dede; color: #a94442; }

        .totals-container {
            margin-top: 20px;
            width: 100%;
        }
        .totals-table {
            width: 300px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-weight: bold;
            font-size: 14px;
            background-color: #fcfcfc;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        .overdue-banner {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            border: 1px solid #ebccd1;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            @if(app(\App\Settings\GeneralSettings::class)->site_logo)
                <div class="logo">
                    <img src="{{ public_path('storage/' . app(\App\Settings\GeneralSettings::class)->site_logo) }}"
                         style="height: {{ app(\App\Settings\GeneralSettings::class)->site_logoHeight ?? '60' }}px;">
                </div>
            @endif
            <div class="company-name">{{ $data['site']['name'] }}</div>
            <div>{{ $data['site']['address'] }}</div>
            <div>{{ $data['site']['city'] }}, {{ $data['site']['state'] }} {{ $data['site']['zip'] }}</div>
            <div>Phone: {{ $data['site']['phone'] }}</div>
            <div>Email: {{ $data['site']['email'] }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-info">
                <div><strong>No: {{ $data['invoice_number'] }}</strong></div>
                <div>Date: {{ $data['invoice_date'] }}</div>
                <div>Due Date: {{ $data['due_date'] }}</div>
                <div style="margin-top:10px;">
                    <span class="status-badge status-{{ strtolower($data['status']) }}">
                        {{ $data['status'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($data['is_overdue'])
    <div class="overdue-banner">
        ⚠ OVERDUE: This invoice is {{ $data['days_overdue'] }} day(s) past due.
    </div>
    @endif

    @if($data['transaction'])
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="section-title">Bill To:</div>
                <table class="info-table">
                    <tr><td class="label">Member:</td><td>{{ $data['borrower']['name'] }}</td></tr>
                    <tr><td class="label">Email:</td><td>{{ $data['borrower']['email'] }}</td></tr>
                    <tr><td class="label">Type:</td><td>{{ $data['borrower']['membership_type'] }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 40px;">
                <div class="section-title">Reference:</div>
                <table class="info-table">
                    <tr><td class="label">Ref No:</td><td>{{ $data['transaction']['reference_no'] }}</td></tr>
                    <tr><td class="label">Borrowed:</td><td>{{ $data['transaction']['borrowed_date'] }}</td></tr>
                    <tr><td class="label">Returned:</td><td>{{ $data['transaction']['returned_date'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Borrowed Items & Fine Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th>Status</th>
                    <th class="right">Overdue</th>
                    <th class="right">Lost</th>
                    <th class="right">Damage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $item)
                <tr>
                    <td>
                        <span class="book-title">{{ $item['book_title'] }}</span>
                        <span class="book-isbn">ISBN: {{ $item['isbn'] }}</span>
                        @if($item['damage_notes'])
                            <div style="color: #d63031; font-style: italic; font-size: 9px;">Note: {{ $item['damage_notes'] }}</div>
                        @endif
                    </td>
                    <td>{{ $item['item_status'] }}</td>
                    <td class="right">{{ $item['overdue_fine'] }}</td>
                    <td class="right">{{ $item['lost_fine'] }}</td>
                    <td class="right">{{ $item['damage_fine'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    {{-- Section for Membership Invoice --}}
    <div class="section">
        <div class="section-title">Membership Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $item)
                <tr>
                    <td>{{ $item['book_title'] }}</td> {{-- e.g., "Membership Fee: Premium Membership" --}}
                    <td class="right">{{ $data['fees']['total'] }}</td> {{-- Display total amount due for membership --}}
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td>Total Fees</td>
                <td class="right">{{ $data['fees']['total'] }}</td>
            </tr>
            @if($data['fees']['amount_paid'] !== '$0.00')
            <tr>
                <td>Amount Paid</td>
                <td class="right">-{{ $data['fees']['amount_paid'] }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Amount Due</td>
                <td class="right">{{ $data['fees']['amount_due'] }}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    @if($data['notes'])
    <div style="margin-top: 30px; padding: 10px; background: #fffcf0; border-left: 3px solid #f39c12;">
        <strong>Notes:</strong><br>
        {{ $data['notes'] }}
    </div>
    @endif

    <div class="footer">
        <p><strong>Payment Instructions</strong></p>
        <p>Please pay at the {{ $data['site']['name'] }} circulation desk.</p>
        <p style="margin-top: 5px;">Contact us: {{ $data['site']['email'] }} | {{ $data['site']['phone'] }}</p>
        <p style="margin-top: 15px;">Thank you for using our library services!</p>
    </div>

</body>
</html>

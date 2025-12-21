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
        }
        .container {
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
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
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #000;
        }
        .invoice-info {
            margin-top: 10px;
        }
        .invoice-info div {
            margin-bottom: 3px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 5px 0;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table thead {
            background-color: #f0f0f0;
        }
        .items-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #000;
            font-size: 11px;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        .items-table th.right,
        .items-table td.right {
            text-align: right;
        }
        .book-title {
            font-weight: bold;
            color: #000;
        }
        .book-isbn {
            color: #666;
            font-size: 10px;
        }
        .item-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .item-status-returned {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .item-status-lost {
            background-color: #fee;
            color: #c00;
        }
        .item-status-damaged {
            background-color: #ffeaa7;
            color: #d63031;
        }
        .totals {
            margin-top: 20px;
            float: right;
            width: 350px;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .totals td:first-child {
            text-align: left;
            font-weight: bold;
        }
        .totals td:last-child {
            text-align: right;
        }
        .totals .total-row td {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 16px;
            font-weight: bold;
            padding: 12px 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-unpaid {
            background-color: #fee;
            color: #c00;
        }
        .status-partially_paid {
            background-color: #ffeaa7;
            color: #d63031;
        }
        .status-paid {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .status-waived {
            background-color: #e0e0e0;
            color: #666;
        }
        .overdue-banner {
            background-color: #fee;
            border: 2px solid #c00;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c00;
            font-weight: bold;
        }
        .transaction-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 3px solid #666;
            margin-bottom: 20px;
        }
        .notes-box {
            margin-top: 30px;
            padding: 15px;
            background-color: #fffbf0;
            border-left: 3px solid #f39c12;
        }
        .damage-notes {
            font-style: italic;
            color: #d63031;
            font-size: 10px;
            margin-top: 3px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">Your Library Name</div>
                <div>123 Library Street</div>
                <div>City, State 12345</div>
                <div>Phone: (123) 456-7890</div>
                <div>Email: library@example.com</div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-info">
                    <div><strong>#{{ $data['invoice_number'] }}</strong></div>
                    <div>Invoice Date: {{ $data['invoice_date'] }}</div>
                    <div>Due Date: {{ $data['due_date'] }}</div>
                </div>
            </div>
        </div>

        <!-- Overdue Warning -->
        @if($data['is_overdue'])
        <div class="overdue-banner">
            ⚠ OVERDUE: This invoice is {{ $data['days_overdue'] }} day(s) past due
        </div>
        @endif

        <!-- Invoice Status -->
        <div class="section">
            <span class="status-badge status-{{ strtolower(str_replace(' ', '_', $data['status'])) }}">
                {{ $data['status'] }}
            </span>
        </div>

        <!-- Bill To Section -->
        <div class="section">
            <div class="section-title">Bill To:</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Member Name:</div>
                    <div class="info-value">{{ $data['borrower']['name'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $data['borrower']['email'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Membership Type:</div>
                    <div class="info-value">{{ $data['borrower']['membership_type'] }}</div>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="transaction-box">
            <div class="section-title" style="border: none; padding-bottom: 10px;">Transaction Details</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Reference No:</div>
                    <div class="info-value">{{ $data['transaction']['reference_no'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Borrowed Date:</div>
                    <div class="info-value">{{ $data['transaction']['borrowed_date'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Original Due Date:</div>
                    <div class="info-value">{{ $data['transaction']['due_date'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Returned Date:</div>
                    <div class="info-value">{{ $data['transaction']['returned_date'] }}</div>
                </div>
            </div>
        </div>

        <!-- Items and Fees -->
        <div class="section">
            <div class="section-title">Borrowed Items & Fees</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Book Title / ISBN</th>
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
                            <div class="book-title">{{ $item['book_title'] }}</div>
                            <div class="book-isbn">ISBN: {{ $item['isbn'] }}</div>
                            @if($item['damage_notes'])
                            <div class="damage-notes">Note: {{ $item['damage_notes'] }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="item-status item-status-{{ strtolower($item['item_status']) }}">
                                {{ $item['item_status'] }}
                            </span>
                        </td>
                        <td class="right">{{ $item['overdue_fine'] }}</td>
                        <td class="right">{{ $item['lost_fine'] }}</td>
                        <td class="right">{{ $item['damage_fine'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Fee Summary -->
        <div class="section">
            <div class="section-title">Fee Summary</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Fee Type</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($data['fees']['overdue'] !== '$0.00')
                    <tr>
                        <td>Overdue Fees</td>
                        <td class="right">{{ $data['fees']['overdue'] }}</td>
                    </tr>
                    @endif
                    @if($data['fees']['lost'] !== '$0.00')
                    <tr>
                        <td>Lost Item Fees</td>
                        <td class="right">{{ $data['fees']['lost'] }}</td>
                    </tr>
                    @endif
                    @if($data['fees']['damage'] !== '$0.00')
                    <tr>
                        <td>Damage Fees</td>
                        <td class="right">{{ $data['fees']['damage'] }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals clearfix">
            <table>
                <tr>
                    <td>Total Fees:</td>
                    <td>{{ $data['fees']['total'] }}</td>
                </tr>
                @if($data['fees']['amount_paid'] !== '$0.00')
                <tr>
                    <td>Amount Paid:</td>
                    <td>-{{ $data['fees']['amount_paid'] }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Amount Due:</td>
                    <td>{{ $data['fees']['amount_due'] }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes -->
        @if($data['notes'])
        <div class="notes-box">
            <div class="section-title" style="border: none; padding-bottom: 5px;">Additional Notes:</div>
            <div>{{ $data['notes'] }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>Payment Instructions:</strong></p>
            <p>Please remit payment to the library circulation desk or contact us to arrange payment.</p>
            <p style="margin-top: 10px;">If you have any questions about this invoice, please contact us at library@example.com</p>
            <p style="margin-top: 10px;">Thank you for being a valued member of our library!</p>
        </div>
    </div>
</body>
</html>

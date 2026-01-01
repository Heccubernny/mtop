<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Airtime Receipt</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 8px;
        }

        .table th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Transaction Receipt</h2>
        <p>Airtime Purchase Confirmation</p>
    </div>

    <table class="table">
        <tr>
            <th>Reference</th>
            <td>{{ $transaction->reference }}</td>
        </tr>
        <tr>
            <th>Customer</th>
            <td>{{ $transaction->user->name }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $transaction->user->email }}</td>
        </tr>
        <tr>
            <th>Network</th>
            <td>{{ $transaction->network }}</td>
        </tr>
        <tr>
            <th>Phone Number</th>
            <td>{{ $transaction->phone }}</td>
        </tr>
        <tr>
            <th>Amount</th>
            <td>₦{{ number_format($transaction->amount, 2) }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>SUCCESSFUL</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{{ $transaction->created_at }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">Thank you for your purchase.</p>

</body>

</html>

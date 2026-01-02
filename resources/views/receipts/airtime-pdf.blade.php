<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Airtime Receipt</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: linear-gradient(120deg, #f0f4f8, #d9e2ec);
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .receipt-container {
            max-width: 650px;
            margin: auto;
            background: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            color: #1f3a93;
            font-size: 30px;
        }

        .header p {
            margin: 5px 0 0;
            color: #7f8c8d;
            font-size: 16px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-bottom: 30px;
        }

        .table th,
        .table td {
            padding: 12px 20px;
            border-radius: 10px;
        }

        .table th {
            background-color: #1f3a93;
            color: #fff;
            font-weight: 600;
            text-align: left;
        }

        .table td {
            background-color: #f4f6f8;
        }

        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: #fff;
        }

        .status.success {
            background-color: #27ae60;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .footer p {
            margin: 5px 0;
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="receipt-container">
        <div class="header">
            <!-- Optional logo -->
            <!-- <img src="logo.png" alt="Your Company Logo"> -->
            <h2>Transaction Receipt</h2>
            <p>Airtime Purchase Confirmation</p>
        </div>

        <table class="table">
            <tr>
                <th>Reference</th>
                <td>{{ data_get($transaction->response_body, 'orderid') }}</td>
            </tr>
            <tr>
                <th>Customer</th>
                <td>{{ auth()->user->name }}</td>
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
                <td>{{ $transaction->mobile }}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>₦{{ number_format($transaction->amount, 2) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="status success">SUCCESSFUL</span></td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ $transaction->created_at }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>Powered by Mtop</p>
        </div>
    </div>

</body>

</html>

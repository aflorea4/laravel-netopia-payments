<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .order-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .order-summary h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Checkout</h1>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <p><strong>Order ID:</strong> {{ $order['id'] }}</p>
            <p><strong>Amount:</strong> {{ number_format($order['amount'], 2) }} RON</p>
            <p><strong>Description:</strong> {{ $order['description'] }}</p>
        </div>
        
        <h2>Billing Information</h2>
        
        <form action="{{ route('payment.process') }}" method="post">
            @csrf
            
            <input type="hidden" name="order_id" value="{{ $order['id'] }}">
            <input type="hidden" name="amount" value="{{ $order['amount'] }}">
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ $customer['firstName'] }}" required>
                @error('first_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ $customer['lastName'] }}" required>
                @error('last_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ $customer['email'] }}" required>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="{{ $customer['address'] }}" required>
                @error('address')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="mobile_phone">Mobile Phone</label>
                <input type="text" id="mobile_phone" name="mobile_phone" value="{{ $customer['mobilePhone'] }}" required>
                @error('mobile_phone')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <button type="submit" class="btn">Proceed to Payment</button>
        </form>
    </div>
</body>
</html>

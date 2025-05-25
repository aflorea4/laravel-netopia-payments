<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Netopia Payments</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            text-decoration: none;
        }
        .button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Redirecting to Payment</h1>
        <p>Please wait while we redirect you to the secure payment page...</p>
        
        <div class="loader"></div>
        
        <form id="netopiaForm" action="{{ $paymentUrl }}" method="post">
            <input type="hidden" name="env_key" value="{{ $envKey }}">
            <input type="hidden" name="data" value="{{ $data }}">
            <input type="hidden" name="iv" value="{{ $iv }}">
            <button type="submit" class="button" id="manualButton" style="display: none;">Pay Now</button>
        </form>
        
        <p id="redirectMessage" style="display: none; margin-top: 20px; font-size: 14px;">
            If you are not automatically redirected, please click the button above.
        </p>
    </div>
    
    <script>
        // Submit the form automatically after page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('netopiaForm').submit();
            }, 2000); // Wait 2 seconds before submitting
            
            // Show manual button after 5 seconds (in case auto-submit fails)
            setTimeout(function() {
                document.getElementById('manualButton').style.display = 'inline-block';
                document.getElementById('redirectMessage').style.display = 'block';
            }, 5000);
        });
    </script>
</body>
</html>

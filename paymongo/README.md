# PayMongo Integration Setup

This folder contains all the necessary files for PayMongo payment integration with your e-commerce website.

## Files Overview

- `config.php` - Configuration file with API keys and URLs
- `create-checkout-session.php` - Creates PayMongo checkout sessions
- `payment-success.php` - Success page after payment
- `payment-cancel.php` - Cancel page if payment is cancelled
- `README.md` - This setup guide

## Setup Instructions

### 1. Get PayMongo API Keys

1. Go to [PayMongo Dashboard](https://dashboard.paymongo.com/)
2. Sign up or log in to your account
3. Navigate to API Keys section
4. Copy your **Secret Key** and **Public Key**
5. **Important**: Use test keys first for development

### 2. Configure API Keys

Edit `config.php` and replace the placeholder keys:

```php
define('PAYMONGO_SECRET_KEY', 'sk_test_your_actual_secret_key_here');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_your_actual_public_key_here');
```

### 3. Setup ngrok (Required for Testing)

PayMongo requires HTTPS URLs for webhooks and redirects. Since you're developing locally, you need ngrok:

1. **Install ngrok**: Download from [ngrok.com](https://ngrok.com/)
2. **Start your local server**: Make sure your XAMPP is running
3. **Start ngrok**: Open terminal and run:
   ```bash
   ngrok http 80
   ```
   (Replace 80 with your actual port if different)

4. **Copy the ngrok URL**: You'll see something like:
   ```
   https://abc123def456.ngrok-free.app
   ```

5. **Update config.php**: Replace the ngrok URL:
   ```php
   define('NGROK_BASE_URL', 'https://abc123def456.ngrok-free.app');
   ```

### 4. Test the Integration

1. Go to your checkout page: `http://localhost/checkout.php`
2. Fill out the form and select "Online Payment"
3. Click "Continue to Payment"
4. You should be redirected to PayMongo's secure checkout page
5. Use PayMongo's test card numbers for testing

### 5. Test Card Numbers

For testing, use these card numbers:

- **Visa**: 4242 4242 4242 4242
- **Mastercard**: 5555 5555 5555 4444
- **Any expiry date** in the future
- **Any 3-digit CVC**

## How It Works

1. **User fills checkout form** → Your website
2. **Clicks "Continue to Payment"** → JavaScript validates form
3. **Creates PayMongo session** → `create-checkout-session.php`
4. **Redirects to PayMongo** → Secure payment page
5. **User enters payment details** → PayMongo handles security
6. **Payment success/failure** → Redirects back to your site
7. **Shows success/cancel page** → `payment-success.php` or `payment-cancel.php`

## Payment Methods Supported

- 💳 **Credit/Debit Cards** (Visa, Mastercard, etc.)
- 📱 **GCash** (Philippine mobile wallet)
- 🚗 **GrabPay** (Southeast Asian payment)

## Security Features

- ✅ **PCI DSS Compliant** - PayMongo handles sensitive card data
- ✅ **3D Secure** - Additional security for card payments
- ✅ **HTTPS Required** - All payment pages use secure connections
- ✅ **No Card Storage** - Your server never stores card details

## Troubleshooting

### Common Issues:

1. **"Invalid API Key"**
   - Check your secret key in `config.php`
   - Make sure you're using test keys for development

2. **"Invalid redirect URL"**
   - Update your ngrok URL in `config.php`
   - Make sure ngrok is running

3. **"CORS Error"**
   - Check that your ngrok URL is correct
   - Make sure the payment files are accessible

4. **"Payment not working"**
   - Check browser console for errors
   - Verify PayMongo dashboard for transaction logs

### Debug Mode:

Add this to your `config.php` for debugging:

```php
define('DEBUG_MODE', true);
```

## Going Live

When ready for production:

1. **Get live API keys** from PayMongo dashboard
2. **Update config.php** with live keys
3. **Use your actual domain** instead of ngrok
4. **Test thoroughly** with small amounts first
5. **Set up webhooks** for order status updates

## Support

- **PayMongo Documentation**: [docs.paymongo.com](https://docs.paymongo.com/)
- **PayMongo Support**: [support.paymongo.com](https://support.paymongo.com/)

---

**Remember**: Always test with small amounts first and use test API keys during development!

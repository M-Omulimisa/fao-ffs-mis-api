# Pesapal Production Migration Checklist

## Pre-Production Requirements

### Business Documentation
- [ ] Business registration certificate
- [ ] Tax identification number (PIN/VAT)
- [ ] Bank account details for settlements
- [ ] Director/Owner ID documents
- [ ] Business license (if applicable)

### Technical Requirements
- [ ] SSL certificate for your domain
- [ ] Production server setup
- [ ] Database backup before migration
- [ ] Production IPN URL accessible
- [ ] Production callback URL accessible

### Pesapal Account Setup
- [ ] Submit production account request
- [ ] Complete KYC verification
- [ ] Receive production credentials
- [ ] Configure production webhook URLs
- [ ] Test with small amounts first

## Migration Steps

### 1. Update Environment Variables
```env
PESAPAL_ENVIRONMENT=production
PESAPAL_CONSUMER_KEY=your_production_key
PESAPAL_CONSUMER_SECRET=your_production_secret
PESAPAL_IPN_URL=https://yourdomain.com/api/pesapal/ipn
PESAPAL_CALLBACK_URL=https://yourdomain.com/payment-callback
```

### 2. Update Currency Configuration
- Remove KES 1000 limit validation
- Configure appropriate currency (KES/UGX/USD)
- Update conversion rates if needed

### 3. Testing Protocol
- [ ] Test small transaction (e.g., KES 10)
- [ ] Verify IPN notifications work
- [ ] Test payment completion flow
- [ ] Verify order status updates
- [ ] Test refund process (if applicable)

### 4. Monitoring Setup
- [ ] Enable production logging
- [ ] Set up error alerts
- [ ] Monitor transaction success rates
- [ ] Track settlement reports

## Important Notes

- **Gradual Rollout**: Start with limited users/amounts
- **Monitoring**: Watch for any integration issues
- **Support**: Have Pesapal support contact ready
- **Backup**: Keep sandbox config for future testing

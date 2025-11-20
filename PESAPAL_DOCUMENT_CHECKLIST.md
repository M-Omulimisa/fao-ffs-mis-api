# Document Preparation Checklist for Pesapal Production Account

## ✅ **Required Business Documents**

### **1. Company Registration Documents**
- [ ] **Certificate of Incorporation** - Biltel Electronics Limited
- [ ] **Memorandum and Articles of Association**
- [ ] **Company Registration Extract** (recent copy from URSB)

### **2. Tax Compliance Documents**
- [ ] **TIN Certificate** (Tax Identification Number)
- [ ] **VAT Registration Certificate** (if applicable)
- [ ] **Tax Clearance Certificate** (recent)

### **3. Banking Documents**
- [ ] **Bank Account Opening Letter** - KCB Bank Uganda
- [ ] **Bank Statements** (last 3 months) - Account: 2310924675
- [ ] **Bank Reference Letter**

### **4. Personal Identification**
- [ ] **Andrew Henry Magombe - National ID** (copy)
- [ ] **Andrew Henry Magombe - Passport** (if available)

### **5. Business Operations**
- [ ] **Business License** (trading license)
- [ ] **Website Screenshots** - http://blitxpress.com
- [ ] **Business Profile/Brochure** (optional but helpful)

### **6. Financial Information** (if requested)
- [ ] **Financial Statements** (if company has been operating for over 1 year)
- [ ] **Projected Revenue Statements**

## 📧 **Email Sending Instructions**

### **Step 1: Gather Documents**
- Scan all documents in high quality PDF format
- Ensure all documents are current (within 6 months)
- Organize files with clear names (e.g., "Biltel_Certificate_of_Incorporation.pdf")

### **Step 2: Send Email**
- **To:** developer@pesapal.com
- **CC:** support@pesapal.com
- **Subject:** Production Account Activation Request - Biltel Electronics Limited (BlitXpress)
- **Attachments:** All required documents (max 25MB total)

### **Step 3: Follow Up**
- Wait 3-5 business days for initial response
- If no response, send a polite follow-up email
- Keep phone number +256783204665 available for verification calls

## 🔧 **Technical Preparation**

### **Before Going Live:**
- [ ] Ensure http://blitxpress.com has SSL certificate (HTTPS)
- [ ] Test IPN URL accessibility: http://blitxpress.com/api/pesapal/ipn
- [ ] Test callback URL accessibility: http://blitxpress.com/payment-callback
- [ ] Backup current database before switching to production
- [ ] Prepare production environment variables

### **Post-Approval Steps:**
1. Update `.env` file with production credentials
2. Run `php artisan config:cache`
3. Test with small UGX transaction first
4. Monitor transaction success rates
5. Set up production logging and alerts

## 📞 **Expected Communication**

### **Pesapal May Ask For:**
- Additional business verification documents
- Phone verification call to +256783204665
- Clarification on business model and transaction volumes
- Proof of website ownership
- Additional bank verification

### **Timeline:**
- **Document Review:** 3-7 business days
- **Verification Process:** 1-3 days
- **Account Activation:** 1-2 days after approval
- **Total Time:** 1-2 weeks (typical)

## ⚠️ **Important Notes**

1. **Ensure Accuracy:** All information in documents must match exactly
2. **Keep Originals:** Maintain original copies of all submitted documents
3. **Response Time:** Pesapal typically responds within 5 business days
4. **Support Hours:** Contact during business hours for faster response
5. **Compliance:** Be prepared for KYC (Know Your Customer) verification process

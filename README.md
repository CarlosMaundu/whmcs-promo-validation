# WHMCS Promo Code Validation Hook

## Overview

This project provides a custom WHMCS hook to validate promo codes during the checkout process. It ensures that users cannot use a promo code for a trial product if they have previously used it, and enforces the application of a promo code for trial products.

## Use Cases

- **Software Trials**: Ensure users apply promo codes to access trial versions of software.
- **Limited Time Offers**: Restrict users from reusing promo codes for limited time offers.
- **Marketing Campaigns**: Validate promo codes used in marketing campaigns.

## Usage

1. Replace `<YOUR_PRODUCT_ID>` in `promoValidation.php` with your actual trial product ID.
2. Place `promoValidation.php` in `includes/hooks/`.

### Key Features

- Checks if the trial product is in the cart.
- Validates the promo code application.
- Ensures that users who have previously used the trial product cannot use the promo code again.
- Handles both logged-in and guest users.

## Installation

1. **Clone the repository:**

   ```sh
   git clone https://github.com/CarlosMaundu/whmcs-promo-validation.git
   cd whmcs-promo-validation

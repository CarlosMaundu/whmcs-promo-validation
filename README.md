# WHMCS Promo Code Validation Hook

## Overview

This project provides a custom WHMCS hook to validate promo codes during the checkout process. It ensures that users cannot use a promo code for a trial product if they have previously used it, and enforces the application of a promo code for trial products.

## Use Cases

- Preventing users from reusing promo codes for trial products.
- Enforcing promo code application for specific products during checkout.
- Handling validation for both logged-in and guest users.

### Key Features

- Checks if the trial product is in the cart.
- Validates the promo code application.
- Ensures that users who have previously used the trial product cannot use the promo code again.
- Handles both logged-in and guest users.

## Installation

1. **Clone the repository:**

   ```sh
   git clone https://github.com/yourusername/whmcs-promo-validation.git
   cd whmcs-promo-validation

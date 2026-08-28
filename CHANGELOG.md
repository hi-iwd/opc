# Changelog

## 7.0.1
- Bug fix

## 7.0.0

Initial release of `IWD_OneStepCheckout`, IWD's one-step checkout for Magento 2 / Adobe Commerce.

The module follows a compatibility-first architecture: zero class preferences, zero around plugins, zero RequireJS module replacements, zero Knockout template replacements, native `/checkout` route.

- One-page checkout with numbered sections (Contact / Shipping Address / Delivery Method / Payment) on the native checkout route
- Auto-save shipping with validator gating (configurable)
- Place Order in the order summary for an admin-configurable allowlist of payment methods; all other methods keep their native buttons
- Payment-locked placeholder with progress tracker
- Order comments, newsletter opt-in, guest-to-customer account creation (all opt-in)
- Admin design tokens (colors, font) applied at runtime per store view
- Layout modes: One Page / Multi-Step (native steps, restyled)
- Responsive desktop / tablet / mobile layouts with a sticky mobile Place Order bar
- Virtual and downloadable cart support
- Hard no-op when Hyvä Checkout is enabled; byte-for-byte native checkout when disabled
- PayPal partner attribution (BN code) applied automatically to PayPal API calls, the Smart Buttons SDK and Pay Later messaging
- 46 unit tests

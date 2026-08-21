# IWD One-Page Checkout for Magento 2

A single-page checkout that **enhances Magento's native checkout instead of replacing it**. All UX improvements are layered on top of the stock Luma/Knockout checkout as additive, chain-safe RequireJS mixins and after-plugins:

- no class preferences,
- no route overrides (the checkout stays on `/checkout`),
- no Knockout template replacements,
- no `map:`/`paths` RequireJS swaps.

Because nothing native is replaced, the module coexists with payment gateways, shipping extensions, tax extensions and custom checkout code. When the module is disabled, the storefront checkout is native Magento again.

| Package   | Module |
|-----------|---|
| `iwd/opc` | `IWD_OneStepCheckout` |

## Features

- **One-page checkout**: Contact, Shipping Address, Delivery Method and Payment as numbered sections on a single screen, with a sticky order summary.
- **Auto-save shipping ("no Next button")**: once the address is complete and a delivery method is chosen, shipping saves and payment loads automatically. Configurable; each save is validated by Magento's own validators, so required fields added by other extensions are respected.
- **Place Order in the order summary**: for verified payment methods (configurable allowlist), the Place Order action moves into the summary. Any method not on the list keeps its own native button, so SDK, express and 3-D Secure payment flows are never bypassed.
- **Payment-locked state**: a friendly placeholder with a progress tracker while payment methods have not loaded yet.
- **Order comments**: optional "Order notes" field, saved to the order (visible to the customer and in the admin).
- **Newsletter opt-in**: optional checkbox; the subscription is linked to the customer account when one exists.
- **Guest-to-customer**: optionally create a customer account from a guest order after checkout.
- **Design customization**: colors and font family configurable in the admin per store view, applied at runtime with no re-deploy.
- **Layout modes**: `One Page` (default), or `Multi-Step` — the native step-by-step flow with the module's visual skin.
- **Responsive**: dedicated desktop, tablet and mobile layouts; on mobile the Place Order button is a sticky bottom bar.
- **Virtual/downloadable carts** are handled with their own collapsed section layout.
- **Translation-ready**: every storefront and admin phrase is in `i18n/en_US.csv`.
- **PayPal partner attribution**: while the checkout is enabled, IWD's PayPal BN (partner attribution) code is applied automatically to PayPal API calls, the Smart Buttons SDK and Pay Later messaging. No setup required; when the checkout is disabled, Magento's native attribution is restored.

## Requirements

- Magento Open Source / Adobe Commerce **2.4.7 or 2.4.8+** (Luma/Knockout checkout)
- PHP **8.2 / 8.3 / 8.4 / 8.5**

**Not supported:** Hyvä Checkout. The module detects `Hyva_Checkout` and disables itself completely (native behavior is preserved; no assets are loaded).

## Installation

### Composer (recommended)

```bash
composer require iwd/opc
bin/magento module:enable IWD_OneStepCheckout
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy   # production mode
bin/magento cache:flush
```

### Manual (app/code)

Copy the repository contents to `app/code/IWD/OneStepCheckout/`, then run the same `bin/magento` commands as above.

### Uninstall

```bash
bin/magento module:disable IWD_OneStepCheckout
composer remove iwd/opc
bin/magento setup:upgrade && bin/magento cache:flush
```

The module writes no database schema; disabling it returns the native checkout.

## Configuration

**Stores → Configuration → IWD → One Page Checkout**

| Setting                                             | Default | Notes |
|-----------------------------------------------------|---|---|
| General → Enable One Page Checkout                  | Yes | Off = native checkout, byte for byte |
| General → Layout Mode                               | One Page | One Page / Multi-Step (native steps, restyled) |
| General → Auto-Save Shipping                        | Yes | Turn off if an extension adds required shipping fields that auto-save should wait for (e.g. freight/LTL fields) |
| General → Relocatable Payment Methods               | offline methods | Comma-separated method codes whose Place Order action moves to the summary button. Only add a method after verifying its flow works from the summary button |
| Features → Order Comment Field                      | No | Adds "Order notes" to the payment section |
| Features → Newsletter Subscribe Checkbox            | No | Subscribes the email after the order is placed |
| Features → Create Account for Guests After Checkout | No | Creates a customer account from the guest order |
| Design → colors / font family                       | empty | Optional overrides (hex values / CSS font stack); blank keeps the built-in palette |

All settings are per store view.

### Payment method compatibility

The module never re-implements payment logic. The summary Place Order button delegates to the selected payment method's own place-order action, and only for method codes you have explicitly allowed. Express wallets (PayPal smart buttons, Apple Pay, Google Pay), 3-D Secure and hosted-redirect methods keep their native buttons and flows by design.

## For developers

Unit tests (PHPUnit 9, mocks only):

```bash
composer require --dev magento/module-checkout magento/module-quote \
  magento/module-sales magento/module-customer magento/module-newsletter
vendor/bin/phpunit
```

Inside a Magento installation: `vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/IWD/OneStepCheckout/Test/Unit`.

- After changing `requirejs-config.js` or any mixin in a developer environment, clear `pub/static` **and** hard-reload the browser (the merged RequireJS config is cached on both sides).

## License

Open Software License 3.0 (OSL-3.0) — the same license as Magento Open Source. See [LICENSE.txt](LICENSE.txt).

Copyright © IWD Agency, [iwdagency.com](https://www.iwdagency.com).

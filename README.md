# pms-taiwan-gateways
Extends the feature from Paid Member Subscriptions for ECPay in Taiwan.

## Debugging
* Use `$payment->log_data()` to log the raw data during the payment.
## Changelog

* 2022-05-23 (v1.1.2)
  * Fix: duplicated calculation of return URL still happened, use 'complete' to prvenet.
  * Fix: fixed the typo of the return URL name to get the correct data.
  * Improve: started to fit the coding standards.

* 2022-04-21 (v1.1.1)
  * Fix: Add prefix of class of SDK in case of duplicated class name.

* 2022-04-21 (v1.1.0)
  * Add: UI to set transaction prefix.
  * Change: Sync period transaction to payment settings.

* 2022-04-20 (v1.0.2)
  * Add: UI to set period transactions.
  * Fix: duplicated calculation of return URL.
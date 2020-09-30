# Changelog Shopware 5

** 1.0.0 **

* Initial Version

** 1.1.0 **

* Removed unnecessary typecasts for order lines.
* Label and translations changes in configuration file.
* Afterpay payment method country available. 
* Add Iban custom information for bank-transfer payment method.
* Remove PaymentKeeperSubscriber.php.
* Fix undefined array key notice in ProcessPaymentDisplaySubscriber.php.
* Remove webhook option from settings (also code realiation).
* Merged a logic from IDealIssuerSubscriber and PaymentKeeper to be a single ProcessPaymentDisplaySubscriber.
* Added iBAN information for BankTransfer payment method.
* Implemented country validation feature for Afterpay payment method.
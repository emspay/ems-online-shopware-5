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
* Remove webhook option from settings (also code relation).
* Merged a logic from IDealIssuerSubscriber and PaymentKeeper to be a single ProcessPaymentDisplaySubscriber.
* Added iBAN information for BankTransfer payment method.
* Implemented country validation feature for Afterpay payment method.

** 1.2.0 **

* Reorganization of the plugin XML file and namespaces according to the requirements of the online store.
* Add TEST API Button to payment settings page.
* Add prefixes to all classes and folders where it required from Shopware support.
* Reworked error system handling.
* Added error logging system. 
* Added warning message block on confirm page what must describe the reason of return to the checkout page. 
* Added auto Cache clearing system on activate/deactivate plugin action.

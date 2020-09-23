# EMS Online plugin for Shopware 5.6.6
This is the offical EMS Online plugin.

## About
By integrating your webshop with EMS Online you can accept payments from your customers in an easy and trusted manner with all relevant payment methods supported.


## Version number
Version 1.0.2


## Pre-requisites to install the plug-ins: 
- PHP v7 and above
- MySQL v5.4 and above

## Installation
Manual installation of the Shopware 5 plugin using (s)FTP

1. Unzip your archive with the plugin. Put the 'custom' folder to the root of the Shopware. 
2. Go to the admin panel, this will be '/backend' in your URL address. 
3. Open tab Configuration>Plugin Manager or use keyboard shortcut "CTRL+ALT+P". 
    * Open the 'Installed' menu option, and find the 'uninstalled' section. 
    * There find Ginger Payments and click the button 'install plugin'. 
    * Put you API key in the next opened window and choice CaCert option (by default is activated). 
    * Then click Activate, to turn on the plugin.
4. Open tab Configuration>Payment Methods. There you can find payment method what you want to use. By default all payment after installation is disabled. To set payment as active:
    * Choice payment from the list of all payment methods.
    * In opened form search checkbox with label Active and turn it on.
    * Click Save Button 
    * in the bottom part of the form 
5. Once the modules are installed you can offer the payment methods in your webshop.
6. Compatibility: Shopware 5.6.*
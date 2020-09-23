# EMS Online plugin for Shopware 5.6.6
This is the offical EMS Online plugin.

## About
By integrating your webshop with EMS Online you can accept payments from your customers in an easy and trusted manner with all relevant payment methods supported.


## Version number
Version 1.1.0


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
    * Choice CaCert option (by default is activated). 
    * Choice the use webhook option (by default is activated).
        * Are you offering Klarna on your pay page? In that case enter the following fields:
            * Test API key field. Copy the API Key of your test webshop in the Test API key field. When your Klarna application is approved an extra test webshop was created for you to use in your test with Klarna. The name of this webshop starts with ‘TEST Klarna’.
            * Klarna IP For the payment method Klarna you can choose to offer it only to a limited set of whitelisted IP addresses. You can use this for instance when you are in the testing phase and want to make sure that Klarna is not available yet for your customers. If you do not offer Klarna you can leave the Test API key and Klarna debug IP fields empty.
        * Are you offering Afterpay on your pay page?
            * To allow AfterPay to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for AfterPay" field. Example: BE, NL, FR.
            * See the instructions for Klarna.
    * Then click Activate, to turn on the plugin.
4. Open tab Configuration>Payment Methods. There you can find payment method what you want to use. By default all payment after installation is disabled. To set payment as active:
    * Choice payment from the list of all payment methods.
    * In opened form search checkbox with label Active and turn it on.
    * Click Save Button 
    * in the bottom part of the form 
5. Once the modules are installed you can offer the payment methods in your webshop.
6. Compatibility: Shopware 5.6.*
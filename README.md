# Instant Search + Magento 1
Magento 1 extension for Instantsearch+

Installation
------------
1. Download the most up to date InstantSearch+ for Magento Extension from [our repository](https://github.com/instantsearchplus/magento1_extension/releases)
2. Unzip the package
3. Open your Magento Admin
4. Disable Magento cache (_System/Cache Management_)  
    a. Using Magento Admin Uploader: open System/Magento Connect/Magento Connect Manager, upload the extension using "Direct package file upload" and click Install  
    b. FTP Upload: Upload /app and /lib to your Magento server (these should already be on your Magento server so just merge the extension folders with the existing ones)  
5. IS+ template file is found in app/design/frontend/base/default/template/autocompleteplus - copy this template if you are using a different template directory
6. Logout and Login again to your Magento Admin
7. Enable Magento Cache
8. Refresh Magento Cache
9. Refresh Varnish cache
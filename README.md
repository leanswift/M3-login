# M3-Login

This repository has gift card add-on module that works only with eConnect.

> **Note:** Please take a necessary backup before install this module.


# Installation:

The Login module can be installed with [Composer](https://getcomposer.org/). 

There are two ways to install the module in  Magento. Composer way (**Recommended**) and the other one is manual installation.

# Pre-Condition for installation using Composer:

   Install and setup Composer in your environment.                                                  
   Download link: [https://getcomposer.org/Composer-Setup.exe]


# Install a module using Composer either any of the below methods:

**Method 1:**


 Open command prompt and navigate to the Magento root directory where the composer.json file is placed. Run the below commands.
  
 
     composer config repositories.leanswift vcs https://github.com/leanswift/M3-login
  
     composer config -g github-oauth.github.com < Get GitHub oauth token from LeanSwift >
  
     composer require leanswift/login
  
 > **Note:** If you face any issues, proceed Method 2.

**Method 2:**


**Step 1:** Add the M3-Login package to your `composer.json` file available in your Magento root folder.

```json
{
    "require": {
        "leanswift/login": "1.0.0"
    }
}
```
  > **Note:** You need to provide the package name and the latest released version. Like, **1.x.x**.
 
**Step 2:** Add M3-Login Module's GitHub repository URL.

```json
{
    "repositories" : [
        {
          "type": "vcs",
          "url": "https://github.com/leanswift/M3-login/"
        }
    ]
}
```

**Step 3:** Configure O-Auth token Authorization.
```json
{
     "config": {
        "github-oauth": {
          "github.com": "<Get GitHub Oauth token from LeanSwift>"
        }
     }
}
```

After composer installation, open command prompt and navigate to the Magento root  directory where the composer.json file is placed.
         
Run **composer install**, if composer is being executed for the first time, else **composer update**.

Now the module will install under the **/vendor/leanswift/module-login/** folder as per the composer.json configuration.

 
# Manual Installation:

   **Step 1:** Download and extract the package from [GitHub](https://github.com/leanswift/M3-login)
   
   **Step 2:**  You have the folder name **M3-login"** version . Copy all the folders available in the extracted folder.
   
   **Step 3:** Go to your Magento 2 root folder and paste all the folders in the below path.
   
          Path : /**Magento root folder**/app/code/LeanSwift/Login/ 
          
# How to enable the Module in Magento:
          
  Open Command prompt and navigate to the Magento root directory and run the below commands,   
    
            php bin/magento module:status 
            
  You can view the list of the modules available in your Magento along with the M3-Login module is in **disabled mode**.
            
            
  Enable the module, upgrade schema and flush cache using the commands below.
  
            php bin/magento module:enbale LeanSwift_Login
             
            php bin/magento setup:upgrade 
            
            php bin/magento cache:flush
            
        
  

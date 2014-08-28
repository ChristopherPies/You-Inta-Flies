DDM Note:
In order to keep the keys above the webroot and allow use of application specific keys we made some changes;


Don't use global - Symlink this dir to your public dir - and use require/include to run these files and set the key and user variables:

Controller example:
public function plagScanLinkAction() {
    $parts = explode('/', $_SERVER['REQUEST_URI']);
    $this->view->page = $parts[3];

    $bootstrap = $this->getInvokeArg('bootstrap');
    $options = $bootstrap->getOptions();
    $this->view->plagscan = $options['plagscan'];

    $this->view->layout()->disableLayout();
}

View example:
<?php
$apiUser = $this->plagscan['user'];
$apiKey = $this->plagscan['apikey'];

if(file_exists(PROJECT_ROOT . 'public/PlagScanRemoteLMS/' . $this->page . '.php')) {
    require_once PROJECT_ROOT . 'public/PlagScanRemoteLMS/' . $this->page . '.php';
} else {
     require_once PROJECT_ROOT . 'public/PlagScanRemoteLMS/' . $this->page;
}





Below is the original README




The scripts in this folder allow you and your users to access PlagScan reports within your Content / Learning Management System.
You can (and should) add your own browser-based login and access rights checking.

To get started you will only have to modify global.php -

1) Copy all scripts to a directory on your webserver, e.g. https://yourdomain.com/PlagScan/

2) Add your own PlagScan API username and key to global.php
You will find the key on the PlagScan admininstrator API Integration page https://www.plagscan.com/apisetup

3) On the API Integration page https://www.plagscan.com/apisetup you should also add as Internal Link URL
your domain and directory where you put these scripts, e.g. https://yourdomain.com/PlagScan/
(Please note that setting the "Internal Link URL" will only influence reports generated from the moment you enter a link and not change reports generated earlier!)

In global.php you can also add your own browser-based login and access rights checking.

To see what this can look like see the working example:
https://www.wahlkurse.de/PlagScanAPI/view?2150873

The example simply uses the test scripts, without login / access check.
All the links to other report types (docx, pdf, list) work fine within the local domain, wahlkurse.de in this test.
Only in the background the scripts fetch requested content via the API from PlagScan.
Hint: The Internal Link URL for the example is simply https://www.wahlkurse.de/PlagScanAPI



Web Server Hint: Your server has to support extension hiding (view?2150873 instead of view.php?2150873).

There are different ways to achieve that you call view and view.php is processed - the best way depends on your local setup. You might want to do some googling on the terms below to find the optimal solution.
The following assumes you are running an Apache Web-Server, which allows .htaccess, per-directory or a global setting in the .conf file.
You can use "content negotiation":

Options +MultiViews

or Rewriting Rules (requires ModRewrite), e.g.:

RewriteEngine On
RewriteRule ^([^.]+)$ $1.php

<?php
$installer = $this;

$helper = Mage::helper('autocompleteplus_autosuggest');

//getting site url
$url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

//getting site owner email
$storeMail = $helper->getConfigDataByFullPath('autocompleteplus/config/store_email');

$multistoreJson = $helper->getMultiStoreDataJson();

// Checking config table values
// getEdition exist from version 1.12, LICENSE_EE.txt file only exists in EE edition, we need the condition to work on EE version less then 1.11.x.x
if (!method_exists('Mage' , 'getEdition') && file_exists('LICENSE_EE.txt') && method_exists('Mage' , 'getVersion') && version_compare(Mage::getVersion(), '1.10.0.0.', '<') === true){
    if ($installer->getConnection()->showTableStatus($this->getTable('autocompleteplus_config'))) {
        $config_arr = Mage::getModel('autocompleteplus_autosuggest/config')->getCollection()->getData();
        $config = $config_arr[0];
    } else {
        $config = false;
    }
} else {
    if ($installer->getConnection()->isTableExists($this->getTable('autocompleteplus_config'))) {
        $config_arr = Mage::getModel('autocompleteplus_autosuggest/config')->getCollection()->getData();
        $config = $config_arr[0];
    } else {
        $config = false;
    }
}


$data = array();
if ($config && isset($config['licensekey'])) {
    $data['uuid'] = $config['licensekey'];
}

$key='';
$auth_key='';
$is_reachable=0;
$errMsg = '';

try {
    $commandOrig = "http://magento.instantsearchplus.com/install";
    $data['multistore'] = $multistoreJson;
    if (method_exists('Mage' , 'getEdition')){
        $data['edition'] = Mage::getEdition();
    } else {
        $data['edition'] = 'Unknown';
    }
    $data['site'] = $url;
    $data['email'] = $storeMail;
    $data['f'] = '2.0.6.2';
    
    $auto_arr = json_decode($helper->sendPostCurl($commandOrig, $data), true);
    
    $key = $auto_arr['uuid'];
    $auth_key = $auto_arr['authentication_key'];
    if (strlen($key) > 50) {
        $key = 'InstallFailedUUID';
    }
    if(isset($auto_arr['is_reachable'])){
        $is_reachable=$auto_arr['is_reachable'];
    }
    
    Mage::log(print_r($auto_arr, true), null, 'autocomplete.log',true);
    
    if ($key == 'InstallFailedUUID') {
        $errMsg .= 'Could not get license string.';
    }
    
    //sending error info
    if ($errMsg != '') {
    
        $command = "http://magento.instantsearchplus.com/install_error";
        $data = array();
        $data['site'] = $url;
        $data['msg'] = $errMsg;
        $data['email'] = $storeMail;
        //$data['product_count']=$productCount;
        $data['multistore'] = $multistoreJson;
        $data['f'] = '2.0.6.2';
        $res = $helper->sendPostCurl($command, $data);
    }

    //getting sitemap.xml
    if ($key != '' && $key != 'InstallFailedUUID'){
        $stemapUrl='Sitemap:http://magento.instantsearchplus.com/ext_sitemap?u='.$key.PHP_EOL;
        $robotsPath=Mage::getBaseDir().DS.'robots.txt';
        if (file_exists($robotsPath)) {
            if (strpos(file_get_contents($robotsPath),$stemapUrl) == false){
                if(is_writable($robotsPath)){
                    //append sitemap
                    file_put_contents($robotsPath, $stemapUrl, FILE_APPEND | LOCK_EX);
                }else{
                    //write message that file is not writteble
                    $command="http://magento.instantsearchplus.com/install_error";
                    $data=array();
                    $data['site']=$url;
                    $data['msg']='File '.$robotsPath.' is not writable.';
                    $data['f'] = '2.0.6.2';
                    $res=$helper->sendPostCurl($command,$data);
                }
            }
        }else{
            //create file
            if(is_writable(Mage::getBaseDir())){
                //create robots sitemap
                file_put_contents($robotsPath,$stemapUrl);
            }else{
                //write message that directory is not writteble
                $command="http://magento.instantsearchplus.com/install_error";
                $data=array();
                $data['site']=$url;
                $data['msg']='Directory '.Mage::getBaseDir().' is not writable.';
                $data['f'] = '2.0.6.2';
                $res=$helper->sendPostCurl($command,$data);
            }
        }
    }

} catch (Exception $e) {
    $key = 'failed';
    $errMsg = $e->getMessage();
    Mage::log('Install failed with a message: ' . $errMsg, null, 'autocomplete.log',true);
    $command = "http://magento.instantsearchplus.com/install_error";

    $data = array();
    $data['site'] = $url;
    $data['msg'] = $errMsg;
    $data['original_install_URL'] = $commandOrig;
    $data['f'] = '2.0.6.2';
    $res = $helper->sendPostCurl($command, $data);
}


$installer->startSetup();
    
    $res=$installer->run("
        DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_config')};

        CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_config')} (

          `id` int(11) NOT NULL auto_increment,

          `licensekey` varchar(255) character set utf8 NOT NULL,

          `authkey` varchar(255) character set utf8 NOT NULL,

          `site_url` varchar(255) character set utf8 NOT NULL,

          `is_reachable` TINYINT NOT NULL,

          `errormessage` TEXT character set utf8 NOT NULL,

          `server_type` varchar(255) character set utf8,
          
          `cdn_cache_key` varchar(255) character set utf8,

           PRIMARY KEY  (`id`)

        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

    $res = $installer->run("INSERT INTO {$this->getTable('autocompleteplus_config')}
    (licensekey,authkey,site_url,is_reachable,errormessage)
    VALUES('" . $key . "','" . $auth_key . "','" . $url . "',".$is_reachable.",'".$errMsg."');");

$installer->endSetup();

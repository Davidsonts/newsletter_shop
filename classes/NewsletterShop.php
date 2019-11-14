<?php

use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Product\ProductInterface;

class NewsletterShop extends ObjectModel
{
    public static function selectAll(){
        $sql = 'SELECT * FROM '._DB_PREFIX_.'newsletter_shop
        INNER JOIN '._DB_PREFIX_.'emailsubscription  
        ON '._DB_PREFIX_.'emailsubscription.id = '._DB_PREFIX_.'newsletter_shop.ps_emailsubscription_id
        ;';
        
        //return Db::getInstance()->getValue($sql);
        return Db::getInstance()->executeS($sql);
    }

    public static function selectId($ps_emailsubscription_id){
        $sql = 'SELECT * FROM '._DB_PREFIX_.'newsletter_shop WHERE ps_emailsubscription_id = "'.$ps_emailsubscription_id.'"';
        
        return Db::getInstance()->executeS($sql);
    }

    public static function addNew($name, $ps_emailsubscription_id){
        
        $sql = 'INSERT INTO `'._DB_PREFIX_.'newsletter_shop` (`name`, `ps_emailsubscription_id`) 
            VALUES ("'.$name.'", "'.$ps_emailsubscription_id.'");';

        if (!Db::getInstance()->execute($sql))
            die('Error add.');
    }  

    public static function selectEmailSubscription($email){
        $sql = 'SELECT email FROM '._DB_PREFIX_.'emailsubscription WHERE email = "'.$email.'"';
        
        return Db::getInstance()->getValue($sql);
    }

    public static function selectIdEmailSubscription($email){
        $sql = 'SELECT id FROM '._DB_PREFIX_.'emailsubscription WHERE email = "'.$email.'"';
        
        return Db::getInstance()->getValue($sql);

    }    
}    
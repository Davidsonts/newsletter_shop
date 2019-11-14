<?php

if (!defined('_PS_VERSION_'))
    exit();

include_once(_PS_MODULE_DIR_ . 'newsletter_shop/classes/NewsletterShop.php');

class Newsletter_Shop extends Module
{
    public function __construct()
    {
        $this->name = 'newsletter_shop';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Davidson Santos';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        

        parent::__construct();

        $this->controllers = array('newslettershop');
        $this->displayName = $this->l('Newsletter_Shop', 'newsletter_shop');
        $this->description = $this->l('This module is developed to display an Newsletter_Shop.', 'newsletter_shop');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', 'newsletter_shop');
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
		return parent::install() &&
            $this->registerHook('displayPosition6');
    }
    
    public function uninstall()
    {   
        include(dirname(__FILE__).'/sql/uninstall.php');
		return parent::uninstall();
    }

    public function hookdisplayPosition6($params)
    {
        // < assign variables to template >
        return $this->display(__FILE__, 'newsletter_shop.tpl');
    }

    public function displayForm()
    {
        // < init fields for form array >
        // $fields_form[0]['form'] = array(
        //     'legend' => array(
        //         'title' => $this->l('YouTube Module'),
        //     ),
        //     'input' => array(
        //         array(
        //             'type' => 'text',
        //             'label' => $this->l('URL of the YouTube video'),
        //             'name' => 'newsletter_shop_url',
        //             //'lang' => true,
        //             'size' => 20,
        //             'required' => true
        //         ),
        //     ),
        //     'submit' => array(
        //         'title' => $this->l('Save'),
        //         'class' => 'btn btn-default pull-right'
        //     )
        // );
    
        // // < load helperForm >
        // $helper = new HelperForm();
    
        // // < module, token and currentIndex >
        // $helper->module = $this;
        // $helper->name_controller = $this->name;
        // $helper->token = Tools::getAdminTokenLite('AdminModules');
        // $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    
        // // < title and toolbar >
        // $helper->title = $this->displayName;
        // $helper->show_toolbar = true;        // false -> remove toolbar
        // $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        // $helper->submit_action = 'submit'.$this->name;
        // $helper->toolbar_btn = array(
        //     'save' =>
        //         array(
        //             'desc' => $this->l('Save'),
        //             'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
        //                 '&token='.Tools::getAdminTokenLite('AdminModules'),
        //         ),
        //     'back' => array(
        //         'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
        //         'desc' => $this->l('Back to list')
        //     )
        // );
    
        // // < load current value >
        // $helper->fields_value['newsletter_shop_url'] = Configuration::get('newsletter_shop_url');
    
        // return $helper->generateForm($fields_form);
    } 
    
    public function getContent()
    {
        $output = null;
    
    
        // < here we check if the form is submited for this module >
        // if (Tools::isSubmit('submit')) {
        //     $youtube_url = strval(Tools::getValue('newsletter_shop_url'));
    
        //     // < make some validation, check if we have something in the input >
        //     if (!isset($youtube_url))
        //         $output .= $this->displayError($this->l('Please insert something in this field.'));
        //     else
        //     {
        //         // < this will update the value of the Configuration variable >
        //         Configuration::updateValue('newsletter_shop_url', $youtube_url);
    
    
        //         // < this will display the confirmation message >
        //         $output .= $this->displayConfirmation($this->l('Video URL updated!'));
        //     }
        // }

        $all_news = NewsletterShop::selectAll();

        $this->context->smarty->assign('all_news', $all_news);

        return $this->display(__FILE__, '/views/templates/admin/list.tpl');

    }
    
    public function hookModuleRoutes($params)
    {
        return [
            'module-mymodule-display' => [
                'controller' => 'display',
                'rule' => 'mymodule{/:id}',
                'keywords' => [
                'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'params' => [
                'fc' => 'module',
                'module' => 'mymodule'
                ]
            ]
        ];
    }

}    
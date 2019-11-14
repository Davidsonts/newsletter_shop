<?php
/**
 * <ModuleName> => cheque
 * <FileName> => validation.php
 * Format expected: <ModuleName><FileName>ModuleFrontController
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Newsletter_ShopNewslettershopModuleFrontController extends ModuleFrontController
{
// http://url/index.php?fc=module&module=newsletter_shop&controller=newslettershop

    const GUEST_NOT_REGISTERED = -1;
    const CUSTOMER_NOT_REGISTERED = 0;
    const GUEST_REGISTERED = 1;
    const CUSTOMER_REGISTERED = 2;

    const LEGAL_PRIVACY = 'LEGAL_PRIVACY';

    private $name;

    public function __construct()
    {
        parent::__construct();

        if(Tools::getValue('name')){
            $this->name = strval(Tools::getValue('name'));
        }
        
        $this->sendMessage();

    }

    public function sendMessage()
    {   
        if (Tools::isSubmit('sendNewsletter')) {
            $name = strval(Tools::getValue('name'));
            $email = strval(Tools::getValue('email'));
            $token = strval(Tools::getValue('token'));
            
            if($name && $email){
                                
                $verif_url = Context::getContext()->link->getModuleLink(
                    'ps_emailsubscription', 'verification', array(
                        'token' => $token,
                    )
                );

                $language = new Language($this->context->language->id);

                if ( 

                    // Mail::Send(
                    //     (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                    //     'contact_form', // email template file to be use
                    //     ' Newsletter Shop', // email subject
                    //     array(
                    //         '{$email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                    //         '{$message}' => 'Hello world' // email content
                    //     ),
                    //     Configuration::get('PS_SHOP_EMAIL'), // receiver email address 
                    //     NULL, //receiver name
                    //     $email, //from email address
                    //     $from  //from name
                    // ) 

                    // && 
                    
                    // sendConfirmationEmail($email)
                    Mail::Send(
                        $this->context->language->id,
                        'newsletter_conf',
                        $this->trans(
                            'Newsletter confirmation',
                            array(),
                            'Emails.Subject',
                            $language->locale
                        ),
                        array(),
                        pSQL($email),
                        null,
                        null,
                        null,
                        null,
                        null,
                        dirname(__FILE__).'/mails/',
                        false,
                        $this->context->shop->id
                    )              

                    &&

                    // sendVerificationEmail($email, $token)
                
                    Mail::Send(
                        $this->context->language->id,
                        'newsletter_verif',
                        $this->trans(
                            'Email verification',
                            array(),
                            'Emails.Subject',
                            $language->locale
                        ),
                        array(
                            '{verif_url}' => $verif_url,
                        ),
                        $email,
                        null,
                        null,
                        null,
                        null,
                        null,
                        dirname(__FILE__).'/mails/',
                        false,
                        $this->context->shop->id
                    )

                ) {
                    $register_status = $this->isNewsletterRegistered($email);

                    $this->register($email, $register_status);
                }
            } ///    
        }
        //die;
        return Tools::redirect('index');
        // return 

    } /// SendMessage

    /**
     * Register in email subscription.
     *
     * @param string|null $hookName
     * @return bool|string
     */
    public function newsletterRegistration($hookName = null)
    {
        // if (empty($_POST['blockHookName']) || $_POST['blockHookName'] !== $hookName) {
        //     return false;
        // }
        if (empty($_POST['email']) || !Validate::isEmail($_POST['email'])) {
            return $this->error = $this->trans('Invalid email address.', array(), 'Shop.Notifications.Error');
        } elseif ($_POST['action'] == '1') {
            $register_status = $this->isNewsletterRegistered($_POST['email']);

            if ($register_status < 1) {
                return $this->error = $this->trans('This email address is not registered.', array(), 'Modules.Emailsubscription.Shop');
            }

            if (!$this->unregister($_POST['email'], $register_status)) {
                return $this->error = $this->trans('An error occurred while attempting to unsubscribe.', array(), 'Modules.Emailsubscription.Shop');
            }

            return $this->valid = $this->trans('Unsubscription successful.', array(), 'Modules.Emailsubscription.Shop');
        } elseif ($_POST['action'] == '0') {
            $register_status = $this->isNewsletterRegistered($_POST['email']);
            if ($register_status > 0) {
                return $this->error = $this->trans('This email address is already registered.', array(), 'Modules.Emailsubscription.Shop');
            }

            $email = pSQL($_POST['email']);
            if (!$this->isRegistered($register_status)) {
                if (Configuration::get('NW_VERIFICATION_EMAIL')) {
                    // create an unactive entry in the newsletter database
                    if ($register_status == self::GUEST_NOT_REGISTERED) {
                        $this->registerGuest($email, false);
                    }

                    if (!$token = $this->getToken($email, $register_status)) {
                        return $this->error = $this->trans('An error occurred during the subscription process.', array(), 'Modules.Emailsubscription.Shop');
                    }

                    $this->sendVerificationEmail($email, $token);

                    return $this->valid = $this->trans('A verification email has been sent. Please check your inbox.', array(), 'Modules.Emailsubscription.Shop');
                } else {
                    if ($this->register($email, $register_status)) {
                        $this->valid = $this->trans('You have successfully subscribed to this newsletter.', array(), 'Modules.Emailsubscription.Shop');
                    } else {
                        return $this->error = $this->trans('An error occurred during the subscription process.', array(), 'Modules.Emailsubscription.Shop');
                    }

                    if ($code = Configuration::get('NW_VOUCHER_CODE')) {
                        $this->sendVoucher($email, $code);
                    }

                    if (Configuration::get('NW_CONFIRMATION_EMAIL')) {
                        $this->sendConfirmationEmail($email);
                    }
                }
            }
        }
    }

      /**
     * Send an email containing a voucher code.
     *
     * @param $email
     * @param $code
     *
     * @return bool|int
     */
    protected function sendVoucher($email, $code)
    {
        $language = new Language($this->context->language->id);
        return Mail::Send(
            $this->context->language->id,
            'newsletter_voucher',
            $this->trans(
                'Newsletter voucher',
                array(),
                'Emails.Subject',
                $language->locale
            ),
            array(
                '{discount}' => $code,
            ),
            $email,
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__).'/mails/',
            false,
            $this->context->shop->id
        );
    }

    /**
     * Send a confirmation email.
     *
     * @param string $email
     *
     * @return bool
     */
    protected function sendConfirmationEmail($email)
    {
        $language = new Language($this->context->language->id);
        return Mail::Send(
            $this->context->language->id,
            'newsletter_conf',
            $this->trans(
                'Newsletter confirmation',
                array(),
                'Emails.Subject',
                $language->locale
            ),
            array(),
            pSQL($email),
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__).'/mails/',
            false,
            $this->context->shop->id
        );
    }

    /**
     * Send a verification email.
     *
     * @param string $email
     * @param string $token
     *
     * @return bool
     */
    protected function sendVerificationEmail($email, $token)
    {
        $verif_url = Context::getContext()->link->getModuleLink(
            'ps_emailsubscription', 'verification', array(
                'token' => $token,
            )
        );
        $language = new Language($this->context->language->id);

        return Mail::Send(
            $this->context->language->id,
            'newsletter_verif',
            $this->trans(
                'Email verification',
                array(),
                'Emails.Subject',
                $language->locale
            ),
            array(
                '{verif_url}' => $verif_url,
            ),
            $email,
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__).'/mails/',
            false,
            $this->context->shop->id
        );
    }

    public function isNewsletterRegistered($customer_email)
    {
        $sql = 'SELECT `email`
                FROM '._DB_PREFIX_.'emailsubscription
                WHERE `email` = \''.pSQL($customer_email).'\'
                AND id_shop = '.$this->context->shop->id;

        if (Db::getInstance()->getRow($sql)) {
            return self::GUEST_REGISTERED;
        }

        $sql = 'SELECT `newsletter`
                FROM '._DB_PREFIX_.'customer
                WHERE `email` = \''.pSQL($customer_email).'\'
                AND id_shop = '.$this->context->shop->id;

        if (!$registered = Db::getInstance()->getRow($sql)) {
            return self::GUEST_NOT_REGISTERED;
        }

        if ($registered['newsletter'] == '1') {
            return self::CUSTOMER_REGISTERED;
        }

        return self::CUSTOMER_NOT_REGISTERED;
    }

    protected function register($email, $register_status)
    {   
       // die("registration");

        if ($register_status == self::GUEST_NOT_REGISTERED) {
            return $this->registerGuest($email);
        }

        if ($register_status == self::CUSTOMER_NOT_REGISTERED) {
            return $this->registerUser($email);
        }

        return false;
    }


    /**
     * Subscribe a guest to the newsletter.
     *
     * @param string $email
     * @param bool   $active
     *
     * @return bool
     */
    protected function registerGuest($email, $active = true)
    {
        $sql = 'INSERT INTO '._DB_PREFIX_.'emailsubscription (id_shop, id_shop_group, email, newsletter_date_add, ip_registration_newsletter, http_referer, active, id_lang)
                VALUES
                ('.$this->context->shop->id.',
                '.$this->context->shop->id_shop_group.',
                \''.pSQL($email).'\',
                NOW(),
                \''.pSQL(Tools::getRemoteAddr()).'\',
                (
                    SELECT c.http_referer
                    FROM '._DB_PREFIX_.'connections c
                    WHERE c.id_guest = '.(int) $this->context->customer->id.'
                    ORDER BY c.date_add DESC LIMIT 1
                ),
                '.(int) $active.',
                '. $this->context->language->id . '
                )';


        Db::getInstance()->execute($sql);

        // ADD         
        $email_existe = NewsletterShop::selectEmailSubscription($email);
        $ps_emailsubscription_id = NewsletterShop::selectIdEmailSubscription($email);

        if($email_existe && $ps_emailsubscription_id){
            NewsletterShop::addNew($this->name, $ps_emailsubscription_id);
        }
        // Add END

        return 1;

    }

    public function activateGuest($email)
    {
        return Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'emailsubscription`
                        SET `active` = 1
                        WHERE `email` = \''.pSQL($email).'\''
        );
    }

    /**
     * Returns a guest email by token.
     *
     * @param string $token
     *
     * @return string email
     */
    protected function getGuestEmailByToken($token)
    {
        $sql = 'SELECT `email`
                FROM `'._DB_PREFIX_.'emailsubscription`
                WHERE MD5(CONCAT( `email` , `newsletter_date_add`, \''.pSQL(Configuration::get('NW_SALT')).'\')) = \''.pSQL($token).'\'
                AND `active` = 0';

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Returns a customer email by token.
     *
     * @param string $token
     *
     * @return string email
     */
    protected function getUserEmailByToken($token)
    {
        $sql = 'SELECT `email`
                FROM `'._DB_PREFIX_.'customer`
                WHERE MD5(CONCAT( `email` , `date_add`, \''.pSQL(Configuration::get('NW_SALT')).'\')) = \''.pSQL($token).'\'
                AND `newsletter` = 0';

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Return a token associated to an user.
     *
     * @param string $email
     * @param string $register_status
     */
    protected function getToken($email, $register_status)
    {
        if (in_array($register_status, array(self::GUEST_NOT_REGISTERED, self::GUEST_REGISTERED))) {
            $sql = 'SELECT MD5(CONCAT( `email` , `newsletter_date_add`, \''.pSQL(Configuration::get('NW_SALT')).'\')) as token
                    FROM `'._DB_PREFIX_.'emailsubscription`
                    WHERE `active` = 0
                    AND `email` = \''.pSQL($email).'\'';
        } elseif ($register_status == self::CUSTOMER_NOT_REGISTERED) {
            $sql = 'SELECT MD5(CONCAT( `email` , `date_add`, \''.pSQL(Configuration::get('NW_SALT')).'\' )) as token
                    FROM `'._DB_PREFIX_.'customer`
                    WHERE `newsletter` = 0
                    AND `email` = \''.pSQL($email).'\'';
        }

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Ends the registration process to the newsletter.
     *
     * @param string $token
     *
     * @return string
     */
    public function confirmEmail($token)
    {
        $activated = false;

        if ($email = $this->getGuestEmailByToken($token)) {
            $activated = $this->activateGuest($email);
        } elseif ($email = $this->getUserEmailByToken($token)) {
            $activated = $this->registerUser($email);
        }

        if (!$activated) {
            return $this->trans('This email is already registered and/or invalid.', array(), 'Modules.Emailsubscription.Shop');
        }

        if ($discount = Configuration::get('NW_VOUCHER_CODE')) {
            $this->sendVoucher($email, $discount);
        }

        if (Configuration::get('NW_CONFIRMATION_EMAIL')) {
            $this->sendConfirmationEmail($email);
        }

        return $this->trans('Thank you for subscribing to our newsletter.', array(), 'Modules.Emailsubscription.Shop');
    }

    /**
     * Send the confirmation mails to the given $email address if needed.
     *
     * @param string $email Email where to send the confirmation
     *
     * @note the email has been verified and might not yet been registered. Called by AuthController::processCustomerNewsletter
     */
    public function confirmSubscription($email)
    {
        if ($email) {
            if ($discount = Configuration::get('NW_VOUCHER_CODE')) {
                $this->sendVoucher($email, $discount);
            }

            if (Configuration::get('NW_CONFIRMATION_EMAIL')) {
                $this->sendConfirmationEmail($email);
            }
        }
    }

    /**
     * Subscribe a customer to the newsletter.
     *
     * @param string $email
     *
     * @return bool
     */
    protected function registerUser($email)
    {
        $sql = 'UPDATE '._DB_PREFIX_.'customer
                SET `newsletter` = 1, newsletter_date_add = NOW(), `ip_registration_newsletter` = \''.pSQL(Tools::getRemoteAddr()).'\'
                WHERE `email` = \''.pSQL($email).'\'
                AND id_shop = '.$this->context->shop->id;

        Db::getInstance()->execute($sql);
                
        // ADD         
        $email_existe = NewsletterShop::selectEmailSubscription($email);
        $ps_emailsubscription_id = NewsletterShop::selectIdEmailSubscription($email);

        if($email_existe && $ps_emailsubscription_id){
            NewsletterShop::addNew($this->name, $ps_emailsubscription_id);
        }
        // Add END

        return 1;

    }

}

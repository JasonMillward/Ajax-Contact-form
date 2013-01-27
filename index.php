<?php
/**
 * Simple config file for jCode Lab
 *
 *
 * Released under the MIT license
 *
 * @copyright  2012 jCode
 * @category   config
 * @version    $Id$
 * @author     Jason Millward <jason@jcode.me>
 * @license    http://opensource.org/licenses/MIT
 * @package    jCode Lab
 */

require_once( dirname( __FILE__ ) . '/../config.php' );
require_once( COMMON_PHP . '/jCode_Custom/recaptchalib.php');
require_once( COMMON_PHP . '/jCode_Custom/jTPL.php');

try {

    // New jTPL class
    $smarty  = new jTPL( TEMPLATE_DIR );
    $footers = array( '<script src="../assets/js/contactForm-submit.js"></script>' );


    $smarty->assign('footers',   $footers);
    $smarty->assign('host',      $_SERVER['HTTP_HOST']);
    $smarty->assign('recaptcha', recaptcha_get_html(RECAPTCHA_PUBLIC, $error) );


    // Display the page
    $smarty->display('header.tpl');
    $smarty->display('contactForm/contact.tpl');
    $smarty->display('footer.tpl');

} catch ( exception $e ) {
    die( $e->getMessage() );
}
?>
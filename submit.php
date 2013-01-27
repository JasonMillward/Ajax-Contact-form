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
require_once( COMMON_PHP . '/jCode_Custom/recaptchalib.php' );
require_once( COMMON_PHP . '/jCode_Custom/validator.php' );

$responseJSON = array();
$responseJSON['status'] = 'success';

if ( isset ( $_POST['submit'] ) ) {

    $validator = new Validator();
    $response  = recaptcha_check_answer(
        RECAPTCHA_PRIVATE,
        $_SERVER['REMOTE_ADDR'],
        $_POST['recaptcha_challenge_field'],
        $_POST['recaptcha_response_field']
    );

    if (!$response->is_valid) {
        $responseJSON['status'] = 'error';
        $responseJSON['captchaError'] = $response->error;

        switch( $response->error ) {
            case 'incorrect-captcha-sol':
                $responseJSON['errorText'] = 'Incorrect CAPTCHA, try again.';
                break;
            case 'invalid-site-private-key':
                $responseJSON['errorText'] = 'Private key is incorrect.';
                break;
            default:
                $responseJSON['errorText'] = 'Unknown error.';
                break;
        }
    }

    if ( !$message = $validator->isValid($_POST['text'], 'text', 'Message') ) {
        $responseJSON['status'] = 'error';
        $responseJSON['errorText'] = $validator->getError();
    }

    if ( !$subject = $validator->isValid($_POST['subject'], 'string', 'Subject') ) {
        $responseJSON['status'] = 'error';
        $responseJSON['errorText'] = $validator->getError();
    }

    if ( !$email = $validator->isValid($_POST['email'], 'email') ) {
        $responseJSON['status'] = 'error';
        $responseJSON['errorText'] = $validator->getError();
    }

    if ( !$name = $validator->isValid($_POST['name'], 'string', 'Name') ) {
        $responseJSON['status'] = 'error';
        $responseJSON['errorText'] = $validator->getError();
    }


    if ( $responseJSON['status'] != 'error' ) {
        $headers   = array();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=iso-8859-1';
        $headers[] = 'From: \'Contact Robot\' <contact@domain.com>';
        $headers[] = sprintf('Reply-To: <%s>',$email);
        $headers[] = 'Sensitivity: Personal';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'X-Mailer: PHP/'.phpversion();

        $content   = array();
        $content[] = '-- New email from contact form';
        $content[] = sprintf('- Sender: %s',  $name);
        $content[] = sprintf('- Email: %s',   $email);
        $content[] = sprintf('- IP: %s',      $_SERVER['REMOTE_ADDR']);
        $content[] = '- Message:';
        $content[] = $message;

        // -- DEBUG
        $responseJSON['$subject'] = $subject;
        $responseJSON['$content'] = implode("\r\n", $content);
        $responseJSON['$headers'] = implode("\r\n", $headers);
        $responseJSON['$params']  = '-ODelivery/Mode=d';
        $responseJSON['command']  = 'mail(\'contact@domain.com\', $subject, $content, $headers, $params);';
        // -- END DEBUG

        // -- NOT DEBUG
        // mail('person@domain.com', $subject, implode("\r\n", $content), implode("\r\n", $headers), $params);
        // -- END NOT DEBUG
    }
}

// -- DEBUG
unset($_POST['recaptcha_challenge_field']);
$responseJSON['post'] = print_r( $_POST, true );
// -- END DEBUG


echo json_encode( $responseJSON );

?>
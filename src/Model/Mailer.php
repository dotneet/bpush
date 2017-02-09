<?php
namespace BPush\Model;

use Mail;
use PEAR;
use Aws\Ses\SesClient as SesClient;
use Aws\Common\Enum\Region as Region;
use Aws\Ses\Exception\SesException as SesException;

class Mailer
{
    public static function send($to, $subject, $body)
    {
        if ( mb_strtolower(MAIL_SENDING_METHOD) === 'smtp' ) {
            Mailer::sendBySMTP($to, $subject, $body);
        } else {
            Mailer::sendBySES($to, $subject, $body);
        }
    }

    public static function sendBySES($to, $subject, $body)
    {
        $client = SesClient::factory(
                array(
                'key' => AWS_ACCESS_KEY,
                'secret' => AWS_SECRET_ACCESS_KEY,
                'region' => AWS_SES_REGION
                )
            );
        $result = $client->sendEmail(array(
            'Source' => NOREPLY_MAIL_ADDRESS,
            'Destination' => array(
                'ToAddresses' => array($to)
            ),
            'Message' => array(
                'Subject' => array(
                    'Data' => $subject,
                    'Charset' => 'UTF-8',
                ),
                'Body' => array(
                    'Text' => array(
                        'Data' => $body,
                        'Charset' => 'UTF-8'
                    )
                )
            )
        ));

        return $result;
    }

    public static function sendBySMTP($to, $subject, $body)
    {
        // Mail class uses mb_send_mail() internally.
        // mb_language(),mb_internal_encoding() affect behavior of mb_send_mail().
        mb_language(DEFAULT_LOCALE);
        mb_internal_encoding("UTF-8");
        
        $from = 'system<' . NOREPLY_MAIL_ADDRESS . '>';
        $headers = [
            'From' => $from,
            'To' => $to,
            'Subject' => mb_encode_mimeheader($subject),
            'Content-Type' => 'text/plain; charset="UTF-8"',
            'Content-Transfer-Encoding' => 'base64'
        ];

        $smtp = Mail::factory('smtp', [
            'host' => SMTP_MAIL_HOST,
            'port' => SMTP_MAIL_PORT,
            'auth' => true,
            'username' => SMTP_MAIL_USER,
            'password' => SMTP_MAIL_PASS,
            //'debug' => true,
            'persist' => false
        ]); 

        $mail = $smtp->send($to, $headers, $body);

        return !PEAR::isError($mail);
    }
}


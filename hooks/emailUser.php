<?php
//
// Description
// -----------
// This function will send an email to a user of the system.
// 
// Info
// ----
// Status: beta
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_users_hooks_emailUser($ciniki, $tnid, $args) {

    //
    // Check for user_id
    //
    if( !isset($args['user_id']) || $args['user_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.1', 'msg'=>'No user specified'));
    }

    //
    // Query for user information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
        . "FROM ciniki_users "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.2', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
    }
    $user = $rc['user'];

    //  
    // The from address can be set in the config file.
    //  
//  $headers = 'From: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
//      'Reply-To: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
//      'X-Mailer: PHP/' . phpversion();
//  mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);

    if( $tnid > 0 ) { 
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
        $rc = ciniki_mail_getSettings($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.66', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        if( !isset($rc['settings']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.67', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        $settings = $rc['settings'];
    } 
    
    //
    // Send via mailgun if configured
    //
    if( isset($settings['mailgun-domain']) && $settings['mailgun-domain'] != '' 
        && isset($settings['mailgun-domain']) && $settings['mailgun-domain'] != '' 
        ) {
        //
        // Setup the message
        //
        $msg = array(
            'from' => $settings['smtp-from-name'] . ' <' . $settings['smtp-from-address'] . '>',
            'subject' => $args['subject'],
//            'subject' => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $args['subject']),
            );
        if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
            $msg['to'] = $user['name'] . ' <' . $ciniki['config']['ciniki.mail']['force.mailto'] . '>';
            $msg['subject'] .= ' [' . $user['email'] . ']';
        } else {
            $msg['to'] = $user['name'] . ' <' . $user['email'] . '>';
        }
        // Add reply to if specified
        if( isset($args['replyto_email']) && $args['replyto_email'] != '' ) {
            if( isset($args['replyto_name']) && $args['replyto_name'] != '' ) {
                $msg['h:Reply-To'] = $args['replyto_name'] . ' <' . $args['replyto_email'] . '>';
            } else {
                $msg['h:Reply-To'] = $args['replyto_email'];
            }
        }
        // Add the message
//        $msg['text'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $args['textmsg']);
        $msg['text'] = $args['textmsg'];
        if( isset($args['htmlmsg']) && $args['htmlmsg'] != '' ) {
            $msg['html'] = $args['htmlmsg'];
            //$msg['html'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $args['htmlmsg']);
        }

        //
        // Send to mailgun api
        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $settings['mailgun-key']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . $settings['mailgun-domain'] . '/messages');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);

        $rsp = json_decode(curl_exec($ch));

        $info = curl_getinfo($ch);
        if( $info['http_code'] != 200 ) {
            error_log("MAIL-ERR: [" . $user['email'] . "] " . $mail->ErrorInfo);
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.68', 'msg'=>'Unable to send email: ' . $mail->ErrorInfo));
        }
        curl_close($ch);
    }

    //
    // Otherwise send via PHPMailer smtp protocol
    //
    else {
//        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
//        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');
//        $mail = new PHPMailer;

        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/PHPMailer.php');
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/SMTP.php');
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/Exception.php');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->SMTPOptions = array(
                'tls'=>array(
                    'verify_peer'=> false,
                    'verify_peer_name'=> false,
                    'allow_self_signed'=> true,
                ),
                'ssl'=>array(
                    'verify_peer'=> false,
                    'verify_peer_name'=> false,
                    'allow_self_signed'=> true,
                ),
            );
            $mail->XMailer = ' ';
            $mail->IsSMTP();

            $use_config = 'yes';
            if( $tnid > 0 ) { 
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
                $rc = ciniki_mail_getSettings($ciniki, $tnid);
                if( $rc['stat'] == 'ok' && isset($rc['settings'])
                    && isset($rc['settings']['smtp-servers']) && $rc['settings']['smtp-servers'] != ''
                    ) {
                    $mail->Host = $rc['settings']['smtp-servers'];
                    if( isset($rc['settings']['smtp-username']) && $rc['settings']['smtp-username'] != '' ) {
                        $mail->SMTPAuth = true;
                        $mail->Username = $rc['settings']['smtp-username'];
                        $mail->Password = $rc['settings']['smtp-password'];
                    }
                    if( isset($rc['settings']['smtp-secure']) && $rc['settings']['smtp-secure'] != '' ) {
                        $mail->SMTPSecure = $rc['settings']['smtp-secure'];
                    }
                    if( isset($rc['settings']['smtp-port']) && $rc['settings']['smtp-port'] != '' ) {
                        $mail->Port = $rc['settings']['smtp-port'];
                    }
                    $use_config = 'no';
                } 
                if( isset($rc['settings']['smtp-from-address']) && $rc['settings']['smtp-from-address'] != ''
                    && isset($rc['settings']['smtp-from-name']) && $rc['settings']['smtp-from-name'] != '' ) {
                    $mail->From = $rc['settings']['smtp-from-address'];
                    $mail->Sender = $rc['settings']['smtp-from-address'];
                    $mail->FromName = $rc['settings']['smtp-from-name'];
                } else {
                    $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                    $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
                }
                if( isset($rc['settings']['smtp-reply-address']) && $rc['settings']['smtp-reply-address'] != '' ) {
                    if( isset($rc['settings']['smtp-from-name']) && $rc['settings']['smtp-from-name'] != '' ) {
                        $mail->addReplyTo($rc['settings']['smtp-reply-address'], $rc['settings']['smtp-from-name']);
                    } else {
                        $mail->addReplyTo($rc['settings']['smtp-reply-address']);
                    }
                }
            } else {
                $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
            }
        
            //
            // If not enough informatio, or none provided, default back to system email
            //
            if( $use_config == 'yes' ) {
                $mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
                if( isset($ciniki['config']['ciniki.core']['system.smtp.username']) 
                    && $ciniki['config']['ciniki.core']['system.smtp.username'] != ''
                    ) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
                    $mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
                }
                if( isset($ciniki['config']['ciniki.core']['system.smtp.secure']) 
                    && $ciniki['config']['ciniki.core']['system.smtp.secure'] != ''
                    ) {
                    $mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
                }
                if( isset($ciniki['config']['ciniki.core']['system.smtp.port']) 
                    && $ciniki['config']['ciniki.core']['system.smtp.port'] != ''
                    ) {
                    $mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];
                }
            }

            if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
                $mail->AddAddress($ciniki['config']['ciniki.mail']['force.mailto'], $user['name']);
                $args['subject'] .= ' [' . $user['email'] . ']';
            } else {
                $mail->AddAddress($user['email'], $user['name']);
            }

            // Add reply to if specified
            if( isset($args['replyto_email']) && $args['replyto_email'] != '' ) {
                if( isset($args['replyto_name']) && $args['replyto_name'] != '' ) {
                    $mail->addReplyTo($args['replyto_email'], $args['replyto_name']);
                } else {
                    $mail->addReplyTo($args['replyto_email']);
                }
            }

            //$mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $args['subject']);
            $mail->Subject = $args['subject'];
            if( isset($args['htmlmsg']) && $args['htmlmsg'] != '' ) {
                $mail->IsHTML(true);
                $mail->Body = $args['htmlmsg'];
                $mail->AltBody = $args['textmsg'];
            } else {
                $mail->IsHTML(false);
                $mail->Body = $args['textmsg'];
            }
            
            $mail->Send();
        } catch(Exception $e) {
            error_log("MAIL-ERR: [" . $user['email'] . "] " . $e->getMessage());
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.users.3', 'msg'=>'Unable to send email: ' . $e->getMessage()));
        }


//        if( !$mail->Send() ) {
//        }
    }

    return array('stat'=>'ok');
}
?>

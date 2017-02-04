<?php

/**
 * Message.php
 * @author Mohammad Hussain http://mhussa.in
 */

namespace mhussain001\mailqueue;

use Yii;
use nterms\mailqueue\models\Queue;

/**
 * Extends `yii\swiftmailer\Message` to enable queuing.
 *
 * @see http://www.yiiframework.com/doc-2.0/yii-swiftmailer-message.html
 */
class Message extends nterms\mailqueue\Message
{
    /**
     * Enqueue the message storing it in database.
     *
     * @param timestamp $time_to_send
     * @return boolean true on success, false otherwise
     */
    public function queue($time_to_send = strtotime("+5 minutes"))
    {
        return $this->mailProcess($time_to_send);
    }

    /**
     * Send mail immediately and store in database for audit 
     * @return boolean true on success, false otherwise
     */
    
    public functin sendNow()
    {
        return $this->mailProcess()
    }

    /**
     * @param timestamp $time_to_send
     * Process mail based on time_to_send. If time to send is now then it will send mail right away else it will be queued
     * @return boolean true on success, false otherwise
     */
    
    protected function mailProcess($time_to_send = 'now')
        if($time_to_send == 'now') {
            $time_to_send = time();
        }

        $item = new Queue();

        $item->from = serialize($this->from);
        $item->to = serialize($this->getTo());
        $item->cc = serialize($this->getCc());
        $item->bcc = serialize($this->getBcc());
        $item->reply_to = serialize($this->getReplyTo());
        $item->charset = $this->getCharset();
        $item->subject = $this->getSubject();
        $item->attempts = 0;
        $item->swift_message = base64_encode(serialize($this));
        $item->time_to_send = date('Y-m-d H:i:s', $time_to_send);

        $parts = $this->getSwiftMessage()->getChildren();
        // if message has no parts, use message
        if ( !is_array($parts) || !sizeof($parts) ) {
            $parts = [ $this->getSwiftMessage() ];
        }

        foreach( $parts as $part ) {
            if( !( $part instanceof \Swift_Mime_Attachment ) ) {
                /* @var $part \Swift_Mime_MimeEntity */
                switch( $part->getContentType() ) {
                    case 'text/html':
                        $item->html_body = $part->getBody();
                    break;
                    case 'text/plain':
                        $item->text_body = $part->getBody();
                    break;
                }

                if( !$item->charset ) {
                    $item->charset = $part->getCharset();
                }
            }
        }
        /**
         if time to send is now then send mail and track in db
        */
        if($time_to_send == 'now') {
            if ($message = $item->toMessage()) {
                if ($this->send($message)) {
                    $item->sent_time = new \yii\db\Expression('NOW()');
                    $attributes[] = 'sent_time';
                } else {
                    $success = false;
                }

                $item->attempts++;
                $item->last_attempt_time = new \yii\db\Expression('NOW()');
            }
        }

        return $item->save();
    }
}

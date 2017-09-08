<?php
namespace makcent\mailer\queue;

use Yii;

class Message extends \yii\swiftmailer\Message
{
	public function queue()
	{
		$redis = Yii::$app->redis;

		if(empty($redis)){
			throw new \yii\base\InvalidConfigException('redis not found in config.');
		}

		$mailer = Yii::$app->mailer;

		if(empty($mailer) || !$redis->select($mailer->database)){
			throw new \yii\base\InvalidConfigException('mailer db not found in config.');
		}

		$message = [];
		$message['from'] = array_keys($this->from);
		$message['to'] = array_keys($this->getTo());
		$message['cc'] = array_keys($this->getCc());
		$message['bcc'] = array_keys($this->getBcc());
		$message['reply_to'] = array_keys($this->getReplyTo());
		$message['charset'] = array_keys($this->getCharset());
		$message['subject'] = array_keys($this->getSubject());
		$parts = $this->getSwiftMessage()->getChildred();

		if(!is_array($parts) || !sizeof($parts)){
			$parts = [$this->getSwiftMessage()];
		}

		foreach ($parts as $part) {
			if(!$part instanceof \Swift_Mime_Attachment){
				switch ($part->getContentType()) {
					case 'text/html':
						$message['html_body'] = $part->getBody();
						break;
					case 'text/plain':
						$message['text_body'] = $part->getBody();
						break;
				}

				if(!$message['charset']){
					$message['charset'] = $part->getCharset();
				}
			}
		}
		return $redis->rpush($mailer->queue, json_encode($message));
	}
}




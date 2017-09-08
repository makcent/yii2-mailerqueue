<?php
namespace makcent\mailer\queue;

use Yii;

class MailerQueue extends \yii\swiftmailer\Mailer
{
	public $messageClass = "makcent\mailer\queue\Message";

	public $queue = "mails";

	public $database = 1;

	public function process()
	{
		$redis = Yii::$app->redis;

		if(empty($redis)){
			throw new \yii\base\InvalidConfigException('redis not found in config.');
		}

		if($redis->select($this->database) && $messages = $redis->lrange($this->queue, 0, -1)){
			$messageObj = new Message();
			foreach ($messages as $message) {
				$message = json_decode($message, true);
				if(empty($message) || !$this->setMessage($messageObj, $message)){
					throw new \ServerErrorHttpException("message error");
				}

				if($messageObj->send()){
					$redis->lrem($this->queue, -1, json_encode($message));
				}
			}
		}
		return true;
	}

	public function setMessage($messageObj, $message)
	{
		if(empty($messageObj)){
			return false;
		}

		if(!empty($message['from']) && !empty($message['to'])){
			$messageObj->setFrom($message['from'])->setTo($message['to']);
			if(!empty($message['cc'])){
				$messageObj->setCc($message['cc']);
			}

			if(!empty($message['bcc'])){
				$messageObj->setBcc($message['bcc']);
			}

			if(!empty($message['reply_to'])){
				$messageObj->setReplyTo($message['reply_to']);
			}

			if(!empty($message['charset'])){
				$messageObj->setCharset($message['charset']);
			}

			if(!empty($message['subject'])){
				$messageObj->setSubject($message['subject']);
			}

			if(!empty($message['html_body'])){
				$messageObj->setHtmlBody($message['html_body']);
			}

			if(!empty($message['text_body'])){
				$messageObj->setTextBody($message['text_body']);
			}

			return $messageObj;
		}

		return false;
	}


}
<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\MailSenders;

use Doctrine\DBAL\Connection;
use Mailsender\Core\Entity\IMail;
use Mailsender\Core\Exceptions\CreateMailException;
use Mailsender\Core\MailSenders\IMailSender;
use Oli\RabbitMq\Connection\ConnectionFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class QueueRabbitMqMailSender
 * Copyright (c) 2018 Petr Olisar
 * @package Sportisimo\Ecommerce\Console\Model\RabbitMQ
 */
class QueueRabbitMqMailSender implements IMailSender
{

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var AMQPChannel
	 */
	private $channel;

	/**
	 * @var \Oli\RabbitMq\Connection\ConnectionFactory
	 */
	private $connectionRabbitMQFactory;

	/**
	 * QueueRabbitMqMailSender constructor.
	 * @param \Doctrine\DBAL\Connection $connection
	 * @param \Oli\RabbitMq\Connection\ConnectionFactory $connectionRabbitMQFactory
	 */
	public function __construct(Connection $connection, ConnectionFactory $connectionRabbitMQFactory)
	{
		$this->connection = $connection;
		$this->connectionRabbitMQFactory = $connectionRabbitMQFactory;
	}

	/**
   * Send created IMail entity.
   * @param IMail $mail
   * @throws CreateMailException
   */
  public function send(IMail $mail): void
  {
    $queue = 'newsletters';
    $exchange = 'mails';

    try
    {
		$data = [
			$mail->getMailType()->getId(),
			json_encode($mail->getRecipient()),
			json_encode($mail->getSender()),
			$mail->getSubject(),
			$mail->getCharset(),
			$mail->getData(),
			$mail->getHashcode(),
		];

		$this->connection->prepare('INSERT INTO mails (`mail_type_id`, `recipient`, `sender`, `subject`, `charset`, `data`, `hashcode`, `date_created`) VALUES (?,?,?,?,?,?,?,NOW())')->execute($data);

	  $connection = $this->connectionRabbitMQFactory->getConnection('default');
	  if($this->channel === null)
	  {
      	$this->channel = $connection->getChannel();
	  }
      $this->channel->exchange_declare($exchange, 'direct');
      $this->channel->queue_declare($queue, false, true, false, false);
      $this->channel->queue_bind($queue, $exchange, 'newsletter');

      $msg = new AMQPMessage((int) $this->connection->lastInsertId(), [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        'content_type' => 'text/plain',
      ]);
      $this->channel->basic_publish($msg, $exchange, 'newsletter');
    }
    catch (\Throwable $e)
    {
      throw new CreateMailException('Nepodarilo se ulozit e-mail do databaze.', 0, $e);
    }
  }

}

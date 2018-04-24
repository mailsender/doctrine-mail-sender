<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\MailSenders;

use Doctrine\DBAL\Connection;
use Mailsender\Core\Entity\IMail;
use Mailsender\Core\Exceptions\CreateMailException;
use Mailsender\Core\MailSenders\IMailSender;

/**
 * Class QueueMailSender
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\MailSenders
 */
class QueueMailSender implements IMailSender
{

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * PHPMailSender constructor.
	 * @param \Doctrine\DBAL\Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Send created IMail entity.
	 * @param IMail|\Mailsender\DoctrineSender\Entity\Mail $mail
	 * @throws CreateMailException
	 */
	public function send(IMail $mail): void
	{
		try
		{
			$this->connection->transactional(
				function () use ($mail) {
					$data = [
						$mail->getMailType()
							->getId(),
						json_encode($mail->getRecipient()),
						json_encode($mail->getSender()),
						$mail->getSubject(),
						$mail->getCharset(),
						$mail->getData(),
						$mail->getHashcode(),
					];

					$this->connection->prepare(
						'INSERT INTO mails (`mail_type_id`, `recipient`, `sender`, `subject`, `charset`, `data`, `hashcode`, `date_created`) VALUES (?,?,?,?,?,?,?,NOW())'
					)
						->execute($data);

					$this->connection->prepare('INSERT INTO mail_queue (`mail_id`) VALUES (?)')
						->execute([(int) $this->connection->lastInsertId()]);
				}
			);
		}
		catch (\Throwable $e)
		{
			throw new CreateMailException('Nepodarilo se ulozit e-mail do databaze.', 0, $e);
		}
	}

}

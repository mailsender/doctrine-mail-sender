<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mailsender\Core\Entity\Attachment;
use Mailsender\Core\Entity\Contact;
use Mailsender\Core\Entity\IContact;
use Mailsender\Core\Entity\IMailType;

/**
 * Class MailType
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\Entity
 * @ORM\Entity
 * @ORM\Table(name="mail_types")
 */
class MailType implements IMailType
{

	/**
	 * @var int
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="code", type="string", length=128, nullable=false)
	 */
	private $name;

	/**
	 * @var IContact
	 * @ORM\Column(type="json_document", options={"jsonb": false})
	 *
	 */
	private $sender;

	/**
	 * @var string
	 * @ORM\Column(type="string", type="string", length=64, nullable=true)
	 */
	private $subject;

	/**
	 * @var \Mailsender\Core\Entity\IAttachment[]|array
	 * @ORM\Column(type="json_document", options={"jsonb": false})
	 */
	private $attachments = [];

	/**
	 * @var IContact[]|array
	 * @ORM\Column(name="bcc_recipients", type="json_document", options={"jsonb": false})
	 */
	private $bccRecipients;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=16, nullable=false)
	 */
	private $charset = 'utf-8';

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $priority = 0;

	/**
	 * MailType constructor.
	 * @param string $name
	 * @param IContact $sender
	 * @param string $subject
	 */
	public function __construct(string $name, IContact $sender, string $subject)
	{
		$this->name = $name;
		$this->sender = $sender;
		$this->subject = $subject;
	}

	/**
	 * @param string $name
	 * @return MailType
	 */
	public function setName(string $name): MailType
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @param IContact $sender
	 * @return MailType
	 */
	public function setSender(IContact $sender): MailType
	{
		$this->sender = $sender;

		return $this;
	}

	/**
	 * @param string $subject
	 * @return MailType
	 */
	public function setSubject(string $subject): MailType
	{
		$this->subject = $subject;

		return $this;
	}

	/**
	 * @param array|\Mailsender\Core\Entity\IAttachment[] $attachments
	 * @return MailType
	 */
	public function setAttachments($attachments): MailType
	{
		$this->attachments = $attachments;

		return $this;
	}

	/**
	 * @param array|IContact[] $bccRecipients
	 * @return MailType
	 */
	public function setBccRecipients($bccRecipients): MailType
	{
		$this->bccRecipients = $bccRecipients;

		return $this;
	}

	/**
	 * @param string $charset
	 * @return MailType
	 */
	public function setCharset(string $charset): MailType
	{
		$this->charset = $charset;

		return $this;
	}

	/**
	 * @param int $priority
	 * @return MailType
	 */
	public function setPriority(int $priority): MailType
	{
		$this->priority = $priority;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getSender(): IContact
	{
		return new Contact($this->sender['name'], $this->sender['email']);
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	/**
	 * @return \Mailsender\Core\Entity\IAttachment[]|array
	 */
	public function getAttachments(): array
	{
		$array = [];
		if($this->attachments !== null)
		{
			foreach ($this->attachments as $attachment)
			{
				$array[] = new Attachment($attachment['filename'], $attachment['path']);
			}
		}
		return $array;
	}

	/**
	 * @return IContact[]|array
	 */
	public function getBccRecipients(): array
	{
		$array = [];
		if($this->bccRecipients !== null)
		{
			foreach ($this->bccRecipients as $bccRecipient)
			{
				$array[] = new Attachment($bccRecipient['filename'], $bccRecipient['path']);
			}
		}
		return $array;
	}

	public function getCharset(): string
	{
		return $this->charset;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}

}

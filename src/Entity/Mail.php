<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Mailsender\Core\Entity\Attachment;
use Mailsender\Core\Entity\Contact;
use Mailsender\Core\Entity\IAttachment;
use Mailsender\Core\Entity\IContact;
use Mailsender\Core\Entity\IMail;
use Mailsender\Core\Entity\IMailType;
use Ramsey\Uuid\Uuid;

/**
 * Class Mail
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\Entity
 * @ORM\Entity
 * @ORM\Table(name="mails")
 */
class Mail implements IMail
{

	/**
	 * @var int
	 * @ORM\Id()
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 * @
	 */
	private $id;

	/**
	 * @var \Mailsender\Core\Entity\IMailType
	 * @ORM\ManyToOne(targetEntity="MailType")
	 * @ORM\JoinColumn(name="mail_type_id", referencedColumnName="id", nullable=false)
	 */
	private $mailType;

	/**
	 * @var array
	 * @ORM\Column(type="json_document", nullable=false, options={"jsonb": false})
	 */
	private $recipient;

	/**
	 * @var array
	 * @ORM\Column(type="json_document", nullable=false, options={"jsonb": false})
	 */
	private $sender;

	/**
	 * @var string|null
	 * @ORM\Column(type="string", length=64, nullable=true)
	 */
	private $subject;

	/**
	 * @var array
	 * @ORM\Column(type="json_document", nullable=true, options={"jsonb": false})
	 */
	private $attachments;

	/**
	 * @var array
	 * @ORM\Column(name="bcc_recipients", type="json_document", nullable=true, options={"jsonb": false})
	 */
	private $bccRecipients;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=16, nullable=false)
	 */
	private $charset;

	/**
	 * @var DateTimeInterface
	 * @ORM\Column(name="date_created", type="datetime", nullable=false)
	 */
	private $dateCreated;

	/**
	 * @var string
	 * @ORM\Column(type="json_document", nullable=true, options={"jsonb": false})
	 */
	private $data = '{}';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=32, nullable=false)
	 */
	private $hashcode;

	/**
	 * @var DateTimeInterface|null
	 * @ORM\Column(name="date_sent", type="datetime", nullable=true)
	 */
	private $dateSent;

	/**
	 * Mail constructor.
	 * @param \Mailsender\Core\Entity\IMailType $mailType
	 */
	public function __construct(IMailType $mailType)
	{
		$this->mailType = $mailType;
		$this->sender = $mailType->getSender();
		$this->subject = $mailType->getSubject();
		$this->attachments = $mailType->getAttachments();
		$this->bccRecipients =$mailType->getBccRecipients();
		$this->charset = $mailType->getCharset();
		$this->dateCreated = new \DateTime();
		$this->hashcode = md5(
			Uuid::uuid4()
				->toString()
		);
	}

	/**
	 * @param \Mailsender\Core\Entity\IContact $recipient
	 * @return Mail
	 */
	public function setRecipient(IContact $recipient): Mail
	{
		$this->recipient = $recipient;

		return $this;
	}

	/**
	 * @param \Mailsender\Core\Entity\IContact $sender
	 * @return Mail
	 */
	public function setSender(IContact $sender): Mail
	{
		$this->sender = $sender;

		return $this;
	}

	/**
	 * @param null|string $subject
	 * @return Mail
	 */
	public function setSubject(?string $subject): Mail
	{
		$this->subject = $subject;

		return $this;
	}

	/**
	 * @param \Mailsender\Core\Entity\IAttachment $attachment
	 * @return Mail
	 */
	public function setAttachments(IAttachment $attachment): Mail
	{
		$this->attachments[] = $attachment;

		return $this;
	}

	/**
	 * @param \Mailsender\Core\Entity\IContact $bccRecipient
	 * @return Mail
	 */
	public function setBccRecipients(IContact $bccRecipient): Mail
	{
		$this->bccRecipients[] = $bccRecipient;

		return $this;
	}

	/**
	 * @param string $charset
	 * @return Mail
	 */
	public function setCharset(string $charset): Mail
	{
		$this->charset = $charset;

		return $this;
	}

	/**
	 * @param string $data
	 * @return Mail
	 */
	public function setData(string $data): Mail
	{
		$this->data = json_decode($data);

		return $this;
	}

	/**
	 * @param \DateTimeInterface $dateSent
	 * @return Mail
	 */
	public function setDateSent(\DateTimeInterface $dateSent): Mail
	{
		$this->dateSent = $dateSent;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return IMailType
	 */
	public function getMailType(): IMailType
	{
		return $this->mailType;
	}

	/**
	 * @return IContact
	 */
	public function getRecipient(): IContact
	{
		if($this->recipient instanceof IContact)
		{
		  return $this->recipient;
		}
		return new Contact($this->recipient['name'], $this->recipient['email']);
	}

	/**
	 * @return IContact
	 */
	public function getSender(): IContact
	{
		if($this->sender instanceof IContact)
		{
			return $this->sender;
		}
		return new Contact($this->sender['name'], $this->sender['email']);
	}

	/**
	 * @return null|string
	 */
	public function getSubject(): ?string
	{
		return $this->subject;
	}

	/**
	 * @return IAttachment[]|array
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

	/**
	 * @return string
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}

	/**
	 * @return DateTimeInterface
	 */
	public function getDateCreated(): DateTimeInterface
	{
		return $this->dateCreated;
	}

	/**
	 * @return string
	 */
	public function getData(): string
	{
		return json_encode($this->data);
	}

	/**
	 * @return string
	 */
	public function getHashcode(): string
	{
		return $this->hashcode;
	}

	/**
	 * @return DateTimeInterface
	 */
	public function getDateSent(): DateTimeInterface
	{
		return $this->dateSent;
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

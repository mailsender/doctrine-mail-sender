<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender;

use Mailsender\Core\Entity\IMail;
use Mailsender\Core\Exceptions\CreateMailException;
use Mailsender\Core\IMailRepository;
use Mailsender\Core\IMailService;
use Mailsender\Core\IMailTypeRepository;
use Mailsender\DoctrineSender\Entity\Mail;
use Mailsender\DoctrineSender\MailTypes\IMailTypeFacadeFactory;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class MailService
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender
 */
final class MailDemoService implements IMailService
{

	/**
	 * @var \Mailsender\Core\IMailRepository
	 */
	private $mailRepository;

	/**
	 * @var \Mailsender\Core\IMailTypeRepository
	 */
	private $mailTypeRepository;

	/**
	 * @var \Mailsender\DoctrineSender\MailTypes\IMailTypeFacadeFactory
	 */
	private $mailTypeFacadeFactory;

	/**
	 * @var null|\Symfony\Component\Serializer\SerializerInterface
	 */
	private $serializer;

	/**
	 * MailService constructor.
	 * @param \Mailsender\Core\IMailRepository $mailRepository
	 * @param \Mailsender\Core\IMailTypeRepository $mailTypeRepository
	 * @param \Mailsender\DoctrineSender\MailTypes\IMailTypeFacadeFactory $mailTypeFacadeFactory
	 * @param null|\Symfony\Component\Serializer\SerializerInterface $serializer
	 */
	public function __construct(IMailRepository $mailRepository, IMailTypeRepository $mailTypeRepository, IMailTypeFacadeFactory $mailTypeFacadeFactory, ?SerializerInterface $serializer = null)
	{
		$this->mailRepository = $mailRepository;
		$this->mailTypeRepository = $mailTypeRepository;
		$this->mailTypeFacadeFactory = $mailTypeFacadeFactory;
		$this->serializer = $serializer;
	}

	/**
	 * Create instance of entity IMail.
	 * @param string $mailTypeName
	 * @return IMail
	 * @throws \Mailsender\Core\Exceptions\CreateMailException
	 */
	public function create(string $mailTypeName): IMail
	{
		try
		{
			$mailType = $this->mailTypeRepository->fetchMailTypeByName($mailTypeName);
			$mail = new Mail($mailType);
		}
		catch (\Exception $e)
		{
			throw new CreateMailException('Nepodarilo se vytvorit e-mail', 0, $e);
		}

		return $mail;
	}

	/**
	 * Return content of the e-mail.
	 * @param IMail $email
	 * @return string
	 */
	public function getContent(IMail $email): string
	{
		$mailType = $email->getMailType();
		$mailTypeFacade = $this->mailTypeFacadeFactory->create($mailType->getName());
		return $mailTypeFacade->getContent($email);
	}

	/**
	 * Create e-mail from json.
	 * @param string $json
	 * @return IMail|object
	 * @throws \InvalidArgumentException
	 */
	public function createByJson(string $json): IMail
	{
		if($this->serializer === null)
		{
			throw new \InvalidArgumentException('Serializer is not set. You have to set serializer first.');
		}

		return $this->serializer->deserialize($json, Mail::class, 'json');
	}

	/**
	 * Create e-mail from ID.
	 * @param int $id
	 * @return IMail
	 */
	public function createById(int $id): IMail
	{
		return $this->mailRepository->fetchMailById($id);
	}

}

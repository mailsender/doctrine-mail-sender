<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Mailsender\Core\Entity\IMailType;
use Mailsender\Core\IMailTypeRepository;
use Mailsender\DoctrineSender\Entity\MailType;

/**
 * Class MailTypeRepository
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\Repository
 */
class MailTypeRepository implements IMailTypeRepository
{

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	private $repository;

	/**
	 * MailRepository constructor.
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->repository = $entityManager->getRepository(MailType::class);
	}

	/**
	 * Returns mail type from database as one object.
	 * @param string $name
	 * @return IMailType
	 * @throws \Doctrine\ORM\NoResultException
	 */
	public function fetchMailTypeByName(string $name): IMailType
	{
		/** @var IMailType $mail */
		$mail = $this->repository->findOneBy(['name' => $name,]);
		if($mail === null)
		{
			throw new NoResultException();
		}

		return $mail;
	}
}

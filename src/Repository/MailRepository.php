<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Mailsender\Core\Entity\IMail;
use Mailsender\Core\IMailRepository;
use Mailsender\DoctrineSender\Entity\Mail;

/**
 * Class MailRepository
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\Repository
 */
class MailRepository implements IMailRepository
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
		$this->repository = $entityManager->getRepository(Mail::class);
	}

	/**
	 * @param int $id
	 * @return IMail
	 * @throws \Doctrine\ORM\NoResultException
	 */
	public function fetchMailById(int $id): IMail
	{
		/** @var IMail $mail */
		$mail = $this->repository->find($id);
		if($mail === null)
		{
			throw new NoResultException();
		}

		return $mail;
	}

}

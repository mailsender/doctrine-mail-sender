<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender;

use Mailsender\Core\Entity\IMail;
use Mailsender\DoctrineSender\MailTypes\IMailTypeFacade;

/**
 * Class MailDemoFacade
 * Copyright (c) 2017 Sportisimo s.r.o.
 * @package Mailsender\DoctrineMailSet
 */
class MailDemoFacade implements IMailTypeFacade
{

	/**
	 * Returns main content of the mail.
	 * @param IMail $mail
	 * @return string
	 */
	public function getContent(IMail $mail): string
	{
		return 'Test string';
	}

}

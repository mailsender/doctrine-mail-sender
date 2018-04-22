<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\MailTypes;

use Mailsender\Core\Entity\IMail;

/**
 * Interface IMailTypeFacade
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\MailTypes
 */
interface IMailTypeFacade
{

	/**
	 * Returns main content of the mail.
	 * @param IMail $mail
	 * @return string
	 */
	public function getContent(IMail $mail): string;

}

<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\MailTypes;

/**
 * Interface IMailTypeFacadeFactory
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\MailTypes
 */
interface IMailTypeFacadeFactory
{

	/**
	 * Create facade, which process template by mail type.
	 * @param string $mailTypeFacadeName
	 * @return \Mailsender\DoctrineSender\MailTypes\IMailTypeFacade
	 */
	public function create(string $mailTypeFacadeName): IMailTypeFacade;

}

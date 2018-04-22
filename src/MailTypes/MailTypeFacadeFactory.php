<?php declare(strict_types = 1);

namespace Mailsender\DoctrineSender\MailTypes;

/**
 * Class MailTypeFacadeFactory
 * Copyright (c) 2018 Petr Olisar
 * @package Mailsender\DoctrineSender\MailTypes
 */
class MailTypeFacadeFactory implements IMailTypeFacadeFactory
{

	/**
	 * If template is not write in native PHP, will by converted to native PHP and save to a temp dir.
	 * @var string
	 */
	private $tempDir;

	/**
	 * MailTypeFacadeFactory constructor.
	 * @param string $tempDir
	 */
	public function __construct(string $tempDir)
	{
		$this->tempDir = $tempDir;
	}

	/**
	 * Create new instance of facade, which process template by mail type.
	 * @param string $mailTypeFacadeName
	 * @return IMailTypeFacade
	 */
	public function create(string $mailTypeFacadeName): IMailTypeFacade
	{
		return new $mailTypeFacadeName($this->tempDir);
	}

}

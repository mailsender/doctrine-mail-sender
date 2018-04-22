<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';

\Tracy\Debugger::enable(false, __DIR__ . '/../temp/');

try
{
	if(!\Doctrine\DBAL\Types\Type::hasType('json_document'))
	{
		\Doctrine\DBAL\Types\Type::addType('json_document', \Dunglas\DoctrineJsonOdm\Type\JsonDocumentType::class);
		/** @var \Dunglas\DoctrineJsonOdm\Type\JsonDocumentType $type */
		$type = \Doctrine\DBAL\Types\Type::getType('json_document');
		$type->setSerializer(
				new \Symfony\Component\Serializer\Serializer(
					[new \Dunglas\DoctrineJsonOdm\Normalizer\ObjectNormalizer(new Symfony\Component\Serializer\Normalizer\ObjectNormalizer())],
					[new \Symfony\Component\Serializer\Encoder\JsonEncoder()]
				)
			);
	}
}
catch (\Doctrine\DBAL\DBALException|InvalidArgumentException|\Symfony\Component\Serializer\Exception\RuntimeException $e)
{
	\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
	echo 'Wrong json document inicialization.';
	exit(1);
}

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(array(dirname(__DIR__) . '/src/Entity'), $isDevMode, __DIR__ . '/../temp/', null, false);
$config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

// database configuration parameters
$conn = array(
	'dbname' => 'sandbox',
	'user' => 'sandbox',
	'password' => 'sandbox',
	'host' => '192.168.33.10',
	'driver' => 'pdo_mysql',
);
try
{
	$em = \Doctrine\ORM\EntityManager::create($conn, $config);
}
catch (\Doctrine\ORM\ORMException|InvalidArgumentException  $e)
{
	\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
	echo 'Wrong database configuration.';
	exit(1);
}


// ------------------------------------- Script ----------------------------------

$mailRepository = new \Mailsender\DoctrineSender\Repository\MailRepository($em);
$mailTypeRepository = new \Mailsender\DoctrineSender\Repository\MailTypeRepository($em);
$mailTypeFacadeFactory = new \Mailsender\DoctrineSender\MailTypes\MailTypeFacadeFactory(__DIR__ . '/../temp/');
$service = new \Mailsender\DoctrineSender\MailDemoService($mailRepository, $mailTypeRepository, $mailTypeFacadeFactory);

/** @var \Mailsender\DoctrineSender\Entity\Mail $mail */
try
{
	$mail = $service->create(\Mailsender\DoctrineSender\MailDemoFacade::class);
}
catch (\Mailsender\Core\Exceptions\CreateMailException $e)
{
	\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
	echo 'E-mail can not be created.';
	exit(1);
}
$mail->setRecipient(new \Mailsender\Core\Entity\Contact('Petr Olišar', 'petr.olisar@gmail.com'));
$mail->setData(json_encode(''));

$mailSender = new \Mailsender\DoctrineSender\MailSenders\QueueMailSender($em->getConnection());
try
{
	$mailSender->send($mail);
}
catch (\Mailsender\Core\Exceptions\CreateMailException $e)
{
	\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
	echo 'E-mail can not be inserted to database.';
	exit(1);
}
var_dump($mail);

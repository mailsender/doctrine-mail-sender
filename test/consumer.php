<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';

\Tracy\Debugger::enable(false, __DIR__ . '/../temp/');

if(is_file(__DIR__ . 'settings.php'))
{
  require_once __DIR__ . 'settings.php';
}
else
{
	$settings = [
		'mailServer' => [
			'host' => '',
			'port' => 0,
			'username' => '',
			'password' => '',
			'secure' => '',
		],
		'queue' => [
			'maxAttemptsCount' => 10,
			'limit' => 10,
		],
	];
}


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


$uuid = \Ramsey\Uuid\Uuid::uuid4();
$maxAttemptsCount = 10;

$connection = $em->getConnection();
try
{
	$connection->executeUpdate(
		'UPDATE mail_queue SET job_id =?
		 WHERE job_id IS NULL AND attempts_count < ?
		 ORDER BY priority DESC, mail_id ASC
		 LIMIT ' . $settings['queue']['limit'], [$uuid->toString(), $settings['queue']['maxAttemptsCount']]
	);
}
catch (\Doctrine\DBAL\DBALException $e)
{
	\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
	echo 'Error while locking job.';
	exit(1);
}


$qb = $connection->createQueryBuilder();
/** @var \Doctrine\DBAL\Driver\PDOStatement $result */
$result = $qb->select('mq.mail_id')->from('mail_queue', 'mq')
	->where('job_id = :jobId')
	->addOrderBy('priority', 'DESC')
	->addOrderBy('mail_id', 'ASC')
	->setParameters(['jobId' => $uuid->toString(),])->execute();


$success = [];
$error = [];
foreach ($result->fetchAll() as $item)
{
	try
	{
		/** @var \Mailsender\DoctrineSender\Entity\Mail $mail */
		$mail = $service->createById((int) $item['mail_id']);

		$mailSender = new \Mailsender\Core\MailSenders\PHPMailSender(
			$settings['mailServer']['host'], $settings['mailServer']['port'], $settings['mailServer']['username'],
			$settings['mailServer']['password'], $settings['mailServer']['secure'], $service
		);

		$mailSender->send($mail);
		$success[] = $mail->getId();
	}
	catch (\PHPMailer\PHPMailer\Exception $e)
	{
		\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
		echo 'Error while sending mail.';
		$error[] = $item['mail_id'];
	}
}

// ------------------------------------ Process sended e-mails -----------------------------------------------

// Updatne errors
if(!empty($error))
{
	try
	{
		$connection->executeUpdate(
			'UPDATE mail_queue SET `job_id` = ?, `attempts_count` = `attempts_count` + 1 WHERE job_id = ? AND mail_id IN (?)',
			[
				null, $uuid->toString(), $error
			]
		);
	}
	catch (\Doctrine\DBAL\DBALException $e)
	{
		\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
		echo 'Error while update mail queue.';
	}
}

// Delete success
if(!empty($success))
{

	try
	{
		$connection->executeQuery(
			'DELETE FROM mail_queue WHERE job_id = ? AND mail_id IN (?)', [
				$uuid->toString(),
				$success,
			], [\PDO::PARAM_STR, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
		)->execute();

		$connection->executeUpdate(
			'UPDATE mails SET `date_sent` = ? WHERE id IN (?)',
			[new DateTime(), $error],
			['datetime', \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
		);
	}
	catch (\Doctrine\DBAL\DBALException $e)
	{
		\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
		echo 'Error while update mail queue.';
	}
}
exit(0);

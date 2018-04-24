<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';

\Tracy\Debugger::enable(false, __DIR__ . '/../temp/');

if(is_file(__DIR__ . '/settings.php'))
{
  require_once __DIR__ . '/settings.php';
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
		'rabbit' => [
			'host' => '192.168.33.10',
			'port' => 5672,
			'user' => 'sportisimo',
			'password' => 'sportisimo',
			'vhost' => '/',
			'heartbeat' => 20.0,
			'connectionTimeout' => 3.0,
			'readWriteTimeout' => 40.0,
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
$mailSender = new \Mailsender\Core\MailSenders\PHPMailSender(
	$settings['mailServer']['host'], $settings['mailServer']['port'], $settings['mailServer']['username'],
	$settings['mailServer']['password'], $settings['mailServer']['secure'], $service
);


$queue = 'newsletters';
$exchange = 'mails';

$connectionProvider = new \Oli\RabbitMq\Connection\ConnectionProvider($settings['rabbit']);
$rabbitConnection = (new \Oli\RabbitMq\Connection\ConnectionFactory(['default' => $connectionProvider]))->getConnection('default');

$channel = $rabbitConnection->getChannel();
$channel->exchange_declare($exchange, 'direct');
$channel->queue_declare($queue, false, true, false, false);
$channel->queue_bind($queue, $exchange, 'newsletter');

$callback = function(\PhpAmqpLib\Message\AMQPMessage $msg) use ($mailRepository, $mailSender, $em) {
	try
	{
		$mail = $mailRepository->fetchMailById((int) $msg->getBody());

		$mailSender->send($mail);

		$em->getConnection()->prepare('UPDATE mails SET `date_sent` = ? WHERE id = ?')->execute([new DateTime(), $mail->getId()]);

		$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

		unset($mail);
	}
	catch(\Throwable $e)
	{
		$msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
	}
};


$channel->basic_qos(null, 20, null);
$channel->basic_consume('newsletters', 'newsletter' . getmypid(), false, false, false, false, $callback);

try
{
	while(\count($channel->callbacks) && memory_get_usage() < 15000000)
	{
		$channel->wait();
	}
}
catch(\PhpAmqpLib\Exception\AMQPOutOfBoundsException $e)
{
	echo $e->getMessage();
}
catch(\PhpAmqpLib\Exception\AMQPRuntimeException $e)
{
	echo $e->getMessage();
}
exit(0);

<?php declare(strict_types = 1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with file to your own project bootstrap
require_once __DIR__ . '/vendor/autoload.php';

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
	echo 'Wrong json document inicialization.';
	exit(1);
}

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(array(realpath(__DIR__ . '/src/Entity')), $isDevMode, __DIR__ . '/../temp/', null, false);
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
	echo 'Wrong database configuration.';
	exit(1);
}

return ConsoleRunner::createHelperSet($em);

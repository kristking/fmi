<?php

namespace CsnUser\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Db\TableGateway\TableGateway;

use CsnUser\Form\UserForm;
use CsnUser\Form\UserFilter;

use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\Reflection as ReflectionHydrator;
use CsnUser\Entity\UserEntity;
use CsnUser\Entity\User;

// Doctrine Entity manager
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

// We may prepare the Doctrine Class Loader if we didn't have Composer Autoloader and Zend Standard Autoloader
use Doctrine\Common\ClassLoader;

// Doctrine Annotations
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use DoctrineORMModule\Stdlib\Hydrator\DoctrineEntity;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder as DoctrineAnnotationBuilder;

// Pagination
use DoctrineModule\Paginator\Adapter\Selectable as SelectableAdapter;
use Doctrine\Common\Collecttions\Criteria as DoctrineCriteria; // for criteria
use Zend\Paginator\Paginator;


use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;

// Zend Annotation 
use Zend\Form\Annotation\AnnotationBuilder;
// for the form
use Zend\Form\Element;

class UserDoctrinePaginatorController extends AbstractActionController
{
	private $usersTable;
	// for Doctrine
	private $conn; // part of dbal;
	private $entityManager; // ORM
	
 	public function __construct()
	{
		// self::__construct();
//-		$this->registerDocrineClassLoader();		
	}
	
	// R -retrieve 	CRUD
	public function indexAction()
	{
		$entityManager = $this->getEntityManager();
/*
//-		$entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
//-		$dql = "SELECT u FROM CsnUser\Entity\User u"; // the best form
//-		$dql = "SELECT u FROM CsnUser\Entity\UserEntity u"; // Class "CsnUser\Entity\UserEntity" is not a valid entity or mapped super class.
//-		$dql = "SELECT u FROM CsnUser\Entity\UserDoctrineEntity u"; // Works
//-		$dql = "SELECT u FROM CsnUser\Entity\UserDoctrineEntityWorking u"; // Works
//-		$dql = "SELECT u FROM CsnUser\Entity\UserDoctrineEntityMin u"; // If the class is not presented [Semantical Error] line 0, col 14 near 'CsnUser\Entity\UserDoctrineEntityMin': Error: Class 'CsnUser\Entity\UserDoctrineEntityMin' is not defined.

//-		Entity Repositories
//- 	Every Entity uses a default repository by default and offers a bunch of convenience methods that you can use to query for instances of that Entity.
//-		$users = $entityManager->getRepository('Bug')->getRecentBugs();
	
//-		$dql = "SELECT u FROM UserDoctrineEntityMin u";
		
//-		$dql = "SELECT u FROM Fmi\Entity\User u";
//-		$query = $entityManager->createQuery($dql);
//-		$query->setMaxResults(30);
//-		$users = $query->getResult();
		
		// The last step
//-		$users = $entityManager->getRepository('CsnUser\Entity\User')->findBy(array());
//-		$user = $entityManager->getRepository('CsnUser\Entity\User')->findOneBy(array('usrConfirmationToken' => $token));
		$users = $entityManager->getRepository('CsnUser\Entity\User')->findAll();
//-		$users = $entityManager->find('CsnUser\Entity\User', 2);

		return new ViewModel(array('rowset' => $users));
*/		
		// Paginator
		// Create the adapter
		// 1) variant 1 is to use SelectableAdapter
		// you can eventualy use parameters
		// Create the criteria
		// $expr     = DoctrineCriteria::expr()->eq('foo', 'bar');
		// $criteria = new DoctrineCriteria($expr);
		// Create the adapter
		// $adapter = new SelectableAdapter($objectRepository, $criteria);
		$adapter = new SelectableAdapter($entityManager->getRepository('CsnUser\Entity\User')); //($objectRepository); // An object repository implements Selectable
		// 2) ToDo make it work. Use the paginator that comes with DoctrineORMModule
		// $adapter = new DoctrinePaginator($entityManager->getRepository('CsnUser\Entity\User')); 

		// Create the paginator itself
		$paginator = new Paginator($adapter);
		$page = 1;
		if ($this->params()->fromRoute('page')) $page = $this->params()->fromRoute('page');
		$paginator->setCurrentPageNumber((int)$page)
				  ->setItemCountPerPage(5);		
		
		return new ViewModel(array('paginator' => $paginator));
	}
	
	// C -Create
	public function createAction()
	{
		$entityManager = $this->getEntityManager();
		$user = new User;
		$builder = new DoctrineAnnotationBuilder($entityManager);
		$form = $builder->createForm( $user );
		$form->setHydrator(new DoctrineHydrator($entityManager,'CsnUser\Entity\User'));
		
		$send = new Element('send');
		$send->setValue('Create'); // submit
		$send->setAttributes(array(
			'type'  => 'submit'
		));
		$form->add($send);
		
		$form->bind($user);
		
		$request = $this->getRequest();
        if ($request->isPost()) {
//			$form->setInputFilter(new UserFilter());
			$form->setData($request->getPost());
			 if ($form->isValid()) {
			 
				// ToDo replace this code with code that uses the $user object. The user has to save himself or use datamapper 
//				$data = $form->getData();
//				$hydrator = new ReflectionHydrator();
//				$data  = $hydrator->extract($data); // turn the object to array
//				unset($data['submit']); // Cannot use object of type CsnUser\Entity\UserEntity as array
//				if (empty($data['usr_registration_date'])) $data['usr_registration_date'] = '2013-07-19 12:00:00';				
//				$this->getUsersTable()->insert($data);
				
				$entityManager->persist($user);
				$entityManager->flush();
				
				return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));										
			}
		}		
		
		return new ViewModel(array('form' => $form));
	}
	
	// U -Update
	public function updateAction()
	{
		$id = $this->params()->fromRoute('id');
		if (!$id) return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));

		$entityManager = $this->getEntityManager();
		
        try {
			$repository = $entityManager->getRepository('CsnUser\Entity\User');
			$user = $repository->find($id);
			
        }
        catch (\Exception $ex) {
			echo $ex->getMessage(); // this never will be seen fi you don't comment the redirect
			return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));	
        }

		$builder = new DoctrineAnnotationBuilder($entityManager);
		$form = $builder->createForm( $user );
		$form->setHydrator(new DoctrineHydrator($entityManager,'CsnUser\Entity\User'));
		$send = new Element('send');
		$send->setValue('Edit'); // submit
		$send->setAttributes(array(
			'type'  => 'submit'
		));
		$form->add($send);
		
		$form->bind($user);
		
		$request = $this->getRequest();
        if ($request->isPost()) {
			// $form->setInputFilter(new UserFilter());
			$form->setData($request->getPost());
			 if ($form->isValid()) {
				
				// ToDo raplace the code with something that uses user object
//				$data = $form->getData();
//				$hydrator = new ReflectionHydrator();
//				$data  = $hydrator->extract($data); // turn the object to array
//				unset($data['submit']);
//				if (empty($data['usr_registration_date'])) $data['usr_registration_date'] = '2013-07-19 12:00:00';
//				$this->getUsersTable()->update($data, array('usr_id' => $id));

				$entityManager->persist($user);
				$entityManager->flush();
				
				return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));
			}			 
		}

		return new ViewModel(array('form' => $form, 'id' => $id));		
	}
	
	// D -Delete
	public function deleteAction()
	{
		$id = $this->params()->fromRoute('id');
		if (!$id) return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));
		
		$entityManager = $this->getEntityManager();
		
        try {
			$repository = $entityManager->getRepository('CsnUser\Entity\User');
			$user = $repository->find($id);
			$entityManager->remove($user);
			$entityManager->flush();
        }
        catch (\Exception $ex) {
			$this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));	
        }
		
		return $this->redirect()->toRoute('csn_user/default', array('controller' => 'user-doctrine-paginator', 'action' => 'index'));										
	}
	
	public function getUsersTable()
	{
		if (!$this->usersTable) {
		// You can use the SM to composite the object
			$resultSetPrototype = new HydratingResultSet(new ReflectionHydrator(), new UserEntity());
//			$resultSetPrototype->setObjectPrototype(new UserEntity());	
//			$resultSetPrototype->setHydrator(new ReflectionHydrator());			
			$this->usersTable = new TableGateway(
				'users', 
				$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'),
				null, // new \Zend\Db\TableGateway\Feature\RowGatewayFeature('usr_id') // Zend\Db\RowGateway\RowGateway Object
				$resultSetPrototype
			);
		}
		return $this->usersTable;
	}
	
	public function getDoctrineConn()
	{
		if (!$this->conn) {
			$config = new \Doctrine\DBAL\Configuration();
			$connectionParams = array(
				'dbname' => 'fmi',
				'user' => 'root',
				'password' => 'password',
				'host' => 'localhost',
				'driver' => 'pdo_mysql',
			);
			// DriverManager returns an instance of Doctrine\DBAL\Connection which is a wrapper around the underlying driver connection (which is often a PDO instance).
			$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
			$this->conn = $conn;		
		}
		return $this->conn;
	}
	
	public function getEntityManager()
	{
		if (!$this->entityManager) {

			// Create a simple "default" Doctrine ORM configuration for Annotations
			// $paths = array("/path/to/entities-or-mapping-files");
			$paths = array(realpath(dirname(__FILE__).'/../Entity'));
			
			// If $devMode is true always use an ArrayCache (in-memory) and regenerate proxies on every request.
			// If $devMode is false, check for Caches in the order APC, Xcache, Memcache (127.0.0.1:11211), Redis (127.0.0.1:6379) unless $cache is passed as fourth argument.
			// If $devMode is false, set then proxy classes have to be explicitly created through the command line.
			// If third argument $proxyDir is not set, use the systems temporary directory.
			$isDevMode = true;

			// the connection configuration
			$dbParams = array(
				'driver'   => 'pdo_mysql',
				'user'     => 'root',
				'password' => 'password',
				'dbname'   => 'fmi',
			);

			// http://stackoverflow.com/questions/14851286/how-to-solve-class-xxx-is-not-a-valid-entity-or-mapped-super-class-error
			// $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode); // this causes Error
			$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, null, null, false);
			// or if you prefer yaml or XML
			//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
			//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);
			
			// Or if you prefer XML
			// $config = Setup::createXMLMetadataConfiguration($paths, $isDevMode);
			// $entityManager = EntityManager::create($dbParams, $config);
			
			// Or if you prefer YAML:
			// $config = Setup::createYAMLMetadataConfiguration($paths, $isDevMode);
			// $entityManager = EntityManager::create($dbParams, $config);
			
			// ToDo add chain 
			// http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/advanced-configuration.html
			
			// obtaining the entity manager from a factory method.
			$this->entityManager = EntityManager::create($dbParams, $config);

		}
		return $this->entityManager;
	}
	
	// We don't have to do that we have enough autoloaders already registered: Composer and Eventualy Zend
	// this could go to doctrine_autoloader.php or init_autoloader.php
	public function registerDocrineClassLoader()
	{
/*
		echo '__DIR__ = ' . __DIR__ . "<br />\n";
		// __DIR__ = C:\Documents and Settings\user\fmi\module\CsnUser\src\CsnUser\Controller

		echo 'realpath(__FILE__) = ' . realpath(__FILE__) . "<br />\n";
		// realpath(__FILE__) = C:\Documents and Settings\user\fmi\module\CsnUser\src\CsnUser\Controller\UserDoctrineController.php

		echo 'realpath(__DIR__) = ' . realpath(__DIR__) . "<br />\n";
		// realpath(__DIR__) = C:\Documents and Settings\user\fmi\module\CsnUser\src\CsnUser\Controller

		echo 'dirname(__DIR__) = ' . dirname(__DIR__) . "<br />\n";
		// dirname(__DIR__) = C:\Documents and Settings\user\fmi\module\CsnUser\src\CsnUser

		echo 'realpath(dirname(__FILE__)."/../../../../../vendor/doctrine/common/lib/Doctrine/Common"' . realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/common/lib/Doctrine/Common') . "<br />\n";
		// realpath(dirname(__FILE__)."/../../../../../vendor/doctrine/common/lib/Doctrine/Common"C:\Documents and Settings\user\fmi\vendor\doctrine\common\lib\Doctrine\Common
		require realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php');

//-		$pathToDoctrineCommon = realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/common/lib/Doctrine/Common/');
		$pathToDoctrineCommon = realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/common/lib/');
		echo "pathToDoctrineCommon = " . $pathToDoctrineCommon . "<br />\n";
		// pathToDoctrineCommon = C:\Documents and Settings\user\fmi\vendor\doctrine\common\lib\Doctrine\Common\ClassLoader.php
		// The autoloader works when we point  to this folder:
		// pathToDoctrineCommon = C:\Documents and Settings\user\fmi\vendor\doctrine\common\lib
*/		
		// Common loader
		$pathToDoctrineCommon = realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/common/lib/');
//-		$classLoaderCommon = new ClassLoader('Doctrine\Common', $pathToDoctrineCommon); // it works perfect like this
		$classLoaderCommon = new ClassLoader('Doctrine', $pathToDoctrineCommon); // it works perfect like this
		$classLoaderCommon->register(); // spl_autoload_register(array($this, 'loadClass')); gets registered	
//-		echo '$classLoaderCommon->canLoadClass("Doctrine\Common\EventManager") = ' . $classLoaderCommon->canLoadClass('Doctrine\Common\EventManager') . "<br />\n";
//-		print_r($classLoaderCommon->getClassLoader('Doctrine\Common\EventManager'));
		
		// DBAL loader
		$pathToDoctrineDbal = realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/dbal/lib/');
//-		$classLoaderDbal = new ClassLoader('Doctrine\DBAL', $pathToDoctrineDbal); // it works perfect like this
		$classLoaderDbal = new ClassLoader('Doctrine', $pathToDoctrineDbal); // it works perfect like this
		$classLoaderDbal->register(); // spl_autoload_register(array($this, 'loadClass')); gets registered
//-		echo '$classLoaderDbal->canLoadClass("Doctrine\DBAL\DriverManager") = ' . $classLoaderDbal->canLoadClass('Doctrine\DBAL\DriverManager') . "<br />\n";
//-		print_r($classLoaderDbal->getClassLoader('Doctrine\DBAL\DriverManager'));
		
		// ORM loader
		$pathToDoctrineOrm = realpath(dirname(__FILE__).'/../../../../../vendor/doctrine/orm/lib/');
//-		$classLoaderOrm = new ClassLoader('Doctrine\ORM', $pathToDoctrineOrm); // it works perfect like this
		$classLoaderOrm = new ClassLoader('Doctrine', $pathToDoctrineOrm); // it works perfect like this
		$classLoaderOrm->register(); // spl_autoload_register(array($this, 'loadClass')); gets registered
//-		echo '$classLoaderOrm->canLoadClass("Doctrine\ORM\EntityManager") = ' . $classLoaderOrm->canLoadClass('Doctrine\ORM\EntityManager') . "<br />\n";	
//-		print_r($classLoaderOrm->getClassLoader('Doctrine\ORM\EntityManager'));
		// Doctrine\Common\ClassLoader Object ( [fileExtension:protected] => .php [namespace:protected] => Doctrine\ORM [includePath:protected] => C:\Documents and Settings\user\fmi\vendor\doctrine\orm\lib [namespaceSeparator:protected] => \ )
	}
}
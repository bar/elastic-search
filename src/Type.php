<?php

namespace Cake\ElasticSearch;

use Cake\Core\App;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\ElasticSearch\Datasource\Connection;
use Cake\ElasticSearch\Query;
use Cake\Event\EventManager;
use Cake\Utility\Inflector;

class Type implements RepositoryInterface {

/**
 * Connection instance
 *
 * @var Cake\ElasticSearch\Connection
 */
	protected $_connection;

/**
 * The name of the Elastic Search type this class represents
 *
 * @var string
 */
	protected $_name;

/**
 * EventManager for this table.
 *
 * All model/behavior callbacks will be dispatched on this manager.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * The name of the class that represent a single document for this type
 *
 * @var string
 */
	protected $_documentClass;


	public function __construct(array $config = []) {
		if (!empty($config['connection'])) {
			$this->connection($config['connection']);
		}

		if (!empty($config['name'])) {
			$this->name($config['name']);
		}

		$this->_eventManager = new EventManager;
	}

/**
 * Get the event manager for this Table.
 *
 * @return Cake\Event\EventManager
 */
	public function getEventManager() {
		return $this->_eventManager;
	}

/**
 * Returns the connection instance or sets a new one
 *
 * @param Cake\ElasticSearch\Connection $conn the new connection instance
 * @return Cake\ElasticSearch\Connection
 */
	public function connection($conn = null) {
		if ($conn === null) {
			return $this->_connection;
		}
		return $this->_connection = $conn;
	}

/**
 * Returns the type name name or sets a new one
 *
 * @param string $name the new type name
 * @return string
 */
	public function name($name = null) {
		if ($name !== null) {
			$this->_name = $name;
		}

		if ($this->_name === null) {
			$name = namespaceSplit(get_class($this));
			$name = substr(end($name), 0, -4);
			if (empty($name)) {
				$name = '*';
			}
			$this->_name = Inflector::underscore($name);
		}

		return $this->_name;
	}

/**
 * Creates a new Query for this repository and applies some defaults based on the
 * type of search that was selected.
 *
 * ### Model.beforeFind event
 *
 * Each find() will trigger a `Model.beforeFind` event for all attached
 * listeners. Any listener can set a valid result set using $query
 *
 * @param string $type the type of query to perform
 * @param array $options An array that will be passed to Query::applyOptions
 * @return \Cake\ORM\Query
 */
	public function find($type = 'all', $options = []) {
		$query = $this->query();
		return $this->callFinder($type, $query, $options);
	}

/**
 * Returns the query as passed
 *
 * @param \Cake\ElasticSearch\Query $query
 * @param array $options
 * @return \Cake\ElasticSearch\Query
 */
	public function findAll(Query $query, array $options = []) {
		return $query;
	}

/**
 * Calls a finder method directly and applies it to the passed query,
 * if no query is passed a new one will be created and returned
 *
 * @param string $type name of the finder to be called
 * @param \Cake\ElasticSearch\Query $query The query object to apply the finder options to
 * @param array $args List of options to pass to the finder
 * @return \Cake\ElasticSearch\Query
 * @throws \BadMethodCallException
 */
	public function callFinder($type, Query $query, $options = []) {
		$query->applyOptions($options);
		$options = $query->getOptions();
		$finder = 'find' . ucfirst($type);
		if (method_exists($this, $finder)) {
			return $this->{$finder}($query, $options);
		}

		throw new \BadMethodCallException(
			sprintf('Unknown finder method "%s"', $type)
		);
	}

/**
 * @{inheritdoc}
 *
 * Any key present in the options array will be translated as a GET argument
 * when getting the documetn by its id. This is often useful whe you need to
 * specify the parent or routing.
 *
 * This method will not trigger the Model.beforeFind callback as it does not use
 * queries for the search, but a faster key lookup to the search index.
 *
 * @throws \Elastica\Exception\NotFoundException if no document exist with such id
 */
	public function get($primaryKey, $options = []) {
		$type = $this->connection()->getIndex()->getType($this->name());
		$result = $type->getDocument($primaryKey, $options);
		$class = $this->entityClass();
		$document = new $class($result->getData());
		$document->clean();
		$document->isNew(false);
		return $document;
	}

/**
 * Creates a new Query instance for this repository
 *
 * @return \Cake\ORM\Query
 */
	public function query() {
		return new Query($this);
	}

/**
 * Update all matching records.
 *
 * Sets the $fields to the provided values based on $conditions.
 * This method will *not* trigger beforeSave/afterSave events. If you need those
 * first load a collection of records and update them.
 *
 * @param array $fields A hash of field => new value.
 * @param array $conditions An array of conditions, similar to those used with find()
 * @return boolean Success Returns true if one or more rows are effected.
 */
	public function updateAll($fields, $conditions) { }

/**
 * Delete all matching records.
 *
 * Deletes all records matching the provided conditions.
 *
 * This method will *not* trigger beforeDelete/afterDelete events. If you
 * need those first load a collection of records and delete them.
 *
 * This method will *not* execute on associations `cascade` attribute. You should
 * use database foreign keys + ON CASCADE rules if you need cascading deletes combined
 * with this method.
 *
 * @param array $conditions An array of conditions, similar to those used with find()
 * @return boolean Success Returns true if one or more rows are effected.
 * @see RepositoryInterface::delete()
 */
	public function deleteAll($conditions) { }

/**
 * Returns true if there is any record in this repository matching the specified
 * conditions.
 *
 * @param array $conditions list of conditions to pass to the query
 * @return boolean
 */
	public function exists(array $conditions) { }

/**
 * Persists an entity based on the fields that are marked as dirty and
 * returns the same entity after a successful save or false in case
 * of any error.
 *
 * @param \Cake\Datasource\EntityInterface the entity to be saved
 * @param array $options
 * @return \Cake\Datasource\EntityInterface|boolean
 */
	public function save(EntityInterface $entity, array $options = []) { }

/**
 * Delete a single entity.
 *
 * Deletes an entity and possibly related associations from the database
 * based on the 'dependent' option used when defining the association.
 *
 * @param \Cake\Datasource\EntityInterface $entity The entity to remove.
 * @param array $options The options fo the delete.
 * @return boolean success
 */
	public function delete(EntityInterface $entity, array $options = []) { }

/**
 * Create a new entity + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $article = $this->Articles->newEntity($this->request->data()) { }
 * }}}
 *
 * The hydrated entity will correctly do an insert/update based
 * on the primary key data existing in the database when the entity
 * is saved. Until the entity is saved, it will be a detached record.
 *
 * @param array $data The data to build an entity with.
 * @param array $associations A whitelist of associations
 *   to hydrate. Defaults to all associations
 * @return Cake\Datasource\EntityInterface
 */
	public function newEntity(array $data = [], $associations = null) { }

/**
 * Create a list of entities + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $articles = $this->Articles->newEntities($this->request->data()) { }
 * }}}
 *
 * The hydrated entities can then be iterated and saved.
 *
 * @param array $data The data to build an entity with.
 * @param array $associations A whitelist of associations
 *   to hydrate. Defaults to all associations
 * @return array An array of hydrated records.
 */
	public function newEntities(array $data, $associations = null) {}

/**
 * Returns the class used to hydrate rows for this table or sets
 * a new one
 *
 * @param string $name the name of the class to use
 * @throws \RuntimeException when the entity class cannot be found
 * @return string
 */
	public function entityClass($name = null) {
		if ($name === null && !$this->_documentClass) {
			$default = '\Cake\ElasticSearch\Document';
			$self = get_called_class();
			$parts = explode('\\', $self);

			if ($self === __CLASS__ || count($parts) < 3) {
				return $this->_documentClass = $default;
			}

			$alias = Inflector::singularize(substr(array_pop($parts), 0, -4));
			$name = implode('\\', array_slice($parts, 0, -1)) . '\Document\\' . $alias;
			if (!class_exists($name)) {
				return $this->_documentClass = $default;
			}
		}

		if ($name !== null) {
			$class = App::classname($name, 'Model/Document');
			$this->_documentClass = $class;
		}

		if (!$this->_documentClass) {
			throw new \RuntimeException(sprintf('Missing document class "%s"', $class));
		}

		return $this->_documentClass;
	}
}

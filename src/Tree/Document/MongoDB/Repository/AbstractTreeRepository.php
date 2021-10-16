<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;
use Gedmo\Tree\TreeListener;

/**
 * Base document repository for MongoDB ODM tree repositories.
 */
abstract class AbstractTreeRepository extends DocumentRepository implements RepositoryInterface
{
    /**
     * Tree listener from the event manager
     *
     * @var TreeListener
     */
    protected $listener = null;

    /**
     * Repository utils
     *
     * @var RepositoryUtilsInterface
     */
    protected $repoUtils = null;

    /**
     * {@inheritdoc}
     *
     * @throws \Gedmo\Exception\InvalidMappingException if the configuration is invalid
     */
    public function __construct(DocumentManager $em, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($em, $uow, $class);
        $treeListener = null;
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    $treeListener = $listener;
                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (is_null($treeListener)) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ODM MongoDB tree listener');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: '.$treeListener->getStrategy($em, $class->name)->getName());
        }

        $this->repoUtils = new RepositoryUtils($this->dm, $this->getClassMetadata(), $this->listener, $this);
    }

    /**
     * Sets the repository utils instance.
     *
     * @return $this
     */
    public function setRepoUtils(RepositoryUtilsInterface $repoUtils)
    {
        $this->repoUtils = $repoUtils;

        return $this;
    }

    /**
     * Sets the repository utils instance.
     *
     * @return RepositoryUtilsInterface|null
     */
    public function getRepoUtils()
    {
        return $this->repoUtils;
    }

    /**
     * {@inheritdoc}
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->repoUtils->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    /**
     * {@inheritdoc}
     */
    public function buildTree(array $nodes, array $options = [])
    {
        return $this->repoUtils->buildTree($nodes, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setChildrenIndex($childrenIndex)
    {
        $this->repoUtils->setChildrenIndex($childrenIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenIndex()
    {
        return $this->repoUtils->getChildrenIndex();
    }

    /**
     * {@inheritdoc}
     */
    public function buildTreeArray(array $nodes)
    {
        return $this->repoUtils->buildTreeArray($nodes);
    }

    /**
     * Checks if the current repository is right for the currently used tree strategy.
     *
     * @return bool
     */
    abstract protected function validate();

    /**
     * Create a query builder to get all root nodes.
     *
     * @param string $sortByField Sort by field
     * @param string $direction   Sort direction ("asc" or "desc")
     *
     * @return Builder
     */
    abstract public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Create a Query instance to get all root nodes.
     *
     * @param string $sortByField Sort by field
     * @param string $direction   Sort direction ("asc" or "desc")
     *
     * @return Query
     */
    abstract public function getRootNodesQuery($sortByField = null, $direction = 'asc');

    /**
     * Create a query builder to get an array of nodes to be used for building a tree.
     *
     * @param object $node        Root node
     * @param bool   $direct      Flag indicating whether only direct children should be retrieved
     * @param array  $options     Options, see {@see RepositoryUtilsInterface::buildTree()} for supported keys
     * @param bool   $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return Builder
     */
    abstract public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Create a Query instance configured to get an array of nodes to be used for building a tree.
     *
     * @param object $node        Root node
     * @param bool   $direct      Flag indicating whether only direct children should be retrieved
     * @param array  $options     Options, see {@see RepositoryUtilsInterface::buildTree()} for supported keys
     * @param bool   $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return Query
     */
    abstract public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Create a query builder to get the list of children for the given node.
     *
     * @param object|null $node        The object to fetch children for; if null, all nodes will be retrieved
     * @param bool        $direct      Flag indicating whether only direct children should be retrieved
     * @param string|null $sortByField Field name to sort by
     * @param string      $direction   Sort direction : "ASC" or "DESC"
     * @param bool        $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return Builder
     */
    abstract public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Create a Query instance configured to get the list of children for the given node.
     *
     * @param object|null $node        The object to fetch children for; if null, all nodes will be retrieved
     * @param bool        $direct      Flag indicating whether only direct children should be retrieved
     * @param string|null $sortByField Field name to sort by
     * @param string      $direction   Sort direction : "ASC" or "DESC"
     * @param bool        $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return Query
     */
    abstract public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);
}

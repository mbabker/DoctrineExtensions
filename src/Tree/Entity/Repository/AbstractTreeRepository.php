<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\RepositoryInterface;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;
use Gedmo\Tree\TreeListener;

/**
 * Base entity repository for ORM tree repositories.
 */
abstract class AbstractTreeRepository extends EntityRepository implements RepositoryInterface
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
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
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
            throw new \Gedmo\Exception\InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: '.$treeListener->getStrategy($em, $class->name)->getName());
        }

        $this->repoUtils = new RepositoryUtils($this->_em, $this->getClassMetadata(), $this->listener, $this);
    }

    /**
     * Create a new query empty builder.
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder();
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
    public function childCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (is_object($node)) {
            if (!($node instanceof $meta->name)) {
                throw new InvalidArgumentException('Node is not related to this repository');
            }

            $wrapped = new EntityWrapper($node, $this->_em);

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException('Node is not managed by UnitOfWork');
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        // We need to remove the ORDER BY DQL part since some vendors could throw an error
        // in count queries
        $dqlParts = $qb->getDQLParts();

        // We need to check first if there's an ORDER BY DQL part, because resetDQLPart doesn't
        // check if its internal array has an "orderby" index
        if (isset($dqlParts['orderBy'])) {
            $qb->resetDQLPart('orderBy');
        }

        $aliases = $qb->getRootAliases();
        $alias = $aliases[0];

        $qb->select('COUNT('.$alias.')');

        return (int) $qb->getQuery()->getSingleScalarResult();
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
    public function buildTreeArray(array $nodes)
    {
        return $this->repoUtils->buildTreeArray($nodes);
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
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * @return QueryBuilder
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

<?php

namespace Gedmo\SoftDeleteable\Query\TreeWalker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\Exec\SingleTableDeleteUpdateExecutor;
use Doctrine\ORM\Query\SqlWalker;
use Gedmo\SoftDeleteable\Query\TreeWalker\Exec\MultiTableDeleteExecutor;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * This SqlWalker is needed when you need to use a DELETE DQL query.
 *
 * It will update the "deletedAt" field with the actual date instead
 * of actually deleting it.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableWalker extends SqlWalker
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var AbstractPlatform|null
     */
    protected $platform;

    /**
     * @var SoftDeleteableListener
     */
    protected $listener;

    protected $configuration;
    protected $alias;
    protected $deletedAtField;
    protected $meta;

    /**
     * {@inheritdoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);

        $this->conn = $this->getConnection();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->listener = $this->getSoftDeleteableListener();
        $this->extractComponents($queryComponents);
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
        switch (true) {
            case $AST instanceof DeleteStatement:
                $primaryClass = $this->getEntityManager()->getClassMetadata($AST->deleteClause->abstractSchemaName);

                return ($primaryClass->isInheritanceTypeJoined())
                    ? new MultiTableDeleteExecutor($AST, $this, $this->meta, $this->platform, $this->configuration)
                    : new SingleTableDeleteUpdateExecutor($AST, $this);
            default:
                throw new \Gedmo\Exception\UnexpectedValueException('SoftDeleteable walker should be used only on delete statement');
        }
    }

    /**
     * Change a DELETE statement to an UPDATE statement.
     *
     * @return string
     */
    public function walkDeleteClause(DeleteClause $deleteClause)
    {
        $em = $this->getEntityManager();
        $class = $em->getClassMetadata($deleteClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $this->setSQLTableAlias($tableName, $tableName, $deleteClause->aliasIdentificationVariable);
        $quotedTableName = $class->getQuotedTableName($this->platform);
        $quotedColumnName = $class->getQuotedColumnName($this->deletedAtField, $this->platform);

        $sql = 'UPDATE '.$quotedTableName.' SET '.$quotedColumnName.' = '.$this->platform->getCurrentTimestampSQL();

        return $sql;
    }

    /**
     * Get the currently used SoftDeleteableListener
     *
     * @return SoftDeleteableListener
     *
     * @throws \Gedmo\Exception\RuntimeException if the listener is not registered
     */
    private function getSoftDeleteableListener()
    {
        if (is_null($this->listener)) {
            $em = $this->getEntityManager();

            foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The SoftDeleteable listener could not be found.');
            }
        }

        return $this->listener;
    }

    /**
     * Search for components in the delete clause of a query.
     *
     * @return void
     */
    private function extractComponents(array $queryComponents)
    {
        $em = $this->getEntityManager();

        foreach ($queryComponents as $alias => $comp) {
            if (!isset($comp['metadata'])) {
                continue;
            }
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            if ($config && isset($config['softDeleteable']) && $config['softDeleteable']) {
                $this->configuration = $config;
                $this->deletedAtField = $config['fieldName'];
                $this->meta = $meta;
            }
        }
    }
}

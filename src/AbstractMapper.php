<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\Traits;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 */
abstract class AbstractMapper implements RepositoryInterface
{
    use Traits\CacheAwareTrait;
    use Traits\ConnectionAwareTrait;
    use Traits\EntityAwareTrait;
    use Traits\MapperTrait;
    use Traits\RepositoryTrait;
    use Traits\ValidatorAwareTrait;

    /**
     * Initialize mapper with proper values for mapped table.
     */
    abstract protected function initialize(): void;

    /**
     * AbstractMapper constructor.
     *
     * @param string $connectionName
     * @param ConnectionFactory $connectionFactory
     * @param ValidatorFactoryInterface|null $validatorFactory
     * @param ValidatorEntityFactory|null $validatorEntityFactory
     * @param array $mappers
     * @param CacheItemPoolInterface|null $cache
     * @param bool $enableCacheOnRead
     */
    public function __construct(
        string $connectionName,
        ConnectionFactory $connectionFactory,
        ValidatorFactoryInterface $validatorFactory = null,
        ValidatorEntityFactory $validatorEntityFactory = null,
        array $mappers = [],
        CacheItemPoolInterface $cache = null,
        bool $enableCacheOnRead = false
    ) {
        $this->setConnectionName($connectionName);
        $this->setConnectionFactory($connectionFactory);
        $this->setCache($cache);
        $this->setValidatorFactories($validatorFactory, $validatorEntityFactory);

        $this->addMappers($mappers);

        if ($enableCacheOnRead) {
            $this->enableCacheOnRead();
        }

        $this->initialize();
    }

    /**
     * @param callable $callback
     * @param Query\SelectBuilder $queryBuilder
     * @param string $key
     * @param int $start
     * @param int $end
     * @param int $batchSize
     * @return void
     * @throws Exception\OrmException
     *
     * @codeCoverageIgnore
     */
    public function apply(
        callable $callback,
        Query\SelectBuilder $queryBuilder,
        string $key,
        int $start = 0,
        int $end = -1,
        int $batchSize = 10000
    ): void {
        if (!in_array($key, $this->primaryKeys)) {
            throw new \UnexpectedValueException(__METHOD__ . ' | The key must be a primary key.');
        }

        $statement = $this->getConnection()->prepare(
            'SELECT MIN(' . $key . ') AS min_value, MAX(' . $key . ') AS max_value FROM ' . $this->getTable()
        );
        $statement->execute();

        $bounds = $statement->fetch(\PDO::FETCH_OBJ);

        $minIndex          = max($start, $bounds->min_value);
        $maxIndex          = $end < 0 ? $bounds->max_value : min($end, $bounds->max_value);
        $currentBatchIndex = $minIndex;

        while ($currentBatchIndex <= $maxIndex) {
            /** @var RepositoryInterface $this */
            $clonedQueryBuilder = clone Query\Factory::getBuilder(Query\Factory::TYPE_SELECT, $this);
            $clonedQueryBuilder
                ->addWhere($key, $currentBatchIndex, '>=')
                ->addWhere(
                    $key,
                    $currentBatchIndex + $batchSize,
                    '<'
                )
            ;

            $batch = $this->query($clonedQueryBuilder);

            foreach ($batch as $item) {
                call_user_func($callback, $item);
            }

            $currentBatchIndex += $batchSize;
        }
    }
}

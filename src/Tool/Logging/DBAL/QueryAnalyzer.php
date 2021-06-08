<?php

namespace Gedmo\Tool\Logging\DBAL;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class QueryAnalyzer implements SQLLogger
{
    /**
     * Used database platform
     *
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * Start time of currently executed query
     *
     * @var int
     */
    private $queryStartTime = null;

    /**
     * Total execution time of all queries
     *
     * @var int
     */
    private $totalExecutionTime = 0;

    /**
     * List of queries executed
     *
     * @var array
     */
    private $queries = [];

    /**
     * Query execution times indexed
     * in same order as queries
     *
     * @var array
     */
    private $queryExecutionTimes = [];

    /**
     * Initialize log listener with database
     * platform, which is needed for parameter
     * conversion
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->queryStartTime = microtime(true);
        $this->queries[] = $this->generateSql($sql, $params, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $ms = round(microtime(true) - $this->queryStartTime, 4) * 1000;
        $this->queryExecutionTimes[] = $ms;
        $this->totalExecutionTime += $ms;
    }

    /**
     * Clean all collected data
     *
     * @return QueryAnalyzer
     */
    public function cleanUp()
    {
        $this->queries = [];
        $this->queryExecutionTimes = [];
        $this->totalExecutionTime = 0;

        return $this;
    }

    /**
     * Dump the statistics of executed queries
     *
     * @param bool $dumpOnlySql
     *
     * @return string
     */
    public function getOutput($dumpOnlySql = false)
    {
        $output = '';
        if (!$dumpOnlySql) {
            $output .= 'Platform: '.$this->platform->getName().PHP_EOL;
            $output .= 'Executed queries: '.count($this->queries).', total time: '.$this->totalExecutionTime.' ms'.PHP_EOL;
        }
        foreach ($this->queries as $index => $sql) {
            if (!$dumpOnlySql) {
                $output .= 'Query('.($index + 1).') - '.$this->queryExecutionTimes[$index].' ms'.PHP_EOL;
            }
            $output .= $sql.';'.PHP_EOL;
        }

        return $output . PHP_EOL;
    }

    /**
     * Index of the slowest query executed
     *
     * @return int
     */
    public function getSlowestQueryIndex()
    {
        $index = 0;
        $slowest = 0;
        foreach ($this->queryExecutionTimes as $i => $time) {
            if ($time > $slowest) {
                $slowest = $time;
                $index = $i;
            }
        }

        return $index;
    }

    /**
     * Get total execution time of queries
     *
     * @return int
     */
    public function getTotalExecutionTime()
    {
        return $this->totalExecutionTime;
    }

    /**
     * Get all queries
     *
     * @return array
     */
    public function getExecutedQueries()
    {
        return $this->queries;
    }

    /**
     * Get number of executed queries
     *
     * @return int
     */
    public function getNumExecutedQueries()
    {
        return count($this->queries);
    }

    /**
     * Get all query execution times
     *
     * @return array
     */
    public function getExecutionTimes()
    {
        return $this->queryExecutionTimes;
    }

    /**
     * Create the SQL with mapped parameters
     *
     * @param string     $sql
     * @param array|null $params
     * @param array|null $types
     *
     * @return string
     */
    private function generateSql($sql, $params, $types)
    {
        if (null === $params || !count($params)) {
            return $sql;
        }
        $converted = $this->getConvertedParams($params, $types);
        if (is_int(key($params))) {
            $index = key($converted);
            $sql = preg_replace_callback('@\?@sm', function ($match) use (&$index, $converted) {
                return $converted[$index++];
            }, $sql);
        } else {
            foreach ($converted as $key => $value) {
                $sql = str_replace(':'.$key, $value, $sql);
            }
        }

        return $sql;
    }

    /**
     * Get the converted parameter list
     *
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    private function getConvertedParams($params, $types)
    {
        $result = [];
        foreach ($params as $position => $value) {
            if (isset($types[$position])) {
                $type = $types[$position];
                if (is_string($type)) {
                    $type = Type::getType($type);
                }
                if ($type instanceof Type) {
                    $value = $type->convertToDatabaseValue($value, $this->platform);
                }
            } elseif (is_object($value) && ($value instanceof \DateTime || $value instanceof \DateTimeInterface)) {
                $value = $value->format($this->platform->getDateTimeFormatString());
            } elseif (!is_null($value)) {
                $type = Type::getType(gettype($value));
                $value = $type->convertToDatabaseValue($value, $this->platform);
            }
            if (is_string($value)) {
                $value = "'{$value}'";
            } elseif (is_null($value)) {
                $value = 'NULL';
            }
            $result[$position] = $value;
        }

        return $result;
    }
}

<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Table;

use Fly\Db\Adapter\Adapter;
use Fly\Db\Row\RowLoader;
use Fly\Db\Sql\TableIdentifier;

class Table extends AbstractTable
{

    /**
     * Constructor
     *
     * @param string|TableIdentifier|array $options
     */
    public function __construct($options)
    {
        if (!is_array($options)) {
            $options = array('table' => $options);
        }

        foreach (array('table', 'adapter', 'rowPrototype') as $opt) {
            $this->{"setup" . ucfirst($opt)}(isset($options[$opt]) ? $options[$opt] : null);
        }

        $this->initialize();
    }

    protected function setupTable($table)
    {
        if (!$table && !($table = $this->getTable())) {
            throw new Exception\RuntimeException('Table must be setup');
        }
        $this->setTable($table instanceof TableIdentifier ? $table : new TableIdentifier($table));
    }

    protected function setupAdapter($adapter)
    {
        if (!$adapter && !($adapter = $this->getAdapter())
            && !($adapter = Adapter::getDefaultAdapter())
        ) {
            throw new Exception\RuntimeException('Adapter must be setup');
        }
        $this->setAdapter($adapter);
    }

    protected function setupRowPrototype($rowPrototype)
    {
        $this->rowPrototype = $rowPrototype ?: self::getRowPrototype($this->getTable()->getTable());
    }

    /** @var callable|array|RowLoader */
    protected static $rowLoader;
    public static function setRowLoader($loader)
    {
        self::$rowLoader = $loader;
    }

    protected static function getRowPrototype($table)
    {
        if (!self::$rowLoader) {
            return null;
        }
        if (is_callable(self::$rowLoader)) {
            $factory = self::$rowLoader;
            try {
                $instance = $factory();
            } catch (\Exception $e) {
                throw new Exception\RuntimeException(
                    'An exception was raised while creating rowLoader', $e->getCode(), $e
                );
            }
            self::$rowLoader = $instance;
        } elseif (is_array(self::$rowLoader)) {
            self::$rowLoader = new RowLoader(self::$rowLoader);
        }
        if (self::$rowLoader instanceof RowLoader) {
            return self::$rowLoader->get($table);
        }
        return null;
    }
}

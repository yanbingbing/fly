<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Table;

use Fly\Db\Adapter\Adapter;
use Fly\Db\Sql\TableIdentifier;
use Fly\MountManager\MountManager;

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
        if (!$rowPrototype && ($loader = MountManager::getInstance()->get('RowLoader'))) {
            $rowPrototype = $loader->get($this->getTable()->getTable());
        }
        $this->rowPrototype = $rowPrototype;
    }
}

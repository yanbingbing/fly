<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Auth\Adapter;

use ArrayObject;
use Fly\Auth\Result as AuthResult;
use Fly\Db\Adapter\Adapter as DbAdapter;
use Fly\Db\Sql;
use Fly\Db\Sql\Expression as Expr;
use Fly\Db\Sql\Predicate\Operator as Op;

class DbTable implements AdapterInterface
{

    /**
     * Database Connection
     *
     * @var DbAdapter
     */
    protected $dbAdapter;

    /**
     * The table name to check
     *
     * @var string|array|Sql\TableIdentifier
     */
    protected $table = null;

    /**
     * The column to use as the identity
     *
     * @var string
     */
    protected $identityColumn = null;

    /**
     * The real column of the identity
     *
     * @var string
     */
    protected $realIdentityColumn = null;

    /**
     * Columns to be used as the credentials
     *
     * @var string
     */
    protected $credentialColumn = null;

    /**
     * Treatment applied to the credential, such as MD5() or PASSWORD()
     *
     * @var string
     */
    protected $credentialTreatment = null;

    /**
     * Results of database authentication query
     *
     * @var array
     */
    protected $resultRow = null;

    /**
     * @var mixed
     */
    protected $credential;

    /**
     * @var mixed
     */
    protected $identity;

    /**
     * Sets configuration options
     *
     * @param DbAdapter $dbAdapter
     * @param string|array|Sql\TableIdentifier $table Optional
     * @param string $identityColumn Optional
     * @param string $credentialColumn Optional
     * @param string $credentialTreatment Optional
     */
    public function __construct(DbAdapter $dbAdapter, $table = null, $identityColumn = null,
                                $credentialColumn = null, $credentialTreatment = null)
    {
        $this->dbAdapter = $dbAdapter;

        if (null !== $table) {
            $this->setTable($table);
        }

        if (null !== $identityColumn) {
            $this->setIdentityColumn($identityColumn);
        }

        if (null !== $credentialColumn) {
            $this->setCredentialColumn($credentialColumn);
        }

        if (null !== $credentialTreatment) {
            $this->setCredentialTreatment($credentialTreatment);
        }
    }

    /**
     * Returns the credential of the account being authenticated, or
     * NULL if none is set.
     *
     * @return mixed
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * Sets the credential for binding
     *
     * @param  mixed $credential
     * @return $this
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;

        return $this;
    }

    /**
     * Returns the identity of the account being authenticated, or
     * NULL if none is set.
     *
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Sets the identity for binding
     *
     * @param  mixed $identity
     * @return $this
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * Set the table name to be used in the select query
     *
     * @param  string|array|Sql\TableIdentifier $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the column name to be used as the identity column
     *
     * @param  string $identityColumn
     * @return $this
     */
    public function setIdentityColumn($identityColumn)
    {
        $this->identityColumn = $identityColumn;
        return $this;
    }

    /**
     * @param  string $identityColumn
     * @return $this
     */
    public function setRealIdentityColumn($identityColumn)
    {
        $this->realIdentityColumn = $identityColumn;
        return $this;
    }

    /**
     * Set the column name to be used as the credential column
     *
     * @param  string $credentialColumn
     * @return $this
     */
    public function setCredentialColumn($credentialColumn)
    {
        $this->credentialColumn = $credentialColumn;
        return $this;
    }

    /**
     * setCredentialTreatment() - allows the developer to pass a parametrized string that is
     * used to transform or treat the input credential data.
     *
     * In many cases, passwords and other sensitive data are encrypted, hashed, encoded,
     * obscured, or otherwise treated through some function or algorithm. By specifying a
     * parametrized treatment string with this method, a developer may apply arbitrary SQL
     * upon input credential data.
     *
     * Examples:
     *
     *  'PASSWORD(?)'
     *  'MD5(?)'
     *
     * @param  string $treatment
     * @return $this
     */
    public function setCredentialTreatment($treatment)
    {
        $this->credentialTreatment = $treatment;
        return $this;
    }

    /**
     * Returns the result row
     *
     * @return ArrayObject|bool
     */
    public function getResultRow()
    {
        if (!$this->resultRow) {
            return false;
        }
        return new ArrayObject($this->resultRow);
    }

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to a database table and
     * attempt to find a record matching the provided identity.
     *
     * @throws Exception\RuntimeException if answering the authentication query is impossible
     * @return AuthResult
     */
    public function authenticate()
    {
        $exception = null;

        if (empty($this->table)) {
            $exception = 'A table must be supplied for the DbTable authentication adapter.';
        } elseif ($this->identityColumn == '') {
            $exception = 'An identity column must be supplied for the DbTable authentication adapter.';
        } elseif ($this->credentialColumn == '') {
            $exception = 'A credential column must be supplied for the DbTable authentication adapter.';
        }

        if (null !== $exception) {
            throw new Exception\RuntimeException($exception);
        }

        if ($this->identity == '') {
            return new AuthResult(AuthResult::FAILURE_IDENTITY_EMPTY, 0);
        } elseif ($this->credential === null) {
            return new AuthResult(AuthResult::FAILURE_CREDENTIAL_EMPTY, 0);
        }

        $code = AuthResult::FAILURE;

        // build credential expression
        if (empty($this->credentialTreatment) || (strpos($this->credentialTreatment, '?') === false)) {
            $this->credentialTreatment = '?';
        }

        $credentialExpression = new Expr(
            '(CASE WHEN ?' . ' = ' . $this->credentialTreatment . ' THEN 1 ELSE 0 END) AS ?',
            array($this->credentialColumn, $this->credential, 'top_auth_credential_match'),
            array(Expr::TYPE_IDENTIFIER, Expr::TYPE_VALUE, Expr::TYPE_IDENTIFIER)
        );

        // get select
        $dbSelect = new Sql\Select();
        $dbSelect->from($this->table)
            ->columns(array('*', $credentialExpression))
            ->where(new Op($this->identityColumn, $this->identity, Op::OP_EQ));

        $sql = new Sql\Sql($this->dbAdapter);
        $statement = $sql->prepareStatement($dbSelect);
        try {
            $result = $statement->execute();
            $resultIdentities = array();
            // iterate result, most cross platform way
            foreach ($result as $row) {
                $resultIdentities[] = $row;
            }
        } catch (\Exception $e) {
            throw new Exception\RuntimeException(
                'The supplied parameters to DbTable failed to '
                . 'produce a valid sql statement, please check table and column names '
                . 'for validity.', 0, $e
            );
        }

        if (count($resultIdentities) < 1) {
            $code = AuthResult::FAILURE_IDENTITY_NOT_FOUND;
            return new AuthResult($code, 0);
        } elseif (count($resultIdentities) > 1) {
            $code = AuthResult::FAILURE_IDENTITY_AMBIGUOUS;
            return new AuthResult($code, 0);
        }

        // Loop, check and break on success.
        foreach ($resultIdentities as $resultIdentity) {
            if ($resultIdentity['top_auth_credential_match'] == '1') {
                unset($resultIdentity['top_auth_credential_match']);
                $this->resultRow = $resultIdentity;
                $code = AuthResult::SUCCESS;
            } else {
                $code = AuthResult::FAILURE_CREDENTIAL_INVALID;
            }
        }
        $identity = 0;
        if ($this->resultRow) {
            if ($this->realIdentityColumn && !isset($this->resultRow[$this->realIdentityColumn])) {
                throw new Exception\RuntimeException(sprintf(
                    'Column "%s" not found in result row', $this->realIdentityColumn));
            }
            $identity = (!$this->realIdentityColumn || $this->realIdentityColumn == $this->identity)
                ? $this->identity : $this->resultRow[$this->realIdentityColumn];
        }

        return new AuthResult($code, $identity);
    }
}

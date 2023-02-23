<?php

require_once(__DIR__ . '/fwConfigs.php');

class fwPDO
{
    private $user;
    private $password;
    private $dbName;
    private $connection;
    
    public function __construct($user, $password, $dbName)
    {
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;

        try
        {
            $dsn = fwConfigs::get('DBType') .
                 ':dbname=' . $this->dbName .
                 ';host=' . fwConfigs::get('DBHost');

            $this->connection = new PDO($dsn, $this->user, $this->password);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    private function _getDbConnection()
    {
        return $this->connection;
    }

    public function execute($query, $params = [])
    {
        $dbConnection = $this->_getDbConnection();
        $statement = $dbConnection->prepare($query);
        return $statement->execute($params);
    }

    public function query($query, $params = [])
    {
        $dbConnection = $this->_getDbConnection();
        $statement = $dbConnection->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function beginTransaction()
    {
        $dbConnection = $this->_getDbConnection();
        $dbConnection->beginTransaction();
    }

    public function commitTransaction()
    {
        $dbConnection = $this->_getDbConnection();
        $dbConnection->commit();
    }
}

?>

<?php

namespace App\Util;

use App\Util\Config;

/**
 * @codeCoverageIgnore
 * 
 * We want each Mysql connection/resource to be unique per request, otherwise
 * transactions may become intertwined. Do not use SharedObject with Mysql.
 * In the future, if we need to conserve resources, we can have non-transaction
 * queries go to a shared resource, and transaction queries go to individual
 * resources.
 */
class Mysql
{
    private static $mysqli;
    private $host;
    private $user;
    private $pass;
    private $name;
    private $port;
    private $sslEnabled;
    private $optsFile;
    private $logger;
    private $totalQueries;
    private $totalQueryTime;
    private $logEnabled;

    // need a separate opts file for mysqldump due to a mysql bug
    // https://bugs.mysql.com/bug.php?id=18209
    private $optsDumpFile;

    public function __construct(Config $config, Logger $logger)
    {
        $this->host = $config->get('dbHost');
        $this->user = $config->get('dbUser');
        $this->pass = $config->get('dbPass');
        $this->name = $config->get('dbName');
        $this->port = $config->get('dbPort', 3306);
        $this->sslEnabled = $config->get('dbSslEnabled', false);
        $this->optsFile = __DIR__ . "/../../mysql-opts";
        $this->optsDumpFile = __DIR__ . "/../../mysqldump-opts";
        $this->logger = $logger;
        $this->totalQueries = 0;
        $this->totalQueryTime = 0;
        $this->logEnabled = true;
    }
    
    public function backupDb($perMinute = false)
    {
        if (file_exists($this->optsFile) || file_exists($this->optsDumpFile)) {
          $this->logger->log("Skipping backup, optsFile exists");
          return;
        }
      $this->generateMysqlOptsFile();
        $mysqlDumpParams = "--defaults-file={$this->optsDumpFile} {$this->name} " .
                "--single-transaction --routines --triggers --events";

        //echo "Creating compressed DB backup.\n";

        if ($perMinute) {
          $backupFile = __DIR__ . "/../../sql/backups/backup-hour-" . date("H") . "-minute-" . date("i") . ".sql";
        } else {
          $backupFile = __DIR__ . "/../../sql/backups/backup-" . date("Y-m-d-H-i_s") . ".sql";
        }

        $this->run("mysqldump $mysqlDumpParams | gzip > $backupFile.gz", true);
        $this->run("ls -l $backupFile.gz", true);

        // Sanity check
        if (filesize($backupFile . '.gz') < 1) {
            throw new \Exception("Failed to backup (file too small? empty backup?)");
        }
        unlink($this->optsFile);
                    unlink($this->optsDumpFile);
        
        return "$backupFile.gz";
    }
    
    // Allow logging to be enabled/disabled for this class only.
    public function logEnabled($val)
    {
        $this->logEnabled = boolval($val);
    }

    /**
     * Perform a prepared query.
     *
     * @param string $query
     * @param string $types
     * @param array $params
     * @return mixed Returns an array if SELECT, or int of affected rows otherwise.
     * @throws \Exception
     */
    public function query($query, $types = '', $params = [])
    {
        if ($this->logEnabled) {
            $this->logger->log('Executing query', Logger::DEBUG, [
                'query' => $query,
                'types' => $types,
                'params' => $params,
                "mysqli_object_hash" => spl_object_hash($this->getMysqli())
            ]);
        }
        
        $startTime = microtime(true);

        $stmt = $this->getMysqli()->prepare($query);

        if (!$stmt) {
            throw new \Exception($this->getMysqli()->error);
        }

        if (!empty($params)) {
            $refs = [];

            foreach ($params as $key => $param) {
                $refs[$key] = &$params[$key];
            }

            $bind = array_merge([$types], $refs);
            call_user_func_array([$stmt, 'bind_param'], $bind);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        if (!$result && $stmt->errno) {
            throw new \Exception('Mysql error: ' . $stmt->error);
        }

        if (!$result) {
            
            if ($this->logEnabled) {
                $this->logger->log('Affected rows', Logger::DEBUG, [
                    'rows' => $stmt->affected_rows,
                ]);
            }
        
            return $stmt->affected_rows;
        }

        $rows = [];

        while ($myrow = $result->fetch_assoc()) {
            $rows[] = $myrow;
        }

        $stmt->close();
        
//        usleep(1);
        
        $seconds = sprintf("%.4f", microtime(true) - $startTime);
        $this->totalQueries++;
        $this->totalQueryTime += $seconds;
        
        if ($this->logEnabled) {
            $this->log('Returned rows', Logger::DEBUG, [
                'rows' => count($rows),
                'queryTimeSeconds' => $seconds,
                'cumulativeQueryTimeSeconds' => $this->totalQueryTime,
                'cumulativeQueryCount' => $this->totalQueries,
            ]);
        }

        return $rows;
    }
    
    private function log($msg, $logLevel = Logger::DEBUG, $data = [])
    {
        $data = array_merge($data, [
            "mysqli_object_hash" => spl_object_hash($this->getMysqli())
        ]);
        
        $this->logger->log($msg, $logLevel, $data);
    }
    
    public function startTransaction()
    {
        $this->log('Starting transaction');
        $this->getMysqli()->autocommit(false);
        $this->getMysqli()->begin_transaction();
    }
    
    public function commit()
    {
        $this->log('Committing transaction');
        $this->getMysqli()->commit();
        $this->getMysqli()->autocommit(true);
    }
    
    public function rollback()
    {
        $this->log('Rolling back transaction');
        $this->getMysqli()->rollback();
        $this->getMysqli()->autocommit(true);
    }

    public function upgradeDb()
    {
        try {
            $this->generateMysqlOptsFile();
            while ($this->upgradeCurrentHash());
        } finally {
            unlink($this->optsFile);
            unlink($this->optsDumpFile);
        }
        echo "--------------------------------------\n";
        echo "All done! Database upgrade successful.\n";
        echo "--------------------------------------\n";
    }

    public function generateMysqlOptsFile()
    {
        if (file_exists($this->optsFile)) {
            unlink($this->optsFile);
        }

        file_put_contents($this->optsFile, "");

        $this->run("chmod 600 {$this->optsFile}", true);

        if (file_exists($this->optsDumpFile)) {
            unlink($this->optsDumpFile);
        }

        file_put_contents($this->optsDumpFile, "");

        $this->run("chmod 600 {$this->optsDumpFile}", true);

        $opts = [
            "[client]",
            "user=\"{$this->user}\"",
            "password=\"{$this->pass}\"",
            "host=\"{$this->host}\"",
            "port=\"{$this->port}\"",
        ];

        file_put_contents($this->optsDumpFile, implode(PHP_EOL, $opts));

        $opts[] = "database=\"{$this->name}\"";

        file_put_contents($this->optsFile, implode(PHP_EOL, $opts));
    }

    public function getLastId()
    {
        return $this->getMysqli()->insert_id;
    }

    /**
     * 
     * @return \mysqli
     */
    private function getMysqli()
    {
        if (!isset(self::$mysqli)) {

			if ($this->sslEnabled) {
				$options = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
			} else {
				$options = null;
			}

		self::$mysqli = new \mysqli($this->host, $this->user, $this->pass, $this->name, $this->port, $options);
		//self::$mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
		if ($this->sslEnabled) {
			self::$mysqli->ssl_set('/etc/mysql/certs/client-key.pem', '/etc/mysql/certs/client-cert.pem', '/etc/mysql/certs/ca-cert.pem', NULL, NULL);
		}
            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
            }
        }
        return self::$mysqli;
    }

    private function upgradeCurrentHash()
    {
        $hash = $this->getDbHash();
        echo "Current DB hash is $hash\n";

        $mysqlParams = "--defaults-file={$this->optsFile}";
        $mysqlDumpParams = "--defaults-file={$this->optsDumpFile} {$this->name} " .
                "--single-transaction --routines --triggers --events";

        $upgradeFile = __DIR__ . "/../../sql/upgrade/$hash.sql";

        echo "Looking for " . basename($upgradeFile) . " ... ";

        if (file_exists($upgradeFile)) {
            echo "Upgrade found. Creating compressed DB backup.\n";
            $backupFile = __DIR__ . "/../../sql/backups/backup-" . date("Y-m-d-H-i-s") . ".sql";
            $this->run("mysqldump $mysqlDumpParams | gzip > $backupFile.gz", true);
            $this->run("ls -l $backupFile.gz", true);

            // Sanity check
            if (filesize($backupFile . '.gz') < 1) {
                throw new \Exception("Failed to backup (file too small? empty backup?)");
            }

            echo "Applying schema changes.\n";
            $this->run("cat $upgradeFile | mysql $mysqlParams", true);
            $newHash = $this->getDbHash();
            echo "New DB hash is $newHash\n";

            if ($newHash == $hash) {
                throw new \Exception("No changes, empty upgrade script?");
            }

            return true;
        } else {
            echo "No more changes\n";
        }

        return false;
    }

    private function run($cmd, $silent = false)
    {
        if (!$silent) {
            echo "Running: $cmd\n";
        }

        exec($cmd, $output, $ret);

        if (!$silent) {
            echo "Output: \n" . implode("\n", $output) . "\n";
            echo "Return value: " . $ret . "\n";
        }

        if ($ret !== 0) {
            throw new \Exception("Command returned non-zero value, exiting");
        }
    }

    private function getDbHash()
    {
        $rows = $this->query('show tables');

        $tableNames = [];
        foreach ($rows as $table) {
            foreach ($table as $tableName) {
                $tableNames[] = $tableName;
            }
        }

        sort($tableNames);

        $tableCreateStatements = [];

        foreach ($tableNames as $tableName) {
		$output = $this->query("show create table $tableName");
		if (!isset($output[0]['Create Table'])) continue;
            $str = $output[0]['Create Table'];
            $str = preg_replace("/AUTO_INCREMENT=\d+[\s]+/", "", $str);
            $tableCreateStatements[$tableName] = $str;
        }
        //echo var_export($tableCreateStatements, true) . PHP_EOL;
        $hash = md5(serialize($tableCreateStatements));

        return $hash;
    }
}

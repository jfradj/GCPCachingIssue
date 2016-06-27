<?php
/**
 * Wrapper around the \Google_DatastoreService class.
 */

final class DatastoreService {

    private static $instance = null;

    private static $required_options = [
        'dataset-id',
        'application-id',
    ];

    static $scopes = [
        "https://www.googleapis.com/auth/datastore",
        "https://www.googleapis.com/auth/userinfo.email",
    ];

    /**
     * @var \Google_Service_Datastore_Resource_Projects
     */
    private $dataset;

    /**
     * @var string
     */
    private $dataset_id;

    private $config = [
    ];

    /** @var string */
    private $lastCursor;

    /**
     * @return DatastoreService The instance of the service.
     * @throws \UnexpectedValueException
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            throw new \UnexpectedValueException('Instance has not been set.');
        }
        return self::$instance;
    }

    public static function setInstance($instance)
    {
        if (self::$instance != null) {
            throw new \UnexpectedValueException('Instance has already been set.');
        }
        self::$instance = $instance;
    }

    /**
     * @param $options - Array with values to configure the service. Options are:
     *   - client-id
     *   - client-secret
     *   - redirect-url
     *   - developer-key
     *   - application-id
     *   - service-account-name
     *   - private-key
     *   - namespace
     */
    public function __construct($options)
    {
        $this->config = array_merge($this->config, $options);
        $this->init($this->config);
    }

    /**
     * @return string
     */
    public function getCursor()
    {
        return $this->lastCursor;
    }

    /**
     * @param string $lastCursor
     */
    public function setCursor($lastCursor)
    {
        $this->lastCursor = $lastCursor;
    }

    /**
     * @param \Google_Service_Datastore_AllocateIdsRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_AllocateIdsResponse
     */
    public function allocateIds(\Google_Service_Datastore_AllocateIdsRequest $postBody, $optParams = [])
    {
        return $this->dataset->allocateIds($this->dataset_id, $postBody, $optParams);
    }

    /**
     * @param \Google_Service_Datastore_BeginTransactionRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_BeginTransactionResponse
     */
    public function beginTransaction(\Google_Service_Datastore_BeginTransactionRequest $postBody, $optParams = array())
    {
        return $this->dataset->beginTransaction($this->dataset_id, $postBody, $optParams);
    }

    public function getNewTransaction()
    {
        $transactionRequest = new \Google_Service_Datastore_BeginTransactionRequest();
        $res = DatastoreService::getInstance()->beginTransaction($transactionRequest);
        if (!$res || !$res->getTransaction()) {
            syslog(LOG_ERR, "cannot begin a transaction");
            throw new \RuntimeException("Cannot begin the transaction.");
        }
        return $res->getTransaction();
    }

    /**
     * @param \Google_Service_Datastore_CommitRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_CommitResponse
     */
    public function commit(\Google_Service_Datastore_CommitRequest $postBody, $optParams = [])
    {
        return $this->dataset->commit($this->dataset_id, $postBody, $optParams);
    }

    /**
     * @param \Google_Service_Datastore_LookupRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_LookupResponse
     */
    public function lookup(\Google_Service_Datastore_LookupRequest $postBody, $optParams = [])
    {
        return $this->dataset->lookup($this->dataset_id, $postBody, $optParams);
    }

    /**
     * @param \Google_Service_Datastore_RollbackRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_RollbackResponse
     */
    public function rollback(\Google_Service_Datastore_RollbackRequest $postBody, $optParams = [])
    {
        return $this->dataset->rollback($this->dataset_id, $postBody, $optParams);
    }

    /**
     * @param $transaction
     * @param array $optParams
     * @return \Google_Service_Datastore_RollbackResponse
     */
    public function rollbackForTransaction($transaction, $optParams = [])
    {
        $rollbackRequest = new \Google_Service_Datastore_RollbackRequest();
        $rollbackRequest->setTransaction($transaction);
        return $this->rollback($rollbackRequest, $optParams);
    }

    /**
     * @param $transaction
     * @param array $optParams
     * @return bool
     */
    public function silentRollbackForTransaction($transaction, $optParams = [])
    {
        try {
            if ($transaction) {
                $this->rollbackForTransaction($transaction, $optParams);
            }
            return true;
        }
        catch (\Exception $ex) {
            syslog(LOG_WARNING, "Rollback failled " . $ex);
            return false;
        }
    }

    /**
     * @param \Google_Service_Datastore_RunQueryRequest $postBody
     * @param array $optParams
     * @return \Google_Service_Datastore_RunQueryResponse
     */
    public function runQuery(\Google_Service_Datastore_RunQueryRequest $postBody, $optParams = [])
    {
        /** @var \Google_Service_Datastore_RunQueryResponse $response */
        $response = $this->dataset->runQuery($this->dataset_id, $postBody, $optParams);
        /** @var \Google_Service_Datastore_QueryResultBatch $batch */
        $batch = $response->getBatch();
        $this->lastCursor = $batch->getEndCursor();
        return $response;
    }

    /**
     * Key helper function, abstracts the namespace
     *
     * @return \Google_Service_Datastore_Key
     */
    public function createKey()
    {
        $key = new \Google_Service_Datastore_Key();

        if (isset($this->config['namespace'])) {
            $partition = new \Google_Service_Datastore_PartitionId();
            $partition->setNamespaceId($this->config['namespace']);
            $key->setPartitionId($partition);
        }

        return $key;
    }

    private function init($options)
    {
        foreach(self::$required_options as $required_option) {
            if (!array_key_exists($required_option, $options)) {
                throw new \InvalidArgumentException(
                    'Option ' . $required_option . ' must be supplied.');
            }
        }
        $client = new \Google_Client();
        $client->setApplicationName($options['application-id']);
        $client->setAuthConfig($options['auth-conf-path']);
        $client->setScopes(self::$scopes);
        $service = new \Google_Service_Datastore($client);

        $this->dataset = $service->projects;
        $this->dataset_id = $options['dataset-id'];
    }
}

<?php
namespace Vendimia\Database\Driver;

/**
 * Query result abstraction
 */
class Result
{
    public function __construct(
        private ConnectorInterface $connector,
        private $result,
    ) {

    }

    public function count()
    {
        return $this->connector->resultCount($this->result);
    }

    /**
     * @see ConnectorInterface::fetch()
     */
    public function fetch()
    {
        return $this->connector->fetch($this->result);
    }

    /**
     * @see ConnectorInterface::fetch()
     */
    public function free()
    {
        return $this->connector->free($this->result);
    }

}

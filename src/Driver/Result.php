<?php
namespace Vendimia\Database\Driver;

class Result
{
    public function __construct(
        private ConnectorInterface $connector,
        private $result,
    ) {

    }

    /**
     * @see ConnectorInterface::fetch()
     */
    public function fetch()
    {
        return $this->connector->fetch($this->result);
    }
}

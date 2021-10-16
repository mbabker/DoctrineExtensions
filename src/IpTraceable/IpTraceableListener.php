<?php

namespace Gedmo\IpTraceable;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\AbstractTrackingListener;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * The IpTraceable listener sets IP information
 * on objects when created and updated.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableListener extends AbstractTrackingListener
{
    /**
     * @var string|null
     */
    protected $ip;

    /**
     * Get the IP address to set on a field.
     *
     * @param ClassMetadata    $meta
     * @param string           $field
     * @param AdapterInterface $eventAdapter
     *
     * @return string|null
     */
    public function getFieldValue($meta, $field, $eventAdapter)
    {
        return $this->ip;
    }

    /**
     * Set the IP address to use with this listener.
     *
     * @param string $ip
     *
     * @throws InvalidArgumentException
     */
    public function setIpValue($ip = null)
    {
        if (isset($ip) && false === filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("ip address is not valid $ip");
        }

        $this->ip = $ip;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}

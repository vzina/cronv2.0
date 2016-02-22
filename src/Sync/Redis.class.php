<?php
/**
 * Redis.class.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/2/22
 * Time: 14:46
 */

namespace EasyCron\Sync;


class Redis
{
    /**
     * @var \Redis
     */
    protected $conn;

    protected $options = array(
        'persistent' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 3,
        'ttl' => 0,
    );

    protected $optionKeys = array(\Redis::OPT_SERIALIZER, \Redis::OPT_PREFIX);

    /**
     * Constructor
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = array())
    {
        if (!extension_loaded("redis"))
            throw new \Exception('extension redis is not exist!');
        $this->options = $options + $this->options;

        $this->conn = new \Redis();

        if (empty($this->options['persistent'])) {
            $this->conn->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        } else {
            $this->conn->pconnect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        foreach ($this->optionKeys as $key) {
            if (isset($this->options[$key])) {
                $this->conn->setOption($key, $this->options[$key]);
            }
        }
    }

    /**
     * Set cache
     *
     * @param mixed $id
     * @param mixed $data
     * @param int $ttl
     * @return bool
     */
    public function set($id, $data, $ttl = null)
    {
        if (null === $ttl) {
            $ttl = $this->options['ttl'];
        }

        if (empty($ttl)) {
            return $this->conn->set($id, $data);
        } else {
            return $this->conn->setex($id, $ttl, $data);
        }
    }

    /**
     * Get Cache Value
     *
     * @param mixed $id
     * @return mixed
     */
    public function get($id)
    {
        if (!is_array($id)) {
            return $this->conn->get($id);
        }
        return array_combine($id, $this->conn->mGet($id));
    }


    /**
     * Set cache
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function __set($key, $value)
    {
        return null === $value ? $this->delete($key) : $this->set($key, $value);
    }

    /**
     * Get cache
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Delete cache
     *
     * @param string $key
     * @return boolean
     */
    public function __unset($key)
    {
        return $this->delete($key);
    }

    /**
     * Magic method
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->conn, $method), $args);
    }
}
<?php
/**
 * Loader.php.
 * Author: yeweijian
 * E-mail: yeweijian@hoolai.com
 * Date: 2016/1/15
 * Time: 15:38
 */

namespace EasyCron;


final class Loader
{
    /**
     * 命名空间的路径
     * @var array
     */
    protected $namespacePath = [];

    /**
     * 映射类
     * @var array
     */
    protected $classMap = [];

    /**
     * 默认加载路径
     * @var string|array|null
     */
    protected $setIncludePath = null;

    /**
     * 文件扩展名
     * @var string
     */
    protected $fileExt = '.php';

    /**
     * 注册默认加载路径
     * $load->register('/usr/local/src')->handle();
     * $load->register(['/usr/local/src','/usr'])->handle();
     * @param $pathMap
     * @return $this
     */
    public function register($pathMap)
    {
        if (!empty($pathMap)) {
            $this->setIncludePath .= $pathMap . PATH_SEPARATOR;
        } else if (is_array($pathMap)) {
            $this->setIncludePath .= join(PATH_SEPARATOR, $pathMap) . PATH_SEPARATOR;
        }
        return $this;
    }

    /**
     * 注册命名空间映射
     * $load->registerNamespace(['Swoole'=>'/usr/local/src','App'=>'/usr'])->handle();
     * @param array $namespaceMap
     * @return $this
     */
    public function registerNamespace(array $namespaceMap)
    {
        $this->namespacePath = array_merge($this->namespacePath, $namespaceMap);
        return $this;
    }

    /**
     * 注册类映射
     * $load->registerClass(['Swoole'=>'/usr/local/src/Swoole.php','App'=>'/usr/app.php'])->handle();
     * @param array $classMap
     * @return $this
     */
    public function registerClass(array $classMap)
    {
        $this->classMap = array_merge($this->classMap, $classMap);
        return $this;
    }

    public function getClassMap()
    {
        return $this->classMap;
    }

    public function getRegisterNamespace()
    {
        return $this->namespacePath;
    }

    public function getRegisterPath()
    {
        return $this->setIncludePath;
    }

    public function setFileExt($value)
    {
        $this->fileExt = '.' . trim($value, '.');
        return $this;
    }

    public function getFileExt()
    {
        return $this->fileExt;
    }

    /**
     * 手动加载文件
     * @param $file
     * @return bool|mixed
     */
    public static function import($file)
    {
        return !is_file($file) ?: (include $file);
    }

    /**
     * Loader启动器
     */
    public function handle()
    {
        if (!is_null($this->setIncludePath)) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $this->setIncludePath);
        }

        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * 自动加载文件方法
     * @param $className
     */
    public function autoload($className)
    {
        /* 类名映射加载 */
        if (isset($this->classMap[$className])) {
            self::import($this->classMap[$className]);
            return;
        }

        /* 默认路径加载 */
        if (false === strpos($className, '\\')) {
            /* 兼容'_'作分割的命名 */
            if (false !== strpos($className, '_')) {
                include str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->fileExt;
            } else {
                include $className . $this->fileExt;
            }
            return;
        }

        /* 命名空间加载 */
        foreach ($this->namespacePath as $namespace => $path) {
            if (false !== strpos($className, $namespace)) {
                $filename = $path . str_replace('\\', DIRECTORY_SEPARATOR, str_replace($namespace, '', $className))
                    . $this->fileExt;
                if (self::import($filename)) {
                    return;
                }
            }
        }
    }
}
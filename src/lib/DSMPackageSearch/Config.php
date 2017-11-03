<?php

namespace DSMPackageSearch;

use \Symfony\Component\Yaml\Yaml;
use \Symfony\Component\Yaml\Exception\ParseException;

/**
 * Configuration class
 *
 * @property array $site Site properties
 * @property array $paths Different paths
 * @property array excludedSynoServices Synology services to exclude from package list
 * @property array sourceUrls Defaults for packages
 * @property string basePath Path to site root (where index.php is located)
 * @property string baseUrl URL to site root (where index.php is located)
 * @property string baseUrlRelative Relative URL to site root (without scheme or hostname)
 */
class Config implements \Iterator
{
    private $iterPos;
    private $basePath;
    private $cfgFile;
    private $config;

    public function __construct($basePath, $cfgFile = 'conf/source_urls.yaml')
    {
        $this->iterPos  = 0;
        $this->basePath = realpath($basePath);
        $this->cfgFile  = $this->basePath . DIRECTORY_SEPARATOR . $cfgFile;

        if (!file_exists($this->cfgFile)) {
            throw new \Exception('Config file "' . $this->cfgFile . '" not found!');
        }

        try {
            /** @var array $config */
            $config = Yaml::parse(file_get_contents($this->cfgFile));            
        } catch (ParseException $e) {
            throw new \Exception($e->getMessage());
        }

        $this->config = $config;
        $this->config['basePath'] = $this->basePath;
        $this->normalizePaths();
    }

    /**
     * Getter magic method.
     *
     * @param string $name Name of requested value.
     * @return mixed Requested value.
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * Setter magic method.
     *
     * @param string $name Name of variable to set.
     * @param mixed $value Value to set.
     */
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Isset feature magic method.
     *
     * @param string $name Name of requested value.
     * @return bool TRUE if value exists, FALSE otherwise.
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * Unset feature magic method.
     *
     * @param string $name Name of value to unset.
     */
    public function __unset($name)
    {
        unset($this->config[$name]);
    }

    public function rewind()
    {
        $this->iterPos = 0;
    }

    public function current()
    {
        return $this->config[array_keys($this->config)[$this->iterPos]];
    }

    public function key()
    {
        return array_keys($this->config)[$this->iterPos];
    }

    public function next()
    {
        $this->iterPos++;
    }

    public function valid()
    {
        return isset(array_keys($this->config)[$this->iterPos]);
    }

    private function normalizePaths()
    {
        if ($this->__isset('paths'))
        {
            foreach ($this->config['paths'] as $key=>$value)
            {
                $this->config['paths'][$key] = $this->basePath.DIRECTORY_SEPARATOR.$value;
            }
        }
    }

}

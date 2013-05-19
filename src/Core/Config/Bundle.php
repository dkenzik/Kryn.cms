<?php

namespace Core\Config;

use Admin\Utils;
use Core\Kryn;

class Bundle extends Model
{

    /**
     * @var Plugin[]
     */
    protected $plugins;

    /**
     * @var Theme[]
     */
    protected $themes;

    /**
     * @var Object[]
     */
    protected $objects;

    /**
     * @var EntryPoint[]
     */
    protected $entryPoints;

    /**
     * @var Asset[]
     */
    protected $adminAssets;

    /**
     * @var string
     */
    protected $bundleName;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $streams;

    /**
     * @var array
     */
    protected $instanceRelations = array();

    /**
     * @param \DOMElement $bundleName
     * @param \DOMElement $bundleDoc
     */
    public function __construct($bundleName, \DOMElement $bundleDoc = null)
    {
        $this->element = $bundleDoc;
        $this->bundleName = $bundleName;
    }

    /**
     * @param string $bundleName
     */
    public function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;
    }

    /**
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * @return \Core\Bundle
     */
    public function getBundleClass()
    {
        return Kryn::getBundle($this->getBundleName());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBundleClass()->getName(true);
    }

    /**
     * @param \DOMNode $node
     */
    public function import(\DOMNode $node)
    {
        if ('bundle' === $node->nodeName) {
            foreach ($node->childNodes as $child) {
                $this->import($child);
            }
        } else if ('#text' !== $node->nodeName) {
            $actualElement = $this->getDirectChild($node->nodeName);
            if ($actualElement) {
                //todo, element(section) already there, so merge both children
            } else {
                //not there yet, just append it
                $this->element->appendChild($this->element->ownerDocument->importNode($node, true));
            }
        }
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins()
    {
        if (null === $this->plugins) {
            $plugins = $this->element->getElementsByTagName('plugin');
            $this->plugins = array();
            foreach ($plugins as $plugin) {
                $this->plugins[] = $this->getModelInstance($plugin);
            }
        }

        return $this->plugins;
    }

    /**
     * @param string $id
     *
     * @return Plugin
     */
    public function getPlugin($id)
    {
        $this->plugins = $this->plugins ? : $this->getPlugins();
        foreach ($this->plugins as $plugin) {
            if ($plugin->getId() == $id) {
                return $plugin;
            }
        }
    }

    /**
     * @param string $streams
     */
    public function setStreams($streams)
    {
        $this->streams = $streams;
    }

    /**
     * @return string
     */
    public function getStreams()
    {
        if (null === $this->streams) {
            $childrenElement = $this->getDirectChild('streams');
            $this->streams = array();
            if ($childrenElement) {
                foreach ($childrenElement->childNodes as $child) {
                    if ('stream' === $child->nodeName) {
                        $this->streams[] = $this->getModelInstance($child);
                    }
                }
            }
        }

        return $this->streams;
    }


    /**
     * @param string $filter
     * @param bool   $regex
     *
     * @return Asset[]|Assets[]
     */
    public function getAdminAssets($filter = '', $regex = false)
    {
        if (null === $this->adminAssets) {
            $childrenElement = $this->getDirectChild('admin');
            $this->adminAssets = array();
            if ($childrenElement) {
                $children = $childrenElement->childNodes;
                foreach ($children as $child) {
                    if ('asset' === $child->nodeName || 'assets' == $child->nodeName) {
                        $this->adminAssets[] = $this->getModelInstance($child);
                    }
                }
            }
        }

        if ('' === $filter) {
            return $this->entryPoints;
        } else {
            $result = array();
            if ($regex) {
                $filter = addcslashes($filter, '/');
            } else {
                $filter = preg_quote($filter, '/');
            }

            foreach ($this->adminAssets as $asset) {
                if (preg_match('/' . $filter . '/', $asset->getPath())) {
                    $result[] = $asset;
                }
            }
            return $result;
        }
    }

    /**
     *
     * @param bool   $localPath   Return the real local accessible path or the defined.
     * @param string $filter      a filter value
     * @param bool   $regex       if you pass a own regex as $filter set this to true
     * @param bool   $compression if true or false it returns only assets with this compression value. null returns all
     *
     * @return string[]
     */
    public function getAdminAssetsPaths($localPath = false, $filter = '', $regex = false, $compression = null)
    {
        $files = array();
        $method = $localPath ? 'getLocalPath' : 'getPath';
        foreach ($this->getAdminAssets($filter, $regex) as $asset) {
            if ($asset instanceof Asset) {
                if (null !== $compression && $compression !== $asset->getCompression()) {
                    continue;
                }
                $files[] = $asset->$method();
            } else if ($asset instanceof Assets) {
                foreach ($asset as $subAsset) {
                    if (null !== $compression && $compression !== $subAsset->getCompression()) {
                        continue;
                    }
                    $files[] = $subAsset->$method();
                }
            }
        }
        return array_unique($files);
    }

    /**
     * @param string $id
     *
     * @return Theme
     */
    public function getTheme($id)
    {
        $this->themes = $this->themes ? : $this->getThemes();
        foreach ($this->themes as $theme) {
            if ($theme->getId() == $id) {
                return $theme;
            }
        }
    }

    /**
     * @param EntryPoint[] $entryPoints
     */
    public function setEntryPoints(array $entryPoints)
    {
        $this->entryPoints = $entryPoints;
    }

    /**
     * @return EntryPoint[]
     */
    public function getEntryPoints()
    {
        return $this->entryPoints;
    }

    public function getEntryPointsArray()
    {
        if (null !== $this->entryPoints) {
            $entryPoints = array();
            foreach ($this->entryPoints as $entryPoint) {
                $entryPoints[$entryPoint->getPath()] = $entryPoint->toArray();
            }
            return $entryPoints;
        }
    }

    public function getAllEntryPoints(EntryPoint $entryPoint = null)
    {
        $entryPoints = array();

        if ($entryPoint) {
            $subEntryPoints = $entryPoint->getChildren();
        } else {
            $subEntryPoints = $this->getEntryPoints();
        }

        if (null !== $subEntryPoints) {
            foreach ($subEntryPoints as $subEntryPoint) {
                $entryPoints[$this->getBundleName() . '/' . $subEntryPoint->getFullPath()] = $subEntryPoint;
                $entryPoints = array_merge(
                    $entryPoints,
                    $this->getAllEntryPoints($subEntryPoint)
                );
            }
        }

        return $entryPoints;
    }

    /**
     * @param \DOMNode $node
     * @param          $instance
     */
    public function setInstanceForNode(\DOMNode $node, $instance)
    {
        $this->instanceRelations[spl_object_hash($node)] = $instance;
    }

    /**
     * @param \DOMNode $node
     *
     * @return mixed
     */
    public function getInstanceForNode(\DOMNode $node)
    {
        return $this->instanceRelations[spl_object_hash($node)];
    }

    /**
     * @param \DOMNode $node
     *
     * @return mixed
     */
    public function getModelInstance(\DOMNode $node)
    {
        if ($instance = $this->getInstanceForNode($node)) {
            return $instance;
        }

        $blacklist = array('Config');

        $clazz = char2Camelcase($node->nodeName, '-');
        if (in_array($clazz, $blacklist)) {
            return;
        }
        $clazz = '\Core\Config\\' . $clazz;

        if (class_exists($clazz)) {
            $instance = new $clazz($node, $this);
            $this->setInstanceForNode($node, $instance);
            return $instance;
        }
    }

    /**
     * @param $path Full path, delimited with `/`;
     *
     * @return EntryPoint
     */
    public function getEntryPoint($path)
    {
        $first = (false === ($pos = strpos($path, '/'))) ? $path : substr($path, 0, $pos);

        if (null !== $this->entryPoints) {
            foreach ($this->entryPoints as $entryPoint) {
                if ($first == $entryPoint->getPath()) {
                    if (false !== strpos($path, '/')) {
                        return $entryPoint->getChild(substr($path, $pos + 1));
                    } else {
                        return $entryPoint;
                    }
                }
            }
        }
    }

    /**
     * @return Object[]
     */
    public function getObjects()
    {
        if (null === $this->objects) {
            $element = $this->getDirectChild('objects');
            $this->objects = array();
            if ($element) {
                foreach ($element->childNodes as $node) {
                    if ('object' === $node->nodeName) {
                        $this->objects[] = $this->getModelInstance($node);
                    }
                }
            }
        }

        return $this->objects;
    }

    public function getObjectsArray()
    {
        $objects = array();
        foreach ($this->getObjects() as $object) {
            $objects[strtolower($object->getId())] = $object->toArray();
        }
        return $objects;
    }

    /**
     * @param string $id
     *
     * @return Object
     */
    public function getObject($id)
    {
        $this->objects = $this->objects ? : $this->getObjects();
        foreach ($this->objects as $object) {
            if ($object->getId() == $id) {
                return $object;
            }
        }
    }

    /**
     * @return Theme[]
     */
    public function getThemes()
    {
        if (null === $this->themes) {
            $themes = $this->element->getElementsByTagName('theme');
            $this->themes = array();
            foreach ($themes as $theme) {
                if ('theme' === $theme->nodeName) {
                    $this->themes[] = $this->getModelInstance($theme);
                }
            }
        }

        return $this->themes;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }


}
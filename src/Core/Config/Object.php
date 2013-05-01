<?php

namespace Core\Config;

class Object extends Model
{
    /**
     * The id of the object.
     *
     * @var string
     */
    protected $id;

    /**
     * A label of the object.
     *
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $desc;

    /**
     * @var string
     */
    protected $table;

    /**
     * The class of the object interface. Needs 'dataModel'=true.
     *
     * @var string
     */
    protected $class;

    /**
     * Which field (the value of it) shall be used as default label for the object.
     *
     * @var string
     */
    protected $labelField;

    /**
     * @var string
     */
    protected $labelTemplate;

    /**
     * Comma separated list of fields which are selected per default.
     *
     * @var string
     */
    protected $defaultSelection;

    /**
     * Comma separated list of fields which are blacklisted and therefore not selectable
     * through the object API.
     *
     * @var string
     */
    protected $blacklistSelection;


    /**
     * The data model in the back.
     *
     * @var string
     */
    protected $dataModel = 'propel';

    /**
     * Whether we handle multi languages or not.
     *
     * @var bool
     */
    protected $multiLanguage = false;

    /**
     * Whether we handle workspaces or not.
     *
     * @var bool
     */
    protected $workspace = false;

    /**
     * @var bool
     */
    protected $domainDepended = false;

    /**
     * Comma separated list of plugins.
     *
     * @var string
     */
    protected $plugins;

    /**
     * Which field shall be used as default label for a default text input `field` instance in the user interface.
     * (ka.Field instance)
     *
     * @var string
     */
    protected $fieldLabel;

    /**
     * @var string
     */
    protected $fieldTemplate;

    /**
     * If the object is nested.
     *
     * @var bool
     */
    protected $nested = false;

    /**
     * @var bool
     */
    protected $nestedRootAsObject = false;

    /**
     * @var string
     */
    protected $nestedRootObject;

    /**
     * @var string
     */
    protected $nestedRootObjectField;

    /**
     * @var string
     */
    protected $nestedRootObjectLabelField;

    /**
     * @var string
     */
    protected $nestedRootObjectExtraFields;

    /**
     * Which field shall be used as default label for the nested set object.
     *
     * @var string
     */
    protected $treeLabel;

    /**
     * @var string
     */
    protected $treeTemplate;

    /**
     * Which fields are selected per default in the nested rest.
     *
     * @var string
     */
    protected $treeFields;

    /**
     * @var string
     */
    protected $treeIcon;

    /**
     * @var array
     */
    protected $treeIconMapping;

    /**
     * Javascript class/function for the tree user interface.
     *
     * @var string
     */
    protected $treeInterfaceClass;

    /**
     * @var string default|custom
     */
    protected $treeInterface = 'default';

    /**
     * @var string
     */
    protected $treeDefaultIcon;

    /**
     * @var bool
     */
    protected $treeFixedIcon = false;

    /**
     * @var string
     */
    protected $treeRootObjectIconPath;

    /**
     * @var bool
     */
    protected $treeRootObjectFixedIcon = false;

    /**
     * Which field shall be used as label for the nested root.
     *
     * @var string
     */
    protected $treeRootFieldLabel;

    /**
     * @var string
     */
    protected $treeRootFieldTemplate;

    /**
     * Comma separated list of field which shall be selected per default in the rest api.
     *
     * @var string
     */
    protected $treeRootFieldFields;

    /**
     * The javascript class/function to be used as the user interface for object browsing/listing.
     *
     * @var
     */
    protected $browserInterfaceClass;

    /**
     * @var string default|custom
     */
    protected $browserInterface = 'default';

    /**
     * @var string custom|default
     */
    protected $browserDataModel = 'default';

    /**
     * The PHP class which handles the retrieving of items for the browsing rest api.
     *
     * @var string
     */
    protected $browserDataModelClass;

    /**
     * @var Field[]
     */
    protected $browserOptions;

    /**
     * @var array
     */
    protected $limitDataSets;

    /**
     * @var Field[]
     */
    protected $fields;

    /**
     * The callable string pointing to a method/function that generates the actual public url for a object pk.
     *
     * example:
     *
     *  `\MyBundle\ControllerXy::getPublicUrl`
     *
     * The function should have this signature:
     *
     *   `function($objectKey, array $pk, array $pluginProperties )
     *
     * @var string
     */
    protected $publicUrlGenerator;

    /**
     * Class name to be used in propel's model classes.
     * Default is the `id`.
     *
     * \BundleName\Models\<className>
     *
     * @var string
     */
    protected $propelClassName;

    public function setupObject()
    {
        $this->id = $this->element->attributes->getNamedItem('id')->nodeValue;

        $blacklist = array('id', 'browserOptions', 'treeIconMapping', 'fields', 'limitDataSets');

        foreach ($this as $k => $v) {
            if (true === in_array($k, $blacklist)) {
                continue;
            }

            $this->setVar($k);
        }

        $this->treeIconMapping = $this->getParameterValues('treeIconMapping', 'icon');
    }

    /**
     * @param string $blacklistSelection
     */
    public function setBlacklistSelection($blacklistSelection)
    {
        $this->blacklistSelection = $blacklistSelection;
    }

    /**
     * @return string
     */
    public function getBlacklistSelection()
    {
        return $this->blacklistSelection;
    }

    /**
     * @param string $browserDataModel
     */
    public function setBrowserDataModel($browserDataModel)
    {
        $this->browserDataModel = $browserDataModel;
    }

    /**
     * @return string
     */
    public function getBrowserDataModel()
    {
        return $this->browserDataModel;
    }

    /**
     * @param string $browserDataModelClass
     */
    public function setBrowserDataModelClass($browserDataModelClass)
    {
        $this->browserDataModelClass = $browserDataModelClass;
    }

    /**
     * @return string
     */
    public function getBrowserDataModelClass()
    {
        return $this->browserDataModelClass;
    }

    /**
     * @param string $browserInterface
     */
    public function setBrowserInterface($browserInterface)
    {
        $this->browserInterface = $browserInterface;
    }

    /**
     * @return string
     */
    public function getBrowserInterface()
    {
        return $this->browserInterface;
    }

    /**
     * @param  $browserInterfaceClass
     */
    public function setBrowserInterfaceClass($browserInterfaceClass)
    {
        $this->browserInterfaceClass = $browserInterfaceClass;
    }

    /**
     * @return
     */
    public function getBrowserInterfaceClass()
    {
        return $this->browserInterfaceClass;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $dataModel
     */
    public function setDataModel($dataModel)
    {
        $this->dataModel = $dataModel;
    }

    /**
     * @return string
     */
    public function getDataModel()
    {
        return $this->dataModel;
    }

    /**
     * @param string $defaultSelection
     */
    public function setDefaultSelection($defaultSelection)
    {
        $this->defaultSelection = $defaultSelection;
    }

    /**
     * @return string
     */
    public function getDefaultSelection()
    {
        return $this->defaultSelection;
    }

    /**
     * @param boolean $domainDepended
     */
    public function setDomainDepended($domainDepended)
    {
        $this->domainDepended = filter_var($domainDepended, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getDomainDepended()
    {
        return $this->domainDepended;
    }

    /**
     * @param string $fieldLabel
     */
    public function setFieldLabel($fieldLabel)
    {
        $this->fieldLabel = $fieldLabel;
    }

    /**
     * @return string
     */
    public function getFieldLabel()
    {
        return $this->fieldLabel;
    }

    /**
     * @param string $fieldTemplate
     */
    public function setFieldTemplate($fieldTemplate)
    {
        $this->fieldTemplate = $fieldTemplate;
    }

    /**
     * @return string
     */
    public function getFieldTemplate()
    {
        return $this->fieldTemplate;
    }

    /**
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        if (null === $this->fields) {
            $fields = $this->getDirectChild('fields');
            if ($fields) {
                foreach ($fields->childNodes as $field) {
                    if ('field' === $field->nodeName) {
                        $this->fields[] = $this->getConfig()->getModelInstance($field);
                    }
                }
            }
        }

        return $this->fields;
    }

    public function getFieldsArray()
    {
        $fields = array();
        foreach ($this->getFields() as $field) {
            $fields[lcfirst($field->getId())] = $field->toArray();
        }
        return $fields;
    }

    /**
     * @param $fieldId
     *
     * @return Field
     */
    public function getField($fieldId)
    {
        foreach ($this->getFields() as $field) {
            if ($field->getId() === $field) {
                return $field;
            }
        }
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $labelField
     */
    public function setLabelField($labelField)
    {
        $this->labelField = $labelField;
    }

    /**
     * @return string
     */
    public function getLabelField()
    {
        return $this->labelField;
    }

    /**
     * @param string $labelTemplate
     */
    public function setLabelTemplate($labelTemplate)
    {
        $this->labelTemplate = $labelTemplate;
    }

    /**
     * @return string
     */
    public function getLabelTemplate()
    {
        return $this->labelTemplate;
    }

    /**
     * @param boolean $multiLanguage
     */
    public function setMultiLanguage($multiLanguage)
    {
        $this->multiLanguage = filter_var($multiLanguage, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getMultiLanguage()
    {
        return $this->multiLanguage;
    }

    /**
     * @param boolean $nested
     */
    public function setNested($nested)
    {
        $this->nested = filter_var($nested, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getNested()
    {
        return $this->nested;
    }

    public function isNested()
    {
        return true === $this->nested;
    }

    /**
     * @param boolean $nestedRootAsObject
     */
    public function setNestedRootAsObject($nestedRootAsObject)
    {
        $this->nestedRootAsObject = filter_var($nestedRootAsObject, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getNestedRootAsObject()
    {
        return $this->nestedRootAsObject;
    }

    /**
     * @param string $nestedRootObject
     */
    public function setNestedRootObject($nestedRootObject)
    {
        $this->nestedRootObject = $nestedRootObject;
    }

    /**
     * @return string
     */
    public function getNestedRootObject()
    {
        return $this->nestedRootObject;
    }

    /**
     * @param string $nestedRootObjectExtraFields
     */
    public function setNestedRootObjectExtraFields($nestedRootObjectExtraFields)
    {
        $this->nestedRootObjectExtraFields = $nestedRootObjectExtraFields;
    }

    /**
     * @return string
     */
    public function getNestedRootObjectExtraFields()
    {
        return $this->nestedRootObjectExtraFields;
    }

    /**
     * @param string $nestedRootObjectField
     */
    public function setNestedRootObjectField($nestedRootObjectField)
    {
        $this->nestedRootObjectField = $nestedRootObjectField;
    }

    /**
     * @return string
     */
    public function getNestedRootObjectField()
    {
        return $this->nestedRootObjectField;
    }

    /**
     * @param string $nestedRootObjectLabelField
     */
    public function setNestedRootObjectLabelField($nestedRootObjectLabelField)
    {
        $this->nestedRootObjectLabelField = $nestedRootObjectLabelField;
    }

    /**
     * @return string
     */
    public function getNestedRootObjectLabelField()
    {
        return $this->nestedRootObjectLabelField;
    }

    /**
     * @param string $plugins
     */
    public function setPlugins($plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @return string
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param string $treeDefaultIcon
     */
    public function setTreeDefaultIcon($treeDefaultIcon)
    {
        $this->treeDefaultIcon = $treeDefaultIcon;
    }

    /**
     * @return string
     */
    public function getTreeDefaultIcon()
    {
        return $this->treeDefaultIcon;
    }

    /**
     * @param string $treeFields
     */
    public function setTreeFields($treeFields)
    {
        $this->treeFields = $treeFields;
    }

    /**
     * @return string
     */
    public function getTreeFields()
    {
        return $this->treeFields;
    }

    /**
     * @param boolean $treeFixedIcon
     */
    public function setTreeFixedIcon($treeFixedIcon)
    {
        $this->treeFixedIcon = filter_var($treeFixedIcon, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getTreeFixedIcon()
    {
        return $this->treeFixedIcon;
    }

    /**
     * @param string $treeIcon
     */
    public function setTreeIcon($treeIcon)
    {
        $this->treeIcon = $treeIcon;
    }

    /**
     * @return string
     */
    public function getTreeIcon()
    {
        return $this->treeIcon;
    }

    /**
     * @param array $treeIconMapping
     */
    public function setTreeIconMapping($treeIconMapping)
    {
        $this->treeIconMapping = $treeIconMapping;
    }

    /**
     * @return array
     */
    public function getTreeIconMapping()
    {
        return $this->treeIconMapping;
    }

    /**
     * @param string $treeInterface
     */
    public function setTreeInterface($treeInterface)
    {
        $this->treeInterface = $treeInterface;
    }

    /**
     * @return string
     */
    public function getTreeInterface()
    {
        return $this->treeInterface;
    }

    /**
     * @param string $treeInterfaceClass
     */
    public function setTreeInterfaceClass($treeInterfaceClass)
    {
        $this->treeInterfaceClass = $treeInterfaceClass;
    }

    /**
     * @return string
     */
    public function getTreeInterfaceClass()
    {
        return $this->treeInterfaceClass;
    }

    /**
     * @param string $treeLabel
     */
    public function setTreeLabel($treeLabel)
    {
        $this->treeLabel = $treeLabel;
    }

    /**
     * @return string
     */
    public function getTreeLabel()
    {
        return $this->treeLabel;
    }

    /**
     * @param string $treeRootFieldFields
     */
    public function setTreeRootFieldFields($treeRootFieldFields)
    {
        $this->treeRootFieldFields = $treeRootFieldFields;
    }

    /**
     * @return string
     */
    public function getTreeRootFieldFields()
    {
        return $this->treeRootFieldFields;
    }

    /**
     * @param string $treeRootFieldLabel
     */
    public function setTreeRootFieldLabel($treeRootFieldLabel)
    {
        $this->treeRootFieldLabel = $treeRootFieldLabel;
    }

    /**
     * @return string
     */
    public function getTreeRootFieldLabel()
    {
        return $this->treeRootFieldLabel;
    }

    /**
     * @param string $treeRootFieldTemplate
     */
    public function setTreeRootFieldTemplate($treeRootFieldTemplate)
    {
        $this->treeRootFieldTemplate = $treeRootFieldTemplate;
    }

    /**
     * @return string
     */
    public function getTreeRootFieldTemplate()
    {
        return $this->treeRootFieldTemplate;
    }

    /**
     * @param boolean $treeRootObjectFixedIcon
     */
    public function setTreeRootObjectFixedIcon($treeRootObjectFixedIcon)
    {
        $this->treeRootObjectFixedIcon = filter_var($treeRootObjectFixedIcon, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getTreeRootObjectFixedIcon()
    {
        return $this->treeRootObjectFixedIcon;
    }

    /**
     * @param string $treeRootObjectIconPath
     */
    public function setTreeRootObjectIconPath($treeRootObjectIconPath)
    {
        $this->treeRootObjectIconPath = $treeRootObjectIconPath;
    }

    /**
     * @return string
     */
    public function getTreeRootObjectIconPath()
    {
        return $this->treeRootObjectIconPath;
    }

    /**
     * @param string $treeTemplate
     */
    public function setTreeTemplate($treeTemplate)
    {
        $this->treeTemplate = $treeTemplate;
    }

    /**
     * @return string
     */
    public function getTreeTemplate()
    {
        return $this->treeTemplate;
    }

    /**
     * @param boolean $workspace
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = filter_var($workspace, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return boolean
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    public function setBrowserOptions($browserOptions)
    {
        $this->browserOptions = $browserOptions;
    }

    public function getBrowserOptions()
    {
        if (null === $this->browserOptions) {
            $fields = $this->getDirectChild('browserOptions');
            if ($fields) {
                foreach ($fields->childNodes as $field) {
                    if ('field' === $field->nodeName) {
                        $this->browserOptions[] = $this->getConfig()->getModelInstance($field);
                    }
                }
            }
        }

        return $this->browserOptions;
    }

    /**
     * @param array $limitDataSets
     */
    public function setLimitDataSets($limitDataSets)
    {
        //todo, read from xml
        $this->limitDataSets = $limitDataSets;
    }

    /**
     * @return array
     */
    public function getLimitDataSets()
    {
        return $this->limitDataSets;
    }

    /**
     * @param string $propelClassName
     */
    public function setPropelClassName($propelClassName)
    {
        $this->propelClassName = $propelClassName;
    }

    /**
     * @return string
     */
    public function getPropelClassName()
    {
        return $this->propelClassName;
    }

    /**
     * @param string $publicUrlGenerator
     */
    public function setPublicUrlGenerator($publicUrlGenerator)
    {
        $this->publicUrlGenerator = $publicUrlGenerator;
    }

    /**
     * @return string
     */
    public function getPublicUrlGenerator()
    {
        return $this->publicUrlGenerator;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }


}
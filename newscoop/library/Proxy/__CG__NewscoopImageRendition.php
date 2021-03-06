<?php

namespace Proxy\__CG__\Newscoop\Image;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class Rendition extends \Newscoop\Image\Rendition implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    /** @private */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    
    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function setWidth($width)
    {
        $this->__load();
        return parent::setWidth($width);
    }

    public function getWidth()
    {
        $this->__load();
        return parent::getWidth();
    }

    public function setHeight($height)
    {
        $this->__load();
        return parent::setHeight($height);
    }

    public function getHeight()
    {
        $this->__load();
        return parent::getHeight();
    }

    public function setSpecs($specs)
    {
        $this->__load();
        return parent::setSpecs($specs);
    }

    public function getSpecs()
    {
        $this->__load();
        return parent::getSpecs();
    }

    public function getName()
    {
        if ($this->__isInitialized__ === false) {
            return $this->_identifier["name"];
        }
        $this->__load();
        return parent::getName();
    }

    public function setCoords($coords)
    {
        $this->__load();
        return parent::setCoords($coords);
    }

    public function getPreview($width, $height)
    {
        $this->__load();
        return parent::getPreview($width, $height);
    }

    public function getThumbnail(\Newscoop\Image\ImageInterface $image, \Newscoop\Image\ImageService $imageService)
    {
        $this->__load();
        return parent::getThumbnail($image, $imageService);
    }

    public function generateImage($imagePath)
    {
        $this->__load();
        return parent::generateImage($imagePath);
    }

    public function generate(\Newscoop\Image\ImageInterface $image)
    {
        $this->__load();
        return parent::generate($image);
    }

    public function getAspectRatio()
    {
        $this->__load();
        return parent::getAspectRatio();
    }

    public function getSelectArea(\Newscoop\Image\ImageInterface $image)
    {
        $this->__load();
        return parent::getSelectArea($image);
    }

    public function getMinSize(\Newscoop\Image\ImageInterface $image)
    {
        $this->__load();
        return parent::getMinSize($image);
    }

    public function isCrop()
    {
        $this->__load();
        return parent::isCrop();
    }

    public function fits(\Newscoop\Image\ImageInterface $image)
    {
        $this->__load();
        return parent::fits($image);
    }

    public function setOffset($offset)
    {
        $this->__load();
        return parent::setOffset($offset);
    }

    public function setLabel($label)
    {
        $this->__load();
        return parent::setLabel($label);
    }

    public function getLabel()
    {
        $this->__load();
        return parent::getLabel();
    }

    public function getInfo()
    {
        $this->__load();
        return parent::getInfo();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'name', 'width', 'height', 'specs', 'offset', 'label');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields as $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}
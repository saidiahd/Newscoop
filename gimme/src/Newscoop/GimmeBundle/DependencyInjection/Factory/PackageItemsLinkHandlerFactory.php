<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\GimmeBundle\DependencyInjection\Factory;  
  
use Symfony\Component\DependencyInjection\ContainerBuilder;  
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;  
use JMS\SerializerBundle\DependencyInjection\HandlerFactoryInterface;  

/**
 * Handler for items link it Package object
 */
class PackageItemsLinkHandlerFactory implements HandlerFactoryInterface  
{  
    public function getConfigKey()  
    {  
        return 'items_link';  
    }  
  
    public function getType(array $config)  
    {  
        return self::TYPE_SERIALIZATION;  
    }  
  
    public function addConfiguration(ArrayNodeDefinition $builder)  
    {  
        $builder->addDefaultsIfNotSet();  
    }  
  
    public function getHandlerId(ContainerBuilder $container, array $config)  
    {  
        return 'newscoop.gimme.serializer.package_items_link_handler';
    }  
}  

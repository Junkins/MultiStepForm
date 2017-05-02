<?php
namespace MultiStepForm\ORM;

use Cake\ORM\Marshaller;
use Cake\ORM\Association;

/**
 * NotUseSetterMarshaller
 */
class NotUseSetterMarshaller extends Marshaller
{
    /**
    * publish
    *
    * @author ito
    */
    public function publish($data, $options)
    {
        $propertyMap = $this->_buildPropertyMap($data, $options);

        $entityClass = $this->_table->entityClass();
        $entity = new $entityClass($data, [
            'useSetters' => false,
            'source' => $this->_table->registryAlias()
        ]);

        foreach ($data as $key => $value) {
            if (isset($propertyMap[$key])) {
                $entity->{$key} = $propertyMap[$key]($value, $entity);;
            }
        }

        return $entity;
    }
}

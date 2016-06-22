<?php

namespace MultiStepForm\ORM;

use Cake\ORM\Marshaller;
use Cake\ORM\Association;

class NotUseSetterMarshaller extends Marshaller
{

    /**
    * publish
    *
    * @author ito
    */
    public function publish($data, $options)
    {
        $propertyMap = $this->_buildPropertyMap($options);

        $entityClass = $this->_table->entityClass();
        $entity = new $entityClass($data, ['useSetters' => false]);

        foreach ($data as $key => $value) {
            if (!isset($propertyMap[$key])) {
                continue;
            }
            $assoc = $propertyMap[$key]['association'];
            $value = $this->publishAssociation($assoc, $value, $propertyMap[$key]);
            $entity->{$key} = $value;
        }

        return $entity;
    }

    /**
    * publishAssociation
    *
    * @author ito
    */
    protected function publishAssociation($assoc, $value, $options)
    {
        if (!is_array($value)) {
            return null;
        }

        // belongsTo,HasOne
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        if (in_array($assoc->type(), $types)) {
            $targetTable = $assoc->target();
            $entityClass = $targetTable->entityClass();
            return new $entityClass($value, ['useSetters' => false]);
        }

        if ($assoc->type() === Association::MANY_TO_MANY) {
            // @todo
            return $this->publisBelongsToMany($assoc, $value, (array)$options);
        }

        // @todo
        return $this->publishMany($assoc, $value, (array)$options);
    }

    /**
    * publisBelongsToMany
    *
    * @author ito
    */
    protected function publisBelongsToMany($assoc, $value, $options)
    {
        return null;
    }

    /**
    * publishMany
    *
    * @author ito
    */
    protected function publishMany($assoc, $data, $options)
    {
        $targetTable = $assoc->target();
        $entityClass = $targetTable->entityClass();

        $entities = [];
        foreach ($data as $key => $value) {
            $entities[] = new $entityClass($value, ['useSetters' => false]);
        }

        return $entities;
    }
}

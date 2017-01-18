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
                continue;
            }
            $assoc = $this->_table->association($key);
            if (is_null($assoc)) {
                continue;
            }
            $value = $this->publishAssociation($assoc, $value);
            $entity->{$key} = $value;
        }

        return $entity;
    }

    /**
    * publishAssociation
    *
    * @author ito
    */
    protected function publishAssociation($assoc, $value)
    {
        if (!is_array($value)) {
            return null;
        }

        // belongsTo,HasOne
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        if (in_array($assoc->type(), $types)) {
            $targetTable = $assoc->target();
            $entityClass = $targetTable->entityClass();
            $entity = new $entityClass($value, [
                'useSetters' => false,
                'source'     => $this->_table->registryAlias()
            ]);
            return $entity;
        }

        if ($assoc->type() === Association::MANY_TO_MANY) {
            return $this->publisBelongsToMany($assoc, $value['_ids']);
        }

        // @todo
        return $this->publishMany($assoc, $value);
    }

    /**
    * publisBelongsToMany
    *
    * @author ito
    */
    protected function publisBelongsToMany($assoc, $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $target = $assoc->target();
        $primaryKey = (array)$target->primaryKey();
        $primaryKey = array_map([$target, 'aliasField'], $primaryKey);
        $filter = [$primaryKey[0] . ' IN' => $ids];

        $query = $target->find();
        $query->where($filter);
        return $query->toArray();
    }

    /**
    * publishMany
    *
    * @author ito
    */
    protected function publishMany($assoc, $data)
    {
        $targetTable = $assoc->target();
        $entityClass = $targetTable->entityClass();

        $entities = [];
        foreach ($data as $key => $value) {
            $entity = new $entityClass($value, [
                'useSetters' => false,
                'source'     => $this->_table->registryAlias()
            ]);
            $entities[] = $entity;
        }

        return $entities;
    }
}

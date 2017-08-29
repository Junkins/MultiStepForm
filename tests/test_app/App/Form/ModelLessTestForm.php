<?php
namespace MultiStepForm\Test\App\Form;

use Cake\Form\Form;
use Cake\Validation\Validator;
/**
 * ModelLessTestForm
 */
class ModelLessTestForm extends Form
{
    /**
     * _buildValidator
     * バリデーション
     * @author hagiwara
     */
    protected function _buildValidator(Validator $validator)
    {
        $validator
            ->requirePresence('name')
            ->notEmpty('name');
        return $validator;
    }
}
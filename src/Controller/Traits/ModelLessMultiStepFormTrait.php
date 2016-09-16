<?php
namespace MultiStepForm\Controller\Traits;

trait ModelLessMultiStepFormTrait
{
    /**
    * isAction
    * Controller::isAction() over write
    * @author ito
    */
    public function isAction($action)
    {
        if (!$this->ModelLessMultiStepForm->isWhitelist($action)) {
            return false;
        }

        return parent::isAction($action);
    }
}


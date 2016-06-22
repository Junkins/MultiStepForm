<?php

namespace MultiStepForm\Controller\Traits;

trait MultiStepFormTrait
{
    /**
    * isAction
    * Controller::isAction() over write
    * @author ito
    */
    public function isAction($action)
    {
        if (!$this->MultiStepForm->isWhitelist($action)) {
            return false;
        }

        return parent::isAction($action);
    }
}

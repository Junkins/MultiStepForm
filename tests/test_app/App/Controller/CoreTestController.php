<?php
namespace MultiStepForm\Test\App\Controller;

use Cake\Controller\Controller;

class CoreTestController extends Controller
{
    public function beforeDispatch()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
    public function beforeAddInput()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
    public function beforeAddConfirm()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
}

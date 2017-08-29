<?php
namespace MultiStepForm\Test\App\Controller;

use Cake\Controller\Controller;

class CoreTestController extends Controller
{
    public function beforeDispatch()
    {
        $this->request->session()->write('beforeDispatch', true);
    }
    public function beforeAddInput()
    {
        $this->request->session()->write('beforeAddInput', true);
    }
    public function beforeAddConfirm()
    {
        $this->request->session()->write('beforeAddConfirm', true);
    }
}

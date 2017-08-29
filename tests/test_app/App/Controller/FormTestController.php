<?php
namespace MultiStepForm\Test\App\Controller;

use Cake\Controller\Controller;

class FormTestController extends Controller
{
    public function add_input()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
    public function add_confirm()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
    public function add_finish()
    {
        $this->request->session()->write(__FUNCTION__, true);
    }
}

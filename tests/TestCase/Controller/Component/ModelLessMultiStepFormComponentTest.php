<?php
namespace MultiStepForm\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use MultiStepForm\Test\App\Controller\ModelLessFormTestController;
use Cake\TestSuite\TestCase;
use MultiStepForm\Controller\Component\ModelLessMultiStepFormComponent;
use MultiStepForm\Test\App\Form\ModelLessTestForm;
/**
 * MultiStepForm\Test\TestCase\Controller\Component\ModelLessMultiStepFormComponent Test Case
 */
class ModelLessMultiStepFormComponentTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Controller = new ModelLessFormTestController();
        $this->Controller->loadComponent('Flash');

        // request->actionはinitializeで使用するためここでセット
        $this->Controller->request->action = 'add';

        $this->ComponentRegistry = new ComponentRegistry($this->Controller);
        $this->ModelLessMultiStepForm = new ModelLessMultiStepFormComponent($this->ComponentRegistry);

        $this->ModelLessTestForm = new ModelLessTestForm();
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ModelLessMultiStepForm);
        parent::tearDown();
    }

    /**
     * Test getConfigMultiStep method
     *
     * @return void
     */
    public function test_getConfigMultiStep()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        $this->ModelLessMultiStepForm->dispatch();
        // add_inputにアクセス
        $this->assertEquals(true, $this->Controller->request->session()->read('add_input'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_confirm'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_finish'));

    }


    /**
     * Test dispatch method
     *
     * @return void
     */
    public function test_dispatchPostMaxSizeError()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        // request->dataをセットしない = post_max_size_error
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        $this->ModelLessMultiStepForm->dispatch();
        // リダイレクトが走る
        $headers = $this->Controller->response->getHeaders();
        $this->assertTrue(array_key_exists('Location', $headers));
    }

    /**
     * Test dispatch method
     *
     * @return void
     */
    public function test_dispatchNextValidationOk()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        // request dataもいる
        $postdata = $this->Controller->request->getParsedBody();
        // とりあえず内部でエラーを出さないように必要最低限をセットする
        $postdata['hidden_key'] = 'abcdef';
        $postdata['here'] = 'add_input';
        $postdata['next'] = 'next';
        $postdata['name'] = 'name';

        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        $this->ModelLessMultiStepForm->dispatch();
        // add_confirmにアクセス
        $this->assertEquals(null, $this->Controller->request->session()->read('add_input'));
        $this->assertEquals(true, $this->Controller->request->session()->read('add_confirm'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_finish'));
    }

    /**
     * Test dispatch method
     *
     * @return void
     */
    public function test_dispatchNextValidationError()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        // request dataもいる
        $postdata = $this->Controller->request->getParsedBody();
        // とりあえず内部でエラーを出さないように必要最低限をセットする
        $postdata['hidden_key'] = 'abcdef';
        $postdata['here'] = 'add_input';
        $postdata['next'] = 'next';
        // nameの必須チェックのバリデーションにかかる
        $postdata['name'] = '';

        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        $this->ModelLessMultiStepForm->dispatch();
        // add_inputにアクセス
        $this->assertEquals(true, $this->Controller->request->session()->read('add_input'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_confirm'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_finish'));
    }

    /**
     * Test dispatch method
     *
     * @return void
     */
    public function test_dispatchBack()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        // request dataもいる
        $postdata = $this->Controller->request->getParsedBody();
        // とりあえず内部でエラーを出さないように必要最低限をセットする
        $postdata['hidden_key'] = 'abcdef';
        $postdata['here'] = 'add_confirm';
        $postdata['back'] = 'back';

        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        $this->ModelLessMultiStepForm->dispatch();
        // add_inputにアクセス
        $this->assertEquals(true, $this->Controller->request->session()->read('add_input'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_confirm'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_finish'));
    }
}

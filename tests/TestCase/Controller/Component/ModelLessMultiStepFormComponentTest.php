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

    ############################################################################
    ### 正常系のテスト
    /**
     * test_dispatch_first_access
     * @author ito
     */
    public function test_dispatch_first_access()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        // FIRST ACCESS
        $this->Controller->request->env('REQUEST_METHOD', 'GET');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            true,
            null,
            null
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatch_next_to_confirm
     * @author ito
     */
    public function test_dispatch_next_to_confirm()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            null,
            true,
            null
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatch_next_to_finish
     * @author ito
     */
    public function test_dispatch_next_to_finish()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);
        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_confirm',
            'next' => 'next',
            'name' => 'name'
        ]);
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            null,
            true,
            true
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatch_back_to_input
     * @author ito
     */
    public function test_dispatch_back_to_input()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_confirm',
            'back' => 'back',
            'name' => 'name'
        ]);
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            true,
            true,
            null
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }


    ############################################################################
    ### エラーのテスト
    /**
     * test_dispatch_next_to_confirm_validation_error
     * @author ito
     */
    public function test_dispatch_next_to_confirm_validation_error()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => '' // nameの必須チェックのバリデーションにかかる
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            true,
            null,
            null
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatch_next_to_confirm_postmaxsize_error
     * @author ito
     */
    public function test_dispatch_next_to_confirm_postmaxsize_error()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);

        // リクエストデータ準備しない = post_max_size_error
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        $expected = [
            null,
            null,
            null
        ];
        $result = [
            $this->Controller->request->session()->read('add_input'),
            $this->Controller->request->session()->read('add_confirm'),
            $this->Controller->request->session()->read('add_finish')
        ];

        $this->assertEquals($result, $expected);
    }


    /**
     * test_dispatch_next_to_confirm_postmaxsize_error_redirect
     * @author ito
     */
    public function test_dispatch_next_to_confirm_postmaxsize_error_redirect()
    {
        $this->ModelLessMultiStepForm->setForm($this->ModelLessTestForm);

        // リクエストデータ準備しない = post_max_size_error
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->ModelLessMultiStepForm->dispatch();

        // ヘッダーの取得
        $headers = $this->Controller->response->getHeaders();
        $this->assertTrue(array_key_exists('Location', $headers));
    }
}

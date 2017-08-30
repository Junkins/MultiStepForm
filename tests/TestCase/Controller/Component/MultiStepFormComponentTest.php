<?php
namespace MultiStepForm\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\FixtureManager;
use MultiStepForm\Test\App\Controller\FormTestController;
use Cake\TestSuite\TestCase;
use MultiStepForm\Test\App\Model\Table\PostsTable;
use MultiStepForm\Controller\Component\MultiStepFormComponent;
/**
 * MultiStepForm\Test\TestCase\Controller\Component\MultiStepFormCoreComponent Test Case
 */
class MultiStepFormComponentTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.multi_step_form.posts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->Posts = new PostsTable([
            'alias' => 'Posts',
            'table' => 'posts',
            'connection' => $this->connection
        ]);

        //fixtureManagerを呼び出し、fixtureを実行する
        $this->fixtureManager = new FixtureManager();
        $this->fixtureManager->fixturize($this);
        $this->fixtureManager->loadSingle('Posts');

        $this->Controller = new FormTestController();
        $this->Controller->loadComponent('Flash');

        // request->actionはinitializeで使用するためここでセット
        $this->Controller->request->action = 'add';

        $this->ComponentRegistry = new ComponentRegistry($this->Controller);
        $this->MultiStepForm = new MultiStepFormComponent($this->ComponentRegistry);
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->MultiStepForm);
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
        // FIRST ACCESS
        $this->MultiStepForm->dispatch();

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
        $this->MultiStepForm->Table = $this->Posts;

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

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
        $this->MultiStepForm->Table = $this->Posts;

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_confirm',
            'next' => 'next',
            'name' => 'name'
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

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
        $this->MultiStepForm->Table = $this->Posts;

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => 'name'
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_confirm',
            'back' => 'back',
            'name' => 'name'
        ]);
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

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
        $this->MultiStepForm->Table = $this->Posts;

        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
            'next' => 'next',
            'name' => '' // nameの必須チェックのバリデーションにかかる
        ]);

        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

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
        $this->MultiStepForm->Table = $this->Posts;

        // リクエストデータ準備しない = post_max_size_error
        // POST
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepForm->dispatch();

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
}

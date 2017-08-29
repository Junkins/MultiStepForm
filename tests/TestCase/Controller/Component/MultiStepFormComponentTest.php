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

    /**
     * Test dispatch method
     *
     * @return void
     */
    public function test_dispatchGet()
    {
        // 初期アクセス
        $this->MultiStepForm->dispatch();
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
        $this->MultiStepForm->dispatch();
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

        // $this->MultiStepForm->setTable('Posts');でのセットではdefaultのConnectionでアクセスをしようとするので
        $this->MultiStepForm->Table = $this->Posts;
        $this->MultiStepForm->dispatch();
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

        // $this->MultiStepForm->setTable('Posts');でのセットではdefaultのConnectionでアクセスをしようとするので
        $this->MultiStepForm->Table = $this->Posts;
        $this->MultiStepForm->dispatch();
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

        // $this->MultiStepForm->setTable('Posts');でのセットではdefaultのConnectionでアクセスをしようとするので
        $this->MultiStepForm->Table = $this->Posts;
        $this->MultiStepForm->dispatch();
        // add_inputにアクセス
        $this->assertEquals(true, $this->Controller->request->session()->read('add_input'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_confirm'));
        $this->assertEquals(null, $this->Controller->request->session()->read('add_finish'));
    }

}

<?php
namespace MultiStepForm\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use MultiStepForm\Test\App\Controller\CoreTestController;
use Cake\TestSuite\TestCase;
use MultiStepForm\Controller\Component\MultiStepFormCoreComponent;
/**
 * MultiStepForm\Test\TestCase\Controller\Component\MultiStepFormCoreComponent Test Case
 */
class MultiStepFormCoreComponentTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Controller = new CoreTestController();

        // request->actionはinitializeで使用するためここでセット
        $this->Controller->request->action = 'add';

        $this->ComponentRegistry = new ComponentRegistry($this->Controller);
        $this->MultiStepFormCore = new MultiStepFormCoreComponent($this->ComponentRegistry);
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->MultiStepFormCore);
        parent::tearDown();
    }

    /**
     * Test getConfigMultiStep method
     *
     * @return void
     */
    public function test_getConfigMultiStep()
    {
        // 全体の設定値取得
        $config = $this->MultiStepFormCore->getConfigMultiStep();
        $assert = [
            'add_input'   => [
                'back'       => false,
                'next'       => 'add_confirm',
                'validate'   => 'default',
                'associated' => []
            ],
            'add_confirm' => [
                'back'       => 'add_input',
                'next'       => 'add_finish',
                'validate'   => false,
                'associated' => []
            ],
            'add_finish'    => [
                'back'       => 'add_confirm',
                'next'       => false,
                'validate'   => false,
                'associated' => []
            ],
        ];
        $this->assertTrue(
            $config === $assert
        );

        // 個別の設定値取得
        $config = $this->MultiStepFormCore->getConfigMultiStep('add_input');
        $assert = [
            'back'       => false,
            'next'       => 'add_confirm',
            'validate'   => 'default',
            'associated' => []
        ];
        $this->assertTrue(
            $config === $assert
        );
    }
    
    /**
     * Test setConfigMultiStep method
     *
     * @return void
     */
    public function test_setConfigMultiStep()
    {
        $setConfig = [
            'edit_input'   => [
                'back'       => false,
                'next'       => 'edit_confirm',
                'validate'   => 'defaultEdit',
                'associated' => []
            ],
            'edit_confirm' => [
                'back'       => 'edit_input',
                'next'       => 'edit_finish',
                'validate'   => false,
                'associated' => []
            ],
            'edit_finish'    => [
                'back'       => 'edit_confirm',
                'next'       => false,
                'validate'   => false,
                'associated' => []
            ],
        ];
        $this->MultiStepFormCore->setConfigMultiStep($setConfig);

        $config = $this->MultiStepFormCore->getConfigMultiStep();
        $this->assertTrue(
            $config === $setConfig
        );
    }

    /**
     * Test insertConfigMultiStep method
     *
     * @return void
     */
    public function test_insertConfigMultiStep()
    {
        $this->MultiStepFormCore->insertConfigMultiStep('add_input.validate', 'defaultCopy');
        $this->MultiStepFormCore->insertConfigMultiStep('add_finish.associated.0', 'Category');
        // 値の確認
        $assert = [
            'add_input'   => [
                'back'       => false,
                'next'       => 'add_confirm',
                'validate'   => 'defaultCopy',
                'associated' => []
            ],
            'add_confirm' => [
                'back'       => 'add_input',
                'next'       => 'add_finish',
                'validate'   => false,
                'associated' => []
            ],
            'add_finish'    => [
                'back'       => 'add_confirm',
                'next'       => false,
                'validate'   => false,
                'associated' => ['Category']
            ],
        ];

        $config = $this->MultiStepFormCore->getConfigMultiStep();
        $this->assertTrue(
            $config === $assert
        );
    }

    /**
     * Test mergeConfigMultiStep method
     *
     * @return void
     */
    public function test_mergeConfigMultiStep()
    {
        $mergeConfig = [
            'add_input'   => [
                'validate'   => 'defaultCopy',
            ],
            'add_finish'    => [
                'associated' => ['Category']
            ],
        ];
        $this->MultiStepFormCore->mergeConfigMultiStep($mergeConfig);
        // 値の確認
        $assert = [
            'add_input'   => [
                'back'       => false,
                'next'       => 'add_confirm',
                'validate'   => 'defaultCopy',
                'associated' => []
            ],
            'add_confirm' => [
                'back'       => 'add_input',
                'next'       => 'add_finish',
                'validate'   => false,
                'associated' => []
            ],
            'add_finish'    => [
                'back'       => 'add_confirm',
                'next'       => false,
                'validate'   => false,
                'associated' => ['Category']
            ],
        ];

        $config = $this->MultiStepFormCore->getConfigMultiStep();
        $this->assertTrue(
            $config === $assert
        );
    }

    /**
     * Test getData method
     * post値が空の時の挙動
     *
     * @return void
     */
    public function test_getDataEmpty()
    {
        $data = $this->MultiStepFormCore->getData();
        $assert = [];
        $this->assertTrue(
            $data === $assert
        );
    }

    /**
     * Test getData method
     * sessionが空の時の挙動
     *
     * @return void
     */
    public function test_getDataSessionEmpty()
    {
        // request->dataの加工
        $postdata = $this->Controller->request->getParsedBody();
        $postdata['hidden_key'] = 'abcdef';
        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $data = $this->MultiStepFormCore->getData();
        $assert = [];
        $this->assertTrue(
            $data === $assert
        );
    }

    /**
     * Test getData method
     *
     * @return void
     */
    public function test_getData()
    {
        // request->dataの加工
        $postdata = $this->Controller->request->getParsedBody();
        $postdata['hidden_key'] = 'abcdef';
        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $sessionData = [
            'name' => 'aaa',
            'hoge' => 'bbb',
            'fuga' => 'ccc',
        ];
        $this->Controller->request->session()->write('abcdef', $sessionData);

        $data = $this->MultiStepFormCore->getData();

        $this->assertTrue(
            $data === $sessionData
        );
    }

    /**
     * Test dispatch method
     * Coreのテストではhookポイントの処理だけ
     * get時はhookポイントにアクセスしない
     *
     * @return void
     */
    public function test_dispatchGet()
    {
        // getとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'GET');
        $this->MultiStepFormCore->dispatch();
        // beforeDispatchは通らない
        $this->assertEquals(null, $this->Controller->request->session()->read('beforeDispatch'));
    }

    /**
     * Test dispatch method
     * Coreのテストではhookポイントの処理だけ
     * post max size error時
     *
     * @return void
     */
    public function test_dispatchPostMaxSizeError()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepFormCore->dispatch();
        // post値が空の時はbeforeDispatchは通らない(post_max_sizeのエラーの場合なので)
        $this->assertEquals(null, $this->Controller->request->session()->read('beforeDispatch'));
    }

    /**
     * Test dispatch method
     * Coreのテストではhookポイントの処理だけ
     *
     * @return void
     */
    public function test_dispatchBeforeDispatch()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');

        // request dataもいる
        $postdata = $this->Controller->request->getParsedBody();
        // とりあえず内部でエラーを出さないように必要最低限をセットする
        $postdata['hidden_key'] = 'abcdef';
        $postdata['here'] = 'add_input';
        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $this->MultiStepFormCore->dispatch();
        // post値が空の時はbeforeDispatchは通らない(post_max_sizeのエラーの場合なので)
        $this->assertEquals(true, $this->Controller->request->session()->read('beforeDispatch'));
        // beforeAddInputは通るし、beforeAddConfirmは通らない
        $this->assertEquals(true, $this->Controller->request->session()->read('beforeAddInput'));
        $this->assertEquals(null, $this->Controller->request->session()->read('beforeAddConfirm'));
    }

    /**
     * Test dispatch method
     * Coreのテストではhookポイントの処理だけ
     * callbackのテスト
     *
     * @return void
     */
    public function test_dispatchCallback()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');

        // request dataもいる
        $postdata = $this->Controller->request->getParsedBody();
        // とりあえず内部でエラーを出さないように必要最低限をセットする
        $postdata['hidden_key'] = 'abcdef';
        $postdata['here'] = 'add_input';
        $this->Controller->request = $this->Controller->request->withParsedBody($postdata);

        $this->MultiStepFormCore->insertConfigMultiStep('add_input.callback', 'beforeAddConfirm');

        $this->MultiStepFormCore->dispatch();
        // post値が空の時はbeforeDispatchは通らない(post_max_sizeのエラーの場合なので)
        $this->assertEquals(true, $this->Controller->request->session()->read('beforeDispatch'));
        // callbackでbeforeAddConfirmを指定しているのでinputではなくconfirmを通す
        $this->assertEquals(null, $this->Controller->request->session()->read('beforeAddInput'));
        $this->assertEquals(true, $this->Controller->request->session()->read('beforeAddConfirm'));
    }
}

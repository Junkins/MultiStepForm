<?php
namespace MultiStepForm\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use MultiStepForm\Test\App\Controller\CoreTestController;
use Cake\TestSuite\IntegrationTestCase;
use MultiStepForm\Controller\Component\MultiStepFormCoreComponent;

/**
 * MultiStepForm\Test\TestCase\Controller\Component\MultiStepFormCoreComponent Test Case
 */
class MultiStepFormCoreComponentTest extends IntegrationTestCase
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

    ############################################################################
    ### Configのテスト
    /**
     * test_getConfigMultiStep
     * @author ito
     * @dataProvider data_getConfigMultiStep
     */
    public function test_getConfigMultiStep($here, $expected)
    {
        $result = $this->MultiStepFormCore->getConfigMultiStep($here);
        $this->assertEquals($result, $expected);
    }

    /**
     * data_getConfigMultiStep
     * @author ito
     */
    public function data_getConfigMultiStep()
    {
        return [
            [
                '',
                [
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
                ]
            ],
            [
                'add_input',
                [
                    'back'       => false,
                    'next'       => 'add_confirm',
                    'validate'   => 'default',
                    'associated' => []
                ]
            ],
            [
                'add_confirm',
                [
                    'back'       => 'add_input',
                    'next'       => 'add_finish',
                    'validate'   => false,
                    'associated' => []
                ]
            ],
            [
                'add_finish',
                [
                    'back'       => 'add_confirm',
                    'next'       => false,
                    'validate'   => false,
                    'associated' => []
                ]
            ]
        ];
    }

    /**
     * test_setConfigMultiStep
     * @author ito
     * @dataProvider data_setConfigMultiStep
     */
    public function test_setConfigMultiStep($config, $here, $expected)
    {
        $this->MultiStepFormCore->setConfigMultiStep($config);
        $result = $this->MultiStepFormCore->getConfigMultiStep($here);
        $this->assertEquals($result, $expected);
    }

    /**
     * data_setConfigMultiStep
     * @author ito
     */
    public function data_setConfigMultiStep()
    {
        $config1 = [
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
            ]
        ];

        return [
            [$config1, '' , $config1],
            [$config1, 'edit_input' , $config1['edit_input']],
            [$config1, 'edit_confirm' , $config1['edit_confirm']],
            [$config1, 'edit_finish' , $config1['edit_finish']]
        ];
    }

    /**
     * test_insertConfigMultiStep
     * @author ito
     * @dataProvider data_insertConfigMultiStep
     */
    public function test_insertConfigMultiStep($path, $value, $here, $expected)
    {
        $this->MultiStepFormCore->insertConfigMultiStep($path, $value);
        $result = $this->MultiStepFormCore->getConfigMultiStep($here);
        $this->assertEquals($result, $expected);
    }

    /**
     * data_insertConfigMultiStep
     * @author ito
     */
    public function data_insertConfigMultiStep()
    {
        $defaultConfig = [
            'add_input'   => [
                'back'       => false,
                'next'       => 'add_confirm',
                'validate'   => 'defaultInput',
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

        return [
            [
                'add_confirm.validate',
                'defaultConfirm',
                'add_confirm',
                [
                    'back'       => 'add_input',
                    'next'       => 'add_finish',
                    'validate'   => 'defaultConfirm',
                    'associated' => []
                ]
            ],
            [
                'add_finish.associated.0',
                'Category',
                'add_finish',
                [
                    'back'       => 'add_confirm',
                    'next'       => false,
                    'validate'   => false,
                    'associated' => ['Category']
                ]
            ]
        ];
    }

    /**
     * test_mergeConfigMultiStep
     * @author ito
     * @dataProvider data_mergeConfigMultiStep
     */
    public function test_mergeConfigMultiStep($config, $here, $expected)
    {
        $this->MultiStepFormCore->mergeConfigMultiStep($config);
        $result = $this->MultiStepFormCore->getConfigMultiStep($here);
        $this->assertEquals($result, $expected);
    }

    /**
     * data_mergeConfigMultiStep
     * @author ito
     */
    public function data_mergeConfigMultiStep()
    {
        $defaultConfig = [
            'add_input'   => [
                'back'       => false,
                'next'       => 'add_confirm',
                'validate'   => 'defaultInput',
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

        return [
            [
                [
                    'add_input' => [
                        'validate' => 'defaultInput2'
                    ],
                    'add_confirm' => [
                        'validate' => 'defaultConfirm2'
                    ]
                ],
                '',
                [
                    'add_input'   => [
                        'back'       => false,
                        'next'       => 'add_confirm',
                        'validate'   => 'defaultInput2',
                        'associated' => []
                    ],
                    'add_confirm' => [
                        'back'       => 'add_input',
                        'next'       => 'add_finish',
                        'validate'   => 'defaultConfirm2',
                        'associated' => []
                    ],
                    'add_finish'    => [
                        'back'       => 'add_confirm',
                        'next'       => false,
                        'validate'   => false,
                        'associated' => []
                    ],
                ]
            ],
            [
                [
                    'add_input' => [
                        'next'     => 'add_input2',
                        'validate' => 'defaultInput2'
                    ]
                ],
                'add_input',
                [
                    'back'       => false,
                    'next'       => 'add_input2',
                    'validate'   => 'defaultInput2',
                    'associated' => []
                ]
            ]
        ];
    }

    ############################################################################
    ### Dataのテスト
    /**
     * test_getData
     * @author ito
     * @dataProvider data_getData
     */
    public function test_getData($request, $session, $expected)
    {
        // リクエストのセット
        $this->Controller->request = $this->Controller->request->withParsedBody($request);

        $hiddenKey = (isset($request['hidden_key']))? $request['hidden_key']: '';
        $this->Controller->request->session()->write($hiddenKey, $session);

        $result = $this->MultiStepFormCore->getData();

        $this->assertEquals($result, $expected);
    }

    /**
     * data_getData
     * @author ito
     */
    public function data_getData()
    {
        $hiddenKey = 'abcdef';
        return [
            [
                [],
                [],
                []
            ],
            [
                ['hidden_key' => $hiddenKey],
                [],
                []
            ],
            [
                ['hidden_key' => $hiddenKey],
                [
                    'name' => 'aaa',
                    'hoge' => 'bbb',
                    'fuga' => 'ccc',
                ],
                [
                    'name' => 'aaa',
                    'hoge' => 'bbb',
                    'fuga' => 'ccc',
                ]
            ],
        ];
    }

    ############################################################################
    /**
     * test_dispatchGet
     * @author ito
     * Coreのテストではhookポイントの処理だけ
     * get時はhookポイントにアクセスしない
     */
    public function test_dispatchGet()
    {
        // getとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'GET');
        $this->MultiStepFormCore->dispatch();

        $expected = null; // beforeDispatchは通らない
        $result = $this->Controller->request->session()->read('beforeDispatch');

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatchPostMaxSizeError
     * @author ito
     * Coreのテストではhookポイントの処理だけ
     * post max size error時
     */
    public function test_dispatchPostMaxSizeError()
    {
        // postとして挙動させる
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepFormCore->dispatch();

        // post値が空の時はbeforeDispatchは通らない(post_max_sizeのエラーの場合なので)
        $expected = null; // beforeDispatchは通らない
        $result = $this->Controller->request->session()->read('beforeDispatch');

        $this->assertEquals($result, $expected);
    }

    /**
     * test_dispatchBeforeDispatch
     * @author ito
     * Coreのテストではhookポイントの処理だけ
     * @dataProvider data_dispatchBeforeDispatch
     */
    public function test_dispatchBeforeDispatch($callback, $expected)
    {
        // リクエストデータ準備
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'hidden_key' => 'abcdef',
            'here' => 'add_input',
        ]);

        if (!empty($callback)) {
            $this->MultiStepFormCore->insertConfigMultiStep('add_input.callback', $callback);
        }

        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->MultiStepFormCore->dispatch();

        // beforeAddInputは通るし、beforeAddConfirmは通らない
        $result = [
            $this->Controller->request->session()->read('beforeDispatch'),
            $this->Controller->request->session()->read('beforeAddInput'),
            $this->Controller->request->session()->read('beforeAddConfirm')
        ];

        $this->assertEquals($result, $expected);
    }

    /**
     * data_dispatchBeforeDispatch
     * @author ito
     */
    public function data_dispatchBeforeDispatch()
    {
        return [
            [
                '',
                [
                    true,
                    true,
                    null
                ]
            ],
            [
                'beforeAddConfirm',
                [
                    true,
                    null,
                    true
                ]
            ]
        ];
    }
}

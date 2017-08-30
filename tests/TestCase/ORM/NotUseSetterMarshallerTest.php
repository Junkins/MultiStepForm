<?php
namespace MultiStepForm\Test\TestCase\ORM;

use Cake\TestSuite\TestCase;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\FixtureManager;
use MultiStepForm\Test\App\Model\Table\PostsTable;
use MultiStepForm\ORM\NotUseSetterMarshaller;

/**
 * MultiStepForm\ORM\NotUseSetterMarshaller Test Case
 */
class NotUseSetterMarshallerTest extends TestCase
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

        $this->NotUseSetterMarshaller = new NotUseSetterMarshaller($this->Posts);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Posts);
        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function test_publish()
    {
        $data = [
            'name' => 'aaa',
            'dummy' => 'bbb',
        ];
        $config = [
            'back'       => false,
            'next'       => 'confirm',
            'validate'   => 'default',
            'associated' => []
        ];
        $entity = $this->NotUseSetterMarshaller->publish($data, $config);
        $this->assertTrue($data === $entity->toArray());
    }
}

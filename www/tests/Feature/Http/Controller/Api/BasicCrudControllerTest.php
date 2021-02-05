<?php

namespace Tests\Feature\Http\Controller\Api;

use Mockery;
use Tests\TestCase;
use ReflectionClass;
use Illuminate\Http\Request;
use Tests\Stubs\Models\CategoryStub;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Api\BasicCrudController;
use Tests\Stubs\Controllers\CategoryControllerStub;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BasicCrudControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        CategoryStub::dropTable();
        CategoryStub::createTable();
        $this->controller = new CategoryControllerStub();
    }

    protected function tearDown(): void
    {
        CategoryStub::dropTable();
        parent::tearDown();
    }

    public function testIndex()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create([
            'name' => 'test_name',
            'description' => 'test_description'
        ]);
        $this->controller = new CategoryControllerStub();
        $this->assertEquals(
            [$category->toArray()],
            $this->controller->index()->toArray()
        );
    }

    public function testInvalidationInStore()
    {
        $this->expectException(ValidationException::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['name' => '']);
        $this->controller->store($request);
    }

    public function testStore()
    {
        $request = Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->once()
            ->andReturn(['name' => 'test_name', 'description' => 'test_description']);

        $obj = $this->controller->store($request);

        $this->assertEquals(CategoryStub::find(1)->toArray(), $obj->toArray());
    }

    public function testIfFindOrFailFetchModel()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create([
            'name' => 'test_name',
            'description' => 'test_description'
        ]);

        $reflectionClass = new ReflectionClass(BasicCrudController::class);

        $reflectionMethod = $reflectionClass->getMethod('findOrfail');
        $reflectionMethod->setAccessible(true); // NOSONAR

        $result = $reflectionMethod->invokeArgs($this->controller, [$category->id]);

        $this->assertInstanceOf(CategoryStub::class, $result);
    }

    public function testIfFindOrFailThrowExceptionWhenIdInvalid()
    {
        $this->expectException(ModelNotFoundException::class);

        $reflectionClass = new ReflectionClass(BasicCrudController::class);

        $reflectionMethod = $reflectionClass->getMethod('findOrfail');
        $reflectionMethod->setAccessible(true); //NOSONAR
        $reflectionMethod->invokeArgs($this->controller, [0]);
    }

    public function testShow()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create([
            'name' => 'test_name',
            'description' => 'test_description'
        ]);

        /** @var CategoryStub $result */
        $result = $this->controller->show($category->id);

        $this->assertEquals($result->toArray(), $category->toArray());
    }

    public function testUpdate()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create([
            'name' => 'test_name',
            'description' => 'test_description'
        ]);

        $request = Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->once()
            ->andReturn([
                'name' => 'test_changed',
                'description' => 'test_description_chenged'
            ]);

        /** @var CategoryStub $result */
        $result = $this->controller->update($request, $category->id);

        $this->assertEquals($result->toArray(), CategoryStub::find(1)->toArray());
    }

    public function testDestroy()
    {
        /** @var CategoryStub $category */
        $category = CategoryStub::create([
            'name' => 'test_name',
            'description' => 'test_description'
        ]);

        $response = $this->controller->destroy($category->id);

        $this
            ->createTestResponse($response)
            ->assertStatus(\Illuminate\Http\Response::HTTP_NO_CONTENT);

        $this->assertCount(0, CategoryStub::all());
    }
}

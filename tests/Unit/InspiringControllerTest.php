<?php

namespace Tests\Unit;

use App\Http\Controllers\InspiringController;
use App\Services\InspiringService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InspiringServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testStatus()
    {
        $response = $this->get('/inspire');
        $response->assertStatus(200);
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testInspire()
    {
        $mock = \Mockery::mock(InspiringService::class);
        $mock->shouldReceive('inspire')->andReturn('名言');
        $inspiringController = new InspiringController($mock);
        self::assertEquals(
            '名言',
            $inspiringController->inspire()
        );
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        self::assertIsString(
            (new InspiringService())->inspire()
        );
    }

    public function testMysql()
    {
        self::assertIsObject(
            new PDO("mysql:host=127.0.0.1;port=3306;dbname=default", 'default', 'secret')
        );
    }

}

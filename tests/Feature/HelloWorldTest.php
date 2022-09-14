<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HelloWorldTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/hello-world');

        // 如果連線  /hello-world HTTP Status 應該要顯示 200 (成功連線)
        $response->assertStatus(200);

        $response->assertSee('Hello World!');
    }
}

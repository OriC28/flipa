<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Session\FlashType;
use Core\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected ?Session $session = null;
    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new Session();
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }
    public function test_put_stores_data_in_session_array(): void
    {
        $this->session->put('TEST_PUT', 'SUCCESS');
        $this->assertArrayHasKey('TEST_PUT', $_SESSION);
    }
    public function test_get_returns_value_stored_in_session(): void
    {
        $this->session->put('TEST_GET', 'SUCCESS');
        $this->assertSame('SUCCESS',  $this->session->get('TEST_GET'));
    }
    public function test_get_returns_null_for_missing_key(): void
    {
        $this->assertNull($this->session->get('KEY'));
    }
    public function test_get_flash_retrieves_message_and_deletes_it_from_session(): void
    {
        $this->session->flash('welcome', 'Welcome to system', FlashType::FLASH_INFO);

        $firstRead = $this->session->getFlash('welcome');
        $this->assertSame('Welcome to system', $firstRead['message']);
        $this->assertSame(FlashType::FLASH_INFO, $firstRead['type']);

        $secondRead = $this->session->getFlash('welcome');
        $this->assertEmpty($secondRead);
    }
    public function test_has_returns_true_when_key_exists(): void
    {
        $this->session->put('TEST_HAS', 'SUCCESS');
        $this->assertTrue($this->session->has('TEST_HAS'));
    }
    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $this->assertFalse($this->session->has('TEST_HAS_NOT_EXISTS'));
    }
    public function test_all_returns_entire_session_array(): void
    {
        $this->session->put('FIRST', 'FOO');
        $this->session->put('SECOND', 'BOO');
        $this->assertSame($_SESSION, $this->session->all());
    }
    public function test_forget_removes_key_from_session(): void
    {
        $this->session->put('TEST_FORGET', 'FOO');
        $this->session->forget('TEST_FORGET');
        $this->assertArrayNotHasKey('TEST_FORGET', $_SESSION);
    }
    public function test_destroy_all_data_registered_from_session(): void
    {
        $this->session->put('FIRST', 'FOO');
        $this->session->put('SECOND', 'BOO');
        $this->session->destroy();
        $this->assertEmpty($this->session->all());
    }
    public function test_regenerate_calls_session_regenerate_id(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $id = session_id();
        $this->session->regenerate();
        $idRegenerated = session_id();
        $this->assertNotSame($id, $idRegenerated);
    }
}

<?php

namespace MikeWeb\Dsn\Test;

use MikeWeb\Dsn\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase {
    
    public function testMySql() {
        $inputString = 'mysql://root:root_pass@127.0.0.1:3306/test_db';
        $Dsn = new Dsn($inputString);

        $this->assertTrue($Dsn->isValid());
        $this->assertEquals('mysql', $Dsn->getProtocol());
        $this->assertEquals('root', $Dsn->getUsername());
        $this->assertEquals('root_pass', $Dsn->getPassword());
        $this->assertEquals('127.0.0.1', $Dsn->getFirstHost());
        $this->assertEquals('3306', $Dsn->getFirstPort());
        $this->assertEquals('test_db', $Dsn->getDatabase());
        $this->assertEquals($inputString, $Dsn->getDsn());

        $auth = $Dsn->getAuthentication();
        $this->assertArrayHasKey('username', $auth);
        $this->assertArrayHasKey('password', $auth);

        $this->assertEquals('root', $auth['username']);
        $this->assertEquals('root_pass', $auth['password']);
    }

    public function testParameters() {
        $Dsn = new Dsn('mysql://127.0.0.1/test_db?foo=bar&baz=biz&aa');

        $this->assertTrue($Dsn->isValid());
        $parameters = $Dsn->getParameters();

        $this->assertArrayHasKey('foo', $parameters);
        $this->assertArrayHasKey('baz', $parameters);
        $this->assertArrayHasKey('aa', $parameters);

        $this->assertEquals('bar', $parameters['foo']);
        $this->assertEquals('biz', $parameters['baz']);
        $this->assertNull($parameters['aa']);
    }

    public function testParametersOnWithoutDatabase() {
        $Dsn = new Dsn('mysql://127.0.0.1?foo=bar');
        $this->assertTrue($Dsn->isValid());
        $parameters = $Dsn->getParameters();
        $this->assertArrayHasKey('foo', $parameters);
        $this->assertEquals('bar', $parameters['foo']);
    }

    public function authenticationProvider() {
        return [
            ['mysql://root:root_pass@127.0.0.1:3306/test_db', 'root', 'root_pass', 'username', 'password'],
            ['mysql://127.0.0.1:3306/test_db', null, null, 'username', 'password'],
        ];
    }

    /**
     * @dataProvider authenticationProvider
     */
    public function testAuthentication($DsnString, $expectedUserName, $expectedPassword, $expectedAuthUserName, $expectedAuthPassword) {
        $Dsn = new Dsn($DsnString);
        $this->assertTrue($Dsn->isValid());
        $this->assertEquals($expectedUserName, $Dsn->getUsername());
        $this->assertEquals($expectedPassword, $Dsn->getPassword());

        $auth = $Dsn->getAuthentication();
        $this->assertArrayHasKey($expectedAuthUserName, $auth);
        $this->assertArrayHasKey($expectedAuthPassword, $auth);

        $this->assertEquals($expectedUserName, $auth['username']);
        $this->assertEquals($expectedPassword, $auth['password']);
    }

    public function testPartlyMissing() {
        $Dsn = new Dsn('mysql://127.0.0.1/test_db');

        $this->assertTrue($Dsn->isValid());
        $this->assertEquals('mysql', $Dsn->getProtocol());
        $this->assertEquals('127.0.0.1', $Dsn->getFirstHost());
        $this->assertEquals('test_db', $Dsn->getDatabase());
        $this->assertNull($Dsn->getUsername());
        $this->assertNull($Dsn->getPassword());
        $this->assertNull($Dsn->getFirstPort());
    }

    public function testMissingPassword() {
        $Dsn = new Dsn('mysql://root@127.0.0.1:3306/test_db');

        $this->assertTrue($Dsn->isValid());
        $this->assertEquals('mysql', $Dsn->getProtocol());
        $this->assertEquals('127.0.0.1', $Dsn->getFirstHost());
        $this->assertEquals('test_db', $Dsn->getDatabase());
        $this->assertEquals('root', $Dsn->getUsername());
        $this->assertNull($Dsn->getPassword());
    }

    public function invalidDsnProvider() {
        return [
            ['myql:127.0.0.1/test_db'],
            ['myql://'],
        ];
    }

    /**
     * @dataProvider invalidDsnProvider
     */
    public function testInvalid($DsnString) {
        $Dsn = new Dsn($DsnString);
        $this->assertFalse($Dsn->isValid());
    }
}

<?php

/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace Erikwang2013\IndustrialProtocols\FlexRay\Tests\Unit;

use Erikwang2013\IndustrialProtocols\Bridge\TcpGatewayBridge;
use Erikwang2013\IndustrialProtocols\FlexRay\FlexRayProtocol;
use PHPUnit\Framework\TestCase;

class FlexRayTest extends TestCase
{
    public function testMetadata(): void
    {
        $p = new FlexRayProtocol();
        $this->assertSame('flexray', $p->getName());
        $this->assertSame('1.1.1', $p->getVersion());
    }

    public function testRequiresBridge(): void
    {
        $this->expectException(\RuntimeException::class);
        (new FlexRayProtocol())->createConnector([]);
    }

    public function testWithBridge(): void
    {
        $this->assertFalse((new FlexRayProtocol())->createConnector(['bridge' => new TcpGatewayBridge('127.0.0.1', 9999)])->isConnected());
    }

    public function testVariants(): void
    {
        $this->assertSame(['bridge'], (new FlexRayProtocol())->getSupportedVariants());
        $this->assertSame(0, (new FlexRayProtocol())->getDefaultPort());
    }

    public function testConnectorDelegatesToBridge(): void
    {
        $bridge = $this->stubBridge();
        $connector = (new FlexRayProtocol())->createConnector(['bridge' => $bridge]);

        $this->assertSame(\Erikwang2013\IndustrialProtocols\Connection\ConnectionState::CLOSED, $connector->getHealth()->state);
        $connector->connect();
        $this->assertTrue($connector->isConnected());
        $this->assertSame(['ecu/1' => 'ok'], $connector->read('ecu/1'));
        $this->assertSame(['ecu/1' => 'ok'], $connector->write('ecu/1', [1]));
        $connector->disconnect();
        $this->assertFalse($connector->isConnected());
    }

    private function stubBridge(): \Erikwang2013\IndustrialProtocols\Bridge\BridgeInterface
    {
        return new class implements \Erikwang2013\IndustrialProtocols\Bridge\BridgeInterface {
            private bool $ready = false;
            public function open(): void { $this->ready = true; }
            public function close(): void { $this->ready = false; }
            public function execute(string $command, string|array $data = ''): string { return 'ok'; }
            public function isReady(): bool { return $this->ready; }
            public function getType(): string { return 'stub'; }
        };
    }
}

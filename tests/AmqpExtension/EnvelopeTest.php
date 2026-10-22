<?php

declare(strict_types=1);

namespace HumusTest\Amqp\AmqpExtension;

use AMQPBasicProperties;
use AMQPEnvelope;
use Humus\Amqp\Driver\AmqpExtension\Envelope;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EnvelopeTest extends TestCase
{
    /** @dataProvider optionalStringProperties */
    public function testAbsentOptionalPropertyRetainsStringContract(string $property, string $getter): void
    {
        if (! extension_loaded('amqp')) {
            self::markTestSkipped('The native AMQP extension is required.');
        }

        self::assertSame('', (new Envelope(new AMQPEnvelope()))->$getter());
    }

    /** @dataProvider populatedOptionalStringProperties */
    public function testPresentOptionalPropertyIsUnchanged(string $property, string $getter, string $value): void
    {
        if (! extension_loaded('amqp') || version_compare(phpversion('amqp'), '2.0.0', '<')) {
            self::markTestSkipped('The AMQP 2 basic-properties constructor is required.');
        }

        $reflection = new ReflectionClass(AMQPBasicProperties::class);
        $arguments = [];
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $arguments[] = $parameter->getName() === $property ? $value : $parameter->getDefaultValue();
        }

        $native = $reflection->newInstanceArgs($arguments);
        self::assertSame($value, $native->$getter());
        self::assertSame($value, (new Envelope($native))->$getter());
    }

    public function optionalStringProperties(): array
    {
        return [
            'content type' => ['contentType', 'getContentType'],
            'content encoding' => ['contentEncoding', 'getContentEncoding'],
            'message type' => ['type', 'getType'],
            'user id' => ['userId', 'getUserId'],
            'application id' => ['appId', 'getAppId'],
            'message id' => ['messageId', 'getMessageId'],
            'reply to' => ['replyTo', 'getReplyTo'],
            'correlation id' => ['correlationId', 'getCorrelationId'],
        ];
    }

    public function populatedOptionalStringProperties(): array
    {
        $cases = [];
        foreach ($this->optionalStringProperties() as $label => [$property, $getter]) {
            foreach (['value', '', '0', 'Zażółć gęślą'] as $i => $value) {
                $cases[$label . ' ' . $i] = [$property, $getter, $value];
            }
        }

        return $cases;
    }
}

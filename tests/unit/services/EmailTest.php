<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\ImproperActionException;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    private Email $Email;

    private LoggerInterface $Logger;

    protected function setUp(): void
    {
        $this->Logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $this->Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->Email = new Email($MockMailer, $this->Logger, 'phpunit@example.net');
    }

    public function testTestemailSend(): void
    {
        $this->assertTrue($this->Email->testemailSend('toto@example.com'));
    }

    public function testNotConfigured(): void
    {
        $MockMailer = $this->createMock(MailerInterface::class);
        $NotConfiguredEmail = new Email($MockMailer, $this->Logger, 'notconfigured@example.com');
        $this->assertFalse($NotConfiguredEmail->testemailSend('toto@example.com'));
    }

    public function testTransportException(): void
    {
        $MockMailer = $this->createMock(MailerInterface::class);
        $MockMailer->method('send')->willThrowException(new TransportException());
        $Email = new Email($MockMailer, $this->Logger, 'yep@nope.blah');
        $this->expectException(ImproperActionException::class);
        $Email->testemailSend('toto@example.com');

    }

    public function testMassEmail(): void
    {
        $replyTo = new Address('sender@example.com', 'Sergent Garcia');
        $this->assertEquals(16, $this->Email->massEmail(EmailTarget::ActiveUsers, null, '', 'yep'));
        $this->assertEquals(7, $this->Email->massEmail(EmailTarget::Team, 1, 'Important message', 'yep'));
        $this->assertEquals(0, $this->Email->massEmail(EmailTarget::TeamGroup, 1, 'Important message', 'yep'));
        $this->assertEquals(6, $this->Email->massEmail(EmailTarget::Admins, null, 'Important message to admins', 'yep'));
        $this->assertEquals(1, $this->Email->massEmail(EmailTarget::Sysadmins, null, 'Important message to sysadmins', 'yep', $replyTo));
    }

    public function testSendEmail(): void
    {
        $this->assertTrue($this->Email->sendEmail(new Address('a@a.fr', 'blah'), 's', 'b'));
    }

    public function testNotifySysadminsTsBalance(): void
    {
        $this->assertTrue($this->Email->notifySysadminsTsBalance(12));
    }
}

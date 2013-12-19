<?php

namespace BradFeehan\Rainmaker\Test\Unit\Mailbox;

use ArrayIterator;
use BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox;
use BradFeehan\Rainmaker\Test\UnitTestCase;
use Zend\Mail\Storage\Message;

class FeedbackLoopFilterMailboxTest extends UnitTestCase
{

    /**
     * @covers BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox::accept
     */
    public function testAccept()
    {
        $contentType = \Mockery::mock('Zend\\Mail\\Header\\ContentType')
            ->shouldReceive('getType')
                ->andReturn('multipart/report')
            ->shouldReceive('getParameter')
                ->andReturn('feedback-report')
            ->getMock();

        $original = \Mockery::mock('Zend\\Mail\\Storage\\Message')
            ->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn($contentType)
            ->getMock();

        $mailbox = \Mockery::mock(
            'BradFeehan\\Rainmaker\\Mailbox\\FeedbackLoopFilterMailbox[original]',
            array(\Mockery::mock('Iterator')) // needs constructor arg
        );
        $mailbox->shouldReceive('original')->andReturn($original);

        $this->assertTrue($mailbox->accept());
    }

    /**
     * @covers BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox::accept
     */
    public function testAcceptWithNonMessage()
    {
        $original = \Mockery::mock('NotAZendMessage');

        $mailbox = \Mockery::mock(
            'BradFeehan\\Rainmaker\\Mailbox\\FeedbackLoopFilterMailbox[original]',
            array(\Mockery::mock('Iterator')) // needs constructor arg
        );
        $mailbox->shouldReceive('original')->andReturn($original);

        $this->assertFalse($mailbox->accept());
    }

    /**
     * @covers BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox::accept
     */
    public function testAcceptWithNonFeedbackReport()
    {
        $contentType = \Mockery::mock('Zend\\Mail\\Header\\ContentType')
            ->shouldReceive('getType')
                ->andReturn('multipart/mixed')
            ->getMock();

        $original = \Mockery::mock('Zend\\Mail\\Storage\\Message')
            ->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn($contentType)
            ->getMock();

        $mailbox = \Mockery::mock(
            'BradFeehan\\Rainmaker\\Mailbox\\FeedbackLoopFilterMailbox[original]',
            array(\Mockery::mock('Iterator')) // needs constructor arg
        );
        $mailbox->shouldReceive('original')->andReturn($original);

        $this->assertFalse($mailbox->accept());
    }

    /**
     * @covers BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox::current
     */
    public function testCurrent()
    {
        $original = \Mockery::mock('Zend\\Mail\\Storage\\Message');

        $mailbox = \Mockery::mock(
            'BradFeehan\\Rainmaker\\Mailbox\\FeedbackLoopFilterMailbox[original]',
            array(\Mockery::mock('Iterator')) // needs constructor arg
        );
        $mailbox->shouldReceive('original')->andReturn($original);

        $current = $mailbox->current();

        $this->assertInstanceOf(
            'BradFeehan\\Rainmaker\\FeedbackLoopMessage',
            $current
        );

        $this->assertSame($original, $current->getSource());
    }

    /**
     * @covers BradFeehan\Rainmaker\Mailbox\FeedbackLoopFilterMailbox::original
     */
    public function testOriginal()
    {
        $original = \Mockery::mock('Zend\\Mail\\Storage\\Message');

        $innerMailbox = new ArrayIterator(array($original));
        $mailbox = new FeedbackLoopFilterMailbox($innerMailbox);

        $this->assertSame($original, $mailbox->original());
    }
}
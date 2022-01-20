<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core\Imagine\Filter\Loader;

use Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleWidthFilterLoader;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use PHPUnit\Framework\TestCase;

class ScaleWidthFilterLoaderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $innerLoader;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\Loader\ScaleWidthFilterLoader */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerLoader = $this->createMock(LoaderInterface::class);
        $this->loader = new ScaleWidthFilterLoader();
        $this->loader->setInnerLoader($this->innerLoader);
    }

    public function testLoadFail()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loader->load($this->createMock(ImageInterface::class, []));
    }

    public function testLoad()
    {
        $width = 123;
        $image = $this->createMock(ImageInterface::class);
        $this->innerLoader
            ->expects($this->once())
            ->method('load')
            ->with($image, $this->equalTo(['widen' => $width]))
            ->will($this->returnValue($image));

        $this->assertSame($image, $this->loader->load($image, [$width]));
    }
}

class_alias(ScaleWidthFilterLoaderTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\Imagine\Filter\Loader\ScaleWidthFilterLoaderTest');

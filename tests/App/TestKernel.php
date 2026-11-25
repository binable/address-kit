<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\App;

use Binable\AddressKit\AddressKitBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed> $addressKitConfig
     */
    public function __construct(private readonly array $addressKitConfig = [])
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new AddressKitBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.spl_object_id($this);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'validation' => ['enabled' => true],
        ]);
        $container->extension('address_kit', $this->addressKitConfig);

        $container->services()
            ->alias('test.validator', 'validator')
            ->public();
    }
}

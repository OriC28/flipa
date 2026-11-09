<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container\Container;
use Core\Container\Exceptions\BindingResolutionException;
use Core\Container\Exceptions\CircularDependencyException;
use Core\Container\Exceptions\ContainerInstanceException;
use Tests\Fixtures\Container\AnotherConcreteClass;
use Tests\Fixtures\Container\CircularDependencyClassA;
use Tests\Fixtures\Container\ClassWithDependency;
use Tests\Fixtures\Container\ClassWithPrimitiveDefault;
use Tests\Fixtures\Container\ClassWithUnionTypes;
use Tests\Fixtures\Container\ClassWithUnresolvablePrimitive;
use Tests\Fixtures\Container\ConcreteClass;
use Tests\Fixtures\Container\NonInstantiableInterface;
use Tests\Fixtures\Container\DependencyClass;
use Tests\TestBase;

class ContainerTest extends TestBase
{
    protected Container $container;
    protected function setUp(): void
    {
        parent::setUp();
        Container::clearInstance();
        $this->container = new Container();
    }
    public function test_bind_returns_new_instance_each_time(): void
    {
        $this->container->bind(ConcreteClass::class, fn() => new ConcreteClass());
        $dummy_1 = $this->container->make(ConcreteClass::class);
        $dummy_2 = $this->container->make(ConcreteClass::class);
        $this->assertNotSame($dummy_1, $dummy_2);
    }
    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(ConcreteClass::class, fn() => new ConcreteClass());
        $dummy_1 = $this->container->make(ConcreteClass::class);
        $dummy_2 = $this->container->make(ConcreteClass::class);
        $this->assertSame($dummy_1, $dummy_2);
    }
    public function test_has_returns_true_for_registered_binding(): void
    {
        $this->container->singleton(ConcreteClass::class, fn() => new ConcreteClass());
        $this->container->bind(AnotherConcreteClass::class, fn() => new AnotherConcreteClass());
        $this->assertTrue($this->container->has(ConcreteClass::class));
        $this->assertTrue($this->container->has(AnotherConcreteClass::class));
    }
    public function test_has_returns_false_for_unregistered_binding(): void
    {
        $this->assertFalse($this->container->has(ConcreteClass::class));
        $this->assertFalse($this->container->has(AnotherConcreteClass::class));
    }
    public function test_resolves_class_without_constructor_without_prior_binding(): void
    {
        $this->assertInstanceOf(ConcreteClass::class, $this->container->make(ConcreteClass::class));
    }

    public function test_container_resolves_dependencies_recursively_via_auto_wiring(): void
    {
        $classWithDependencyInstance = $this->container->make(ClassWithDependency::class);
        $this->assertInstanceOf(DependencyClass::class, $classWithDependencyInstance->dependency);
    }
    public function test_container_resolves_primitive_parameters_with_default_values(): void
    {
        $classWithPrimitiveDefault = $this->container->make(ClassWithPrimitiveDefault::class);
        $this->assertSame('development', $classWithPrimitiveDefault->env);
    }
    public function test_container_throws_binding_resolution_exception_for_unresolvable_primitive(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->container->make(ClassWithUnresolvablePrimitive::class);
    }
    public function test_container_throws_binding_resolution_exception_for_non_instantiable_class(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->container->make(NonInstantiableInterface::class);
    }
    public function test_container_throws_circular_dependency_exception_for_circular_dependencies(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->container->make(CircularDependencyClassA::class);
    }
    public function test_call_method_executes_closure_with_auto_wired_dependencies(): void
    {
        $this->assertSame('foo', $this->container->call(fn(DependencyClass $dependecy) => $dependecy->property));
    }
    public function test_call_method_executes_controller_method_with_auto_wired_dependencies(): void
    {
        $this->assertSame('foo', $this->container->call([$this->container->make(ClassWithDependency::class), 'show']));
    }
    public function test_container_resolves_union_types_correctly(): void
    {
        $classWithUnionTypesInstance = $this->container->make(ClassWithUnionTypes::class);
        $this->assertInstanceOf(ConcreteClass::class, $classWithUnionTypesInstance->unionTypeDependency);
    }
    public function test_set_instance_throws_exception_if_already_exists(): void
    {
        Container::setInstance($this->container);
        $this->expectException(ContainerInstanceException::class);
        Container::setInstance($this->container);
    }
}

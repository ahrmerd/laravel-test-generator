<?php
namespace Ahrmerd\TestGenerator;

use function PHPUnit\Framework\assertTrue;

class TestHelpers
{
    static function assertActionUsesFormRequest(string $controller, string $method, string $form_request)
    {
        assertTrue(is_subclass_of($form_request, 'Illuminate\\Foundation\\Http\\FormRequest'), $form_request . ' is not a type of Form Request');
        $reflector = new \ReflectionClass($controller);
        $action = $reflector->getMethod($method);

        assertTrue($action->isPublic(), 'Action "' . $method . '" is not public, controller actions must be public.');

        $actual = collect($action->getParameters())->contains(function ($parameter) use ($form_request) {
            return $parameter->getType() instanceof \ReflectionNamedType && $parameter->getType()->getName() === $form_request;
        });
        assertTrue($actual, 'Action "' . $method . '" does not have validation using the "' . $form_request . '" Form Request.');
    }
}

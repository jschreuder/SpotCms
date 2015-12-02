<?php

namespace Spot\Common\ParticleFixes;

use Particle\Validator\Chain;
use Particle\Validator\ValidationResult;

class Validator extends \Particle\Validator\Validator
{
    /**
     * Validates the values in the $values array and returns a ValidationResult.
     *
     * @param array $values
     * @param string $context
     * @return ValidationResult
     */
    public function validate(array $values, $context = self::DEFAULT_CONTEXT)
    {
        $isValid = true;
        $output = new Container();
        $input = new Container($values);
        $stack = $this->getMergedMessageStack($context);

        foreach ($this->chains[$context] as $chain) {
            /** @var Chain $chain */
            $isValid = $chain->validate($stack, $input, $output) && $isValid;
        }

        $result = new ValidationResult(
            $isValid,
            $stack->getFailures(),
            $this->filterResultByExistingInput($output->getArrayCopy(), $input->getArrayCopy())
        );

        $stack->reset();

        return $result;
    }

    /**
     * Walks through the output recursively to check if all its values exist in the input.
     * Only keys that are part of the input should be visible as output.
     *
     * @param array $output
     * @param array $input
     * @return array
     */
    private function filterResultByExistingInput(array $output, array $input)
    {
        foreach ($output as $key => $value) {
            if (!array_key_exists($key, $input)) {
                unset($output[$key]);
                continue;
            }
            if (is_array($value) && is_array($input[$key])) {
                $output[$key] = $this->filterResultByExistingInput($output[$key], $input[$key]);
            }
        }

        return $output;
    }
}

<?php

namespace MarvinLabs\Html\Bootstrap\Elements;

use BadMethodCallException;
use MarvinLabs\Html\Bootstrap\Elements\Traits\Assemblable;
use Spatie\Html\Elements\Div;

abstract class ControlWrapper extends Div
{
    use Assemblable;

    /** @var \Spatie\Html\BaseElement */
    protected $control;

    /** @var array */
    protected $wrapperClasses;

    /** @var array */
    protected $delegatedControlAttributes;

    public function __construct(\Spatie\Html\BaseElement $control,
                                array $wrapperClasses = [],
                                array $delegatedControlAttributes = ['name', 'disabled'])
    {
        parent::__construct();
        $this->control = $control;
        $this->wrapperClasses = $wrapperClasses;
        $this->delegatedControlAttributes = $delegatedControlAttributes;
    }


    /**
     * For some attributes, return the value of the backing control instead of our own
     *
     * @param string $attribute
     * @param null   $fallback
     *
     * @return mixed
     */
    public function getAttribute($attribute, $fallback = null)
    {
        if (\in_array($attribute, $this->delegatedControlAttributes, true))
        {
            return $this->getControlAttribute($attribute, $fallback);
        }

        return parent::getAttribute($attribute, $fallback);
    }

    /**
     * @param string|null $name
     *
     * @return static
     */
    public function name($name)
    {
        $element = clone $this;
        $element->control = $this->control
            ->nameIf($name, $name)
            ->idIf($name, field_name_to_id($name));

        return $element;
    }

    protected function assemble()
    {
        if ($this->control === null)
        {
            return $this;
        }

        $element = $this->wrapControl();

        return $element->addClass($this->wrapperClasses);
    }

    protected abstract function wrapControl();

    public function __call($name, $arguments)
    {
        // Control setters
        foreach (['control' => '', 'forgetControl' => 'forget', 'addControl' => 'add'] as $needle => $replacement)
        {
            if (starts_with($name, $needle))
            {
                $name = str_replace($needle, $replacement, $name);
                if (!method_exists($this->control, $name))
                {
                    throw new BadMethodCallException("$name is not a valid method for the wrapped control");
                }

                $element = clone $this;
                $element->control = $this->control->{$name}(...$arguments);
                return $element;
            }
        }

        if (starts_with($name, 'getControl')) {
            $name = str_replace('getControl', 'get', $name);
            if (empty($name)) {
                return $this->control;
            }

            if (! method_exists($this->control, $name)) {
                throw new BadMethodCallException("$name is not a valid method for the wrapped control");
            }

            return $this->control->{$name}(...$arguments);
        }

        return parent::__call($name, $arguments);
    }
}
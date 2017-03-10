<?php

namespace SimpleXmlMapper;

use Doctrine\Common\Inflector\Inflector;

class CamelCasePropertyNameConverter implements PropertyNameConverterInterface
{
    /**
     * Convert the specified XML property name to its PHP property name.
     *
     * @param string $name
     * @return string
     */
    public function convert($name)
    {
        return Inflector::camelize($name);
    }
}

<?php

namespace SimpleXmlMapper;

interface PropertyNameConverterInterface
{
    /**
     * Convert the specified XML property name to its PHP property name.
     *
     * @param string $name
     * @return string
     */
    public function convert($name);
}

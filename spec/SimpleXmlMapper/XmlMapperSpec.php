<?php

namespace spec\SimpleXmlMapper;

use DateTime;
use SimpleXMLElement;
use PhpSpec\ObjectBehavior;
use SimpleXmlMapper\XmlMapper;
use SimpleXmlMapper\CamelCasePropertyNameConverter;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class XmlMapperSpec extends ObjectBehavior
{
    function let()
    {
        $listExtractors = [new ReflectionExtractor];
        $typeExtractors = [new PhpDocExtractor];
        $extractor = new PropertyInfoExtractor($listExtractors, $typeExtractors);
        $nameConverter = new CamelCasePropertyNameConverter;
        $this->beConstructedWith($extractor, $nameConverter);
        $this->addType(DateTime::class, function ($xml) {
            return DateTime::createFromFormat('Y-m-d H:i:s', $xml);
        });
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(XmlMapper::class);
    }

    function it_maps_strings()
    {
        $this->make_it_map_xml_to_object()->model->shouldEqual('Golf R');
    }

    function it_maps_integers()
    {
        $this->make_it_map_xml_to_object()->year->shouldEqual(2016);
    }

    function it_maps_floats()
    {
        $this->make_it_map_xml_to_object()->msrp->shouldEqual(39995.95);
    }

    function it_maps_truthy_booleans()
    {
        $this->make_it_map_xml_to_object()->awd->shouldEqual(true);
    }

    function it_maps_falsey_booleans()
    {
        $this->make_it_map_xml_to_object()->hybrid->shouldEqual(false);
    }

    function it_maps_arrays()
    {
        $result = $this->make_it_map_xml_to_object()->colours;
        $result->shouldBeArray();
        $result->shouldHaveCount(2);
        $result->shouldContain('Lapiz Blue');
    }

    function it_maps_objects()
    {
        $this->make_it_map_xml_to_object()->manufacturer->shouldHaveType(Manufacturer::class);
    }

    function it_maps_object_collections()
    {
        $result = $this->make_it_map_xml_to_object()->options;
        $result->shouldBeArray();
        $result->shouldHaveCount(2);
        $result->shouldContainType(Option::class);
    }

    function it_maps_custom_types()
    {
        $this->make_it_map_xml_to_object()->manufacturer->founded->shouldHaveType(DateTime::class);
    }

    function it_converts_studly_case_to_camel_case()
    {
        $this->make_it_map_xml_to_object()->horsePower->shouldEqual(292);
    }

    function it_converts_snake_case_to_camel_case()
    {
        $this->make_it_map_xml_to_object()->litresPerHundred->shouldEqual(7.7);
    }

    function make_it_map_xml_to_object()
    {
        $file = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<car>
    <manufacturer>
        <name>Volkswagen</name>
        <founded>1937-01-04 00:00:00</founded>
    </manufacturer>
    <model>Golf R</model>
    <year>2016</year>
    <msrp>39995.95</msrp>
    <colours>
        <colour>Lapiz Blue</colour>
        <colour>Oryx White</colour>
    </colours>
    <options>
        <option>
            <name>19-inch "Pretoria" Wheels</name>
        </option>
        <option>
            <name>Technology Package</name>
        </option>
    </options>
    <hybrid>false</hybrid>
    <awd>true</awd>
    <HorsePower>292</HorsePower>
    <litres_per_hundred>7.7</litres_per_hundred>
</car>
XML;
        $xml = new SimpleXMLElement($file);
        return $this->map($xml, Car::class, true);
    }

    function getMatchers()
    {
        return [
            'containType' => function ($result, $type) {
                foreach ($result as $item) {
                    if (!$item instanceof $type) {
                        return false;
                    }
                }
                return true;
            }
        ];
    }
}

class Car
{
    /**
     * @var Manufacturer
     */
    public $manufacturer;

    /**
     * @var string
     */
    public $model;

    /**
     * @var int
     */
    public $year;

    /**
     * @var float
     */
    public $msrp;

    /**
     * @var array
     */
    public $colours = [];

    /**
     * @var Option[]
     */
    public $options = [];

    /**
     * @var bool
     */
    public $hybrid;

    /**
     * @var bool
     */
    public $awd;

    /**
     * @var int
     */
    public $horsePower;

    /**
     * @var float
     */
    public $litresPerHundred;
}

class Manufacturer
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var DateTime
     */
    public $founded;
}

class Option
{
    /**
     * @var string
     */
    public $name;
}

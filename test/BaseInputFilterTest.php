<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use ArrayIterator;
use ArrayObject;
use FilterIterator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Filter;
use Zend\InputFilter\ArrayInput;
use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\Exception\InvalidArgumentException;
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputInterface;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\BaseInputFilter
 */
class BaseInputFilterTest extends TestCase
{
    /**
     * @var BaseInputFilter
     */
    protected $inputFilter;

    public function setUp()
    {
        $this->inputFilter = new BaseInputFilter();
    }

    public function testInputFilterIsEmptyByDefault()
    {
        $filter = $this->inputFilter;
        $this->assertEquals(0, count($filter));
    }

    public function testAddWithInvalidInputTypeThrowsInvalidArgumentException()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an instance of Zend\InputFilter\InputInterface or Zend\InputFilter\InputFilterInterface ' .
            'as its first argument; received "stdClass"'
        );
        /** @noinspection PhpParamsInspection */
        $inputFilter->add(new stdClass());
    }

    public function testGetThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'no input found matching "not exists"'
        );
        $inputFilter->get('not exists');
    }

    public function testReplaceWithInvalidInputTypeThrowsInvalidArgumentException()
    {
        $inputFilter = $this->inputFilter;
        $inputFilter->add(new Input('foo'), 'replace_me');

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an instance of Zend\InputFilter\InputInterface or Zend\InputFilter\InputFilterInterface ' .
            'as its first argument; received "stdClass"'
        );
        /** @noinspection PhpParamsInspection */
        $inputFilter->replace(new stdClass(), 'replace_me');
    }

    public function testReplaceThrowExceptionIfInputToReplaceDoesNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'no input found matching "not exists"'
        );
        $inputFilter->replace(new Input('foo'), 'not exists');
    }

    public function testGetValueThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            '"not exists" was not found in the filter'
        );
        $inputFilter->getValue('not exists');
    }

    public function testGetRawValueThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            '"not exists" was not found in the filter'
        );
        $inputFilter->getRawValue('not exists');
    }

    public function testSetDataWithInvalidDataTypeThrowsInvalidArgumentException()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an array or Traversable argument; received stdClass'
        );
        /** @noinspection PhpParamsInspection */
        $inputFilter->setData(new stdClass());
    }

    public function testIsValidThrowExceptionIfDataWasNotSetYet()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            RuntimeException::class,
            'no data present to validate'
        );
        $inputFilter->isValid();
    }

    public function testSetValidationGroupThrowExceptionIfInputIsNotAnInputFilter()
    {
        $inputFilter = $this->inputFilter;

        /** @var InputInterface|MockObject $nestedInput */
        $nestedInput = $this->getMock(InputInterface::class);
        $inputFilter->add($nestedInput, 'fooInput');

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Input "fooInput" must implement InputFilterInterface'
        );
        $inputFilter->setValidationGroup(['fooInput' => 'foo']);
    }

    public function testSetValidationGroupThrowExceptionIfInputFilterNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects a list of valid input names; "anotherNotExistsInputFilter" was not found'
        );
        $inputFilter->setValidationGroup(['notExistInputFilter' => 'anotherNotExistsInputFilter']);
    }

    public function testSetValidationGroupThrowExceptionIfInputFilterInArgumentListNotExists()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects a list of valid input names; "notExistInputFilter" was not found'
        );
        $inputFilter->setValidationGroup('notExistInputFilter');
    }

    public function testHasUnknownThrowExceptionIfDataWasNotSetYet()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            RuntimeException::class
        );
        $inputFilter->hasUnknown();
    }

    public function testGetUnknownThrowExceptionIfDataWasNotSetYet()
    {
        $inputFilter = $this->inputFilter;

        $this->setExpectedException(
            RuntimeException::class
        );
        $inputFilter->getUnknown();
    }

    /**
     * Verify the state of the input filter is the desired after change it using the method `add()`
     *
     * @dataProvider addMethodArgumentsProvider
     */
    public function testAddHasGet($input, $name, $expectedInputName, $expectedInput)
    {
        $inputFilter = $this->inputFilter;
        $this->assertFalse(
            $inputFilter->has($expectedInputName),
            "InputFilter shouldn't have an input with the name $expectedInputName yet"
        );
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->add($input, $name);
        $this->assertSame($inputFilter, $return, "add() must return it self");

        // **Check input collection state**
        $this->assertTrue($inputFilter->has($expectedInputName), "There is no input with name $expectedInputName");
        $this->assertCount($currentNumberOfFilters + 1, $inputFilter, 'Number of filters must be increased by 1');

        $returnInput = $inputFilter->get($expectedInputName);
        $this->assertEquals($expectedInput, $returnInput, 'get() does not match the expected input');
    }

    /**
     * Verify the state of the input filter is the desired after change it using the method `add()` and `remove()`
     *
     * @dataProvider addMethodArgumentsProvider
     */
    public function testAddRemove($input, $name, $expectedInputName)
    {
        $inputFilter = $this->inputFilter;

        $inputFilter->add($input, $name);
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->remove($expectedInputName);
        $this->assertSame($inputFilter, $return, 'remove() must return it self');

        $this->assertFalse($inputFilter->has($expectedInputName), "There is no input with name $expectedInputName");
        $this->assertCount($currentNumberOfFilters - 1, $inputFilter, 'Number of filters must be decreased by 1');
    }

    public function testAddingInputWithNameDoesNotInjectNameInInput()
    {
        $inputFilter = $this->inputFilter;

        $foo = new Input('foo');
        $inputFilter->add($foo, 'bas');

        $test = $inputFilter->get('bas');
        $this->assertSame($foo, $test, 'get() does not match the input added');
        $this->assertEquals('foo', $foo->getName(), 'Input name should not change');
    }

    /**
     * @dataProvider inputProvider
     */
    public function testReplace($input, $inputName, $expectedInput)
    {
        $inputFilter = $this->inputFilter;
        $nameToReplace = 'replace_me';
        $inputToReplace = new Input($nameToReplace);

        $inputFilter->add($inputToReplace);
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->replace($input, $nameToReplace);
        $this->assertSame($inputFilter, $return, 'replace() must return it self');
        $this->assertCount($currentNumberOfFilters, $inputFilter, "Number of filters shouldn't change");

        $returnInput = $inputFilter->get($nameToReplace);
        $this->assertEquals($expectedInput, $returnInput, 'get() does not match the expected input');
    }

    /**
     * @dataProvider setDataArgumentsProvider
     */
    public function testSetDataAndGetRawValueGetValue(
        $inputs,
        $data,
        $expectedRawValues,
        $expectedValues,
        $expectedIsValid,
        $expectedInvalidInputs,
        $expectedValidInputs,
        $expectedMessages
    ) {
        $inputFilter = $this->inputFilter;
        foreach ($inputs as $inputName => $input) {
            $inputFilter->add($input, $inputName);
        }
        $return = $inputFilter->setData($data);
        $this->assertSame($inputFilter, $return, 'setData() must return it self');

        // ** Check filter state **
        $this->assertSame($expectedRawValues, $inputFilter->getRawValues(), 'getRawValues() value not match');
        foreach ($expectedRawValues as $inputName => $expectedRawValue) {
            $this->assertSame(
                $expectedRawValue,
                $inputFilter->getRawValue($inputName),
                'getRawValue() value not match for input ' . $inputName
            );
        }

        $this->assertSame($expectedValues, $inputFilter->getValues(), 'getValues() value not match');
        foreach ($expectedValues as $inputName => $expectedValue) {
            $this->assertSame(
                $expectedValue,
                $inputFilter->getValue($inputName),
                'getValue() value not match for input ' . $inputName
            );
        }

        // ** Check validation state **
        $this->assertEquals($expectedIsValid, $inputFilter->isValid(), 'isValid() value not match');
        $this->assertEquals($expectedInvalidInputs, $inputFilter->getInvalidInput(), 'getInvalidInput() value not match');
        $this->assertEquals($expectedValidInputs, $inputFilter->getValidInput(), 'getValidInput() value not match');
        $this->assertEquals($expectedMessages, $inputFilter->getMessages(), 'getMessages() value not match');

        // ** Check unknown fields **
        $this->assertFalse($inputFilter->hasUnknown(), 'hasUnknown() value not match');
        $this->assertEmpty($inputFilter->getUnknown(), 'getUnknown() value not match');
    }

    /**
     * @dataProvider setDataArgumentsProvider
     */
    public function testSetTraversableDataAndGetRawValueGetValue(
        $inputs,
        $data,
        $expectedRawValues,
        $expectedValues,
        $expectedIsValid,
        $expectedInvalidInputs,
        $expectedValidInputs,
        $expectedMessages
    ) {
        $dataTypes = $this->dataTypes();
        $this->testSetDataAndGetRawValueGetValue(
            $inputs,
            $dataTypes['Traversable']($data),
            $expectedRawValues,
            $expectedValues,
            $expectedIsValid,
            $expectedInvalidInputs,
            $expectedValidInputs,
            $expectedMessages
        );
    }

    public function getInputFilter()
    {
        $filter = $this->inputFilter;

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 6));

        $bar = new Input();
        $bar->getFilterChain()->attachByName('stringtrim');
        $bar->getValidatorChain()->attach(new Validator\Digits());

        $baz = new Input();
        $baz->setRequired(false);
        $baz->getFilterChain()->attachByName('stringtrim');
        $baz->getValidatorChain()->attach(new Validator\StringLength(1, 6));

        $qux = new Input();
        $qux->setAllowEmpty(true);
        $qux->getFilterChain()->attachByName('stringtrim');
        $qux->getValidatorChain()->attach(new Validator\StringLength(5, 6));

        $filter->add($foo, 'foo')
               ->add($bar, 'bar')
               ->add($baz, 'baz')
               ->add($qux, 'qux')
               ->add($this->getChildInputFilter(), 'nest');

        return $filter;
    }

    public function getChildInputFilter()
    {
        $filter = new BaseInputFilter();

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 6));

        $bar = new Input();
        $bar->getFilterChain()->attachByName('stringtrim');
        $bar->getValidatorChain()->attach(new Validator\Digits());

        $baz = new Input();
        $baz->setRequired(false);
        $baz->getFilterChain()->attachByName('stringtrim');
        $baz->getValidatorChain()->attach(new Validator\StringLength(1, 6));

        $filter->add($foo, 'foo')
               ->add($bar, 'bar')
               ->add($baz, 'baz');
        return $filter;
    }

    public function testResetEmptyValidationGroupRecursively()
    {
        $data = [
            'flat' => 'foo',
            'deep' => [
                'deep-input1' => 'deep-foo1',
                'deep-input2' => 'deep-foo2',
            ]
        ];
        $filter = $this->inputFilter;
        $filter->add(new Input, 'flat');
        $deepInputFilter = new BaseInputFilter;
        $deepInputFilter->add(new Input, 'deep-input1');
        $deepInputFilter->add(new Input, 'deep-input2');
        $filter->add($deepInputFilter, 'deep');
        $filter->setData($data);
        $filter->setValidationGroup(['deep' => 'deep-input1']);
        // reset validation group
        $filter->setValidationGroup(InputFilterInterface::VALIDATE_ALL);
        $this->assertEquals($data, $filter->getValues());
    }

    /*
     * Idea for this one is that validation may need to rely on context -- e.g., a "password confirmation"
     * field may need to know what the original password entered was in order to compare.
     */

    public function contextProvider()
    {
        $data = ['fooInput' => 'fooValue'];
        $traversableData = new ArrayObject(['fooInput' => 'fooValue']);
        $expectedFromData = ['fooInput' => 'fooValue'];

        return [
            // Description => [$data, $customContext, $expectedContext]
            'by default get context from data (array)' => [$data, null, $expectedFromData],
            'by default get context from data (Traversable)' => [$traversableData, null, $expectedFromData],
            'use custom context' => [[], 'fooContext', 'fooContext'],
        ];
    }

    /**
     * @dataProvider contextProvider
     */
    public function testValidationContext($data, $customContext, $expectedContext)
    {
        $filter = $this->inputFilter;

        $input = $this->createInputInterfaceMock('fooInput', true, true, $expectedContext);
        $filter->add($input, 'fooInput');

        $filter->setData($data);

        $this->assertTrue(
            $filter->isValid($customContext),
            'isValid() value not match. Detail: ' . json_encode($filter->getMessages())
        );
    }

    public function testBuildValidationContextUsingInputGetRawValue()
    {
        $data = [];
        $expectedContext = ['fooInput' => 'fooRawValue'];
        $filter = $this->inputFilter;

        $input = $this->createInputInterfaceMock('fooInput', true, true, $expectedContext, 'fooRawValue');
        $filter->add($input, 'fooInput');

        $filter->setData($data);

        $this->assertTrue(
            $filter->isValid(),
            'isValid() value not match. Detail: ' . json_encode($filter->getMessages())
        );
    }

    public function testContextIsTheSameWhenARequiredInputIsGivenAndOptionalInputIsMissing()
    {
        $data = [
            'inputRequired' => 'inputRequiredValue',
        ];
        $expectedContext = [
            'inputRequired' => 'inputRequiredValue',
            'inputOptional' => null,
        ];
        $inputRequired = $this->createInputInterfaceMock('fooInput', true, true, $expectedContext);
        $inputOptional = $this->createInputInterfaceMock('fooInput', false);

        $filter = $this->inputFilter;
        $filter->add($inputRequired, 'inputRequired');
        $filter->add($inputOptional, 'inputOptional');

        $filter->setData($data);

        $this->assertTrue(
            $filter->isValid(),
            'isValid() value not match. Detail: ' . json_encode($filter->getMessages())
        );
    }

    public function testValidationSkipsFieldsMarkedNotRequiredWhenNoDataPresent()
    {
        $filter = $this->inputFilter;

        $optionalInputName = 'fooOptionalInput';
        /** @var InputInterface|MockObject $optionalInput */
        $optionalInput = $this->getMock(InputInterface::class);
        $optionalInput->method('getName')
            ->willReturn($optionalInputName)
        ;
        $optionalInput->expects($this->never())
            ->method('isValid')
        ;
        $data = [];

        $filter->add($optionalInput);

        $filter->setData($data);

        $this->assertTrue(
            $filter->isValid(),
            'isValid() value not match. Detail . ' . json_encode($filter->getMessages())
        );
        $this->assertArrayNotHasKey(
            $optionalInputName,
            $filter->getValidInput(),
            'Missing optional fields must not appear as valid input neither invalid input'
        );
        $this->assertArrayNotHasKey(
            $optionalInputName,
            $filter->getInvalidInput(),
            'Missing optional fields must not appear as valid input neither invalid input'
        );
    }

    public function testHasUnknown()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'baz' => ''
        ];
        $filter->setData($validData);
        $this->assertFalse($filter->hasUnknown());

        $filter = $this->getInputFilter();
        $invalidData = [
            'bar' => '12345',
            'baz' => '',
            'gru' => '',
        ];
        $filter->setData($invalidData);
        $this->assertTrue($filter->hasUnknown());
    }
    public function testGetUknown()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $unknown = [
            'bar' => '12345',
            'baz' => '',
            'gru' => 10,
            'test' => 'ok',
        ];
        $filter->setData($unknown);
        $unknown = $filter->getUnknown();
        $this->assertEquals(2, count($unknown));
        $this->assertArrayHasKey('gru', $unknown);
        $this->assertEquals(10, $unknown['gru']);
        $this->assertArrayHasKey('test', $unknown);
        $this->assertEquals('ok', $unknown['test']);

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'baz' => ''
        ];
        $filter->setData($validData);
        $unknown = $filter->getUnknown();
        $this->assertEquals(0, count($unknown));
    }

    public function testGetInputs()
    {
        $filter = $this->inputFilter;

        $foo = new Input('foo');
        $bar = new Input('bar');

        $filter->add($foo);
        $filter->add($bar);

        $filters = $filter->getInputs();

        $this->assertCount(2, $filters);
        $this->assertEquals('foo', $filters['foo']->getName());
        $this->assertEquals('bar', $filters['bar']->getName());
    }

    /**
     * @group 4996
     */
    public function testAddingExistingInputWillMergeIntoExisting()
    {
        $filter = $this->inputFilter;

        $foo1    = new Input('foo');
        $foo1->setRequired(true);
        $filter->add($foo1);

        $foo2    = new Input('foo');
        $foo2->setRequired(false);
        $filter->add($foo2);

        $this->assertFalse($filter->get('foo')->isRequired());
    }

    /**
     * @group 5270
     * @requires extension intl
     */
    public function testIsValidWhenValuesSetOnFilters()
    {
        $filter = $this->inputFilter;

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(15, 18));

        $filter->add($foo, 'foo');

        //test valid with setData
        $filter->setData(['foo' => 'invalid']);
        $this->assertFalse($filter->isValid());

        //test invalid with setData
        $filter->setData(['foo' => 'thisisavalidstring']);
        $this->assertTrue(
            $filter->isValid(),
            'isValid() value not match. Detail . ' . json_encode($filter->getMessages())
        );

        //test invalid when setting data on actual filter
        $filter->get('foo')->setValue('invalid');
        $this->assertFalse($filter->get('foo')->isValid(), 'Filtered value is valid, should be invalid');
        $this->assertFalse($filter->isValid(), 'Input filter did not return value from filter');

        //test valid when setting data on actual filter
        $filter->get('foo')->setValue('thisisavalidstring');
        $this->assertTrue(
            $filter->get('foo')
                ->isValid(),
            'Filtered value is not valid. Detail . ' . json_encode(
                $filter->get('foo')
                    ->getMessages()
            )
        );
        $this->assertTrue(
            $filter->isValid(),
            'Input filter did return value from filter. Detail . ' . json_encode($filter->getMessages())
        );
    }

    /**
     * @group 5638
     */
    public function testPopulateSupportsArrayInputEvenIfDataMissing()
    {
        /** @var ArrayInput|MockObject $arrayInput */
        $arrayInput = $this->getMock(ArrayInput::class);
        $arrayInput
            ->expects($this->once())
            ->method('setValue')
            ->with([]);

        $filter = $this->inputFilter;
        $filter->add($arrayInput, 'arrayInput');
        $filter->setData(['foo' => 'bar']);
    }

    /**
     * @group 6431
     */
    public function testMerge()
    {
        $inputFilter       = $this->inputFilter;
        $originInputFilter = new BaseInputFilter();

        $inputFilter->add(new Input(), 'foo');
        $inputFilter->add(new Input(), 'bar');

        $originInputFilter->add(new Input(), 'baz');

        $inputFilter->merge($originInputFilter);

        $this->assertEquals(
            [
                'foo',
                'bar',
                'baz'
            ],
            array_keys($inputFilter->getInputs())
        );
    }

    public function addMethodArgumentsProvider()
    {
        $inputTypes = $this->inputProvider();

        $inputName = function ($inputTypeData) {
            return $inputTypeData[1];
        };

        $sameInput = function ($inputTypeData) {
            return $inputTypeData[2];
        };

        // @codingStandardsIgnoreStart
        $dataTemplates=[
            // Description => [[$input argument], $name argument, $expectedName, $expectedInput]
            'null' =>        [$inputTypes, null         , $inputName   , $sameInput],
            'custom_name' => [$inputTypes, 'custom_name', 'custom_name', $sameInput],
        ];
        // @codingStandardsIgnoreEnd

        // Expand data template matrix for each possible input type.
        // Description => [$input argument, $name argument, $expectedName, $expectedInput]
        $dataSets = [];
        foreach ($dataTemplates as $dataTemplateDescription => $dataTemplate) {
            foreach ($dataTemplate[0] as $inputTypeDescription => $inputTypeData) {
                $tmpTemplate = $dataTemplate;
                $tmpTemplate[0] = $inputTypeData[0]; // expand input
                if (is_callable($dataTemplate[2])) {
                    $tmpTemplate[2] = $dataTemplate[2]($inputTypeData);
                }
                $tmpTemplate[3] = $dataTemplate[3]($inputTypeData);

                $dataSets[$inputTypeDescription . ' / ' . $dataTemplateDescription] = $tmpTemplate;
            }
        }

        return $dataSets;
    }

    public function setDataArgumentsProvider()
    {
        $iAName = 'InputA';
        $iBName = 'InputB';
        $vRaw = 'rawValue';
        $vFiltered = 'filteredValue';

        $dARaw = [$iAName => $vRaw];
        $dBRaw = [$iBName => $vRaw];
        $dAfRaw = [$iAName => ['fooInput' => $vRaw]];
        $d2Raw = array_merge($dARaw, $dBRaw);
        $dAfBRaw = array_merge($dAfRaw, $dBRaw);
        $dAFiltered = [$iAName => $vFiltered];
        $dBFiltered = [$iBName => $vFiltered];
        $dAfFiltered = [$iAName => ['fooInput' => $vFiltered]];
        $d2Filtered = array_merge($dAFiltered, $dBFiltered);
        $dAfBFiltered = array_merge($dAfFiltered, $dBFiltered);

        $required = true;
        $valid = true;
        $bOnFail = true;

        $input = function ($iName, $required, $bOnFail, $isValid, $msg = []) use ($vRaw, $vFiltered) {
            // @codingStandardsIgnoreStart
            return function ($context) use ($iName, $required, $bOnFail, $isValid, $vRaw, $vFiltered, $msg) {
                return $this->createInputInterfaceMock($iName, $required, $isValid, $context, $vRaw, $vFiltered, $msg, $bOnFail);
            };
            // @codingStandardsIgnoreEnd
        };

        $inputFilter = function ($isValid, $msg = []) use ($vRaw, $vFiltered) {
            // @codingStandardsIgnoreStart
            return function ($context) use ($isValid, $vRaw, $vFiltered, $msg) {
                $vRaw = ['fooInput' => $vRaw];
                $vFiltered = ['fooInput' => $vFiltered];
                return $this->createInputFilterInterfaceMock($isValid, $context, $vRaw, $vFiltered, $msg);
            };
            // @codingStandardsIgnoreEnd
        };

        // @codingStandardsIgnoreStart
        $iAri  = [$iAName => $input($iAName, $required, !$bOnFail, !$valid, ['Invalid ' . $iAName])];
        $iAriX = [$iAName => $input($iAName, $required,  $bOnFail, !$valid, ['Invalid ' . $iAName])];
        $iArvX = [$iAName => $input($iAName, $required,  $bOnFail,  $valid, [])];
        $iBri  = [$iBName => $input($iBName, $required, !$bOnFail, !$valid, ['Invalid ' . $iBName])];
        $iBriX = [$iBName => $input($iBName, $required,  $bOnFail, !$valid, ['Invalid ' . $iBName])];
        $iBrvX = [$iBName => $input($iBName, $required,  $bOnFail,  $valid, [])];
        $ifAi  = [$iAName => $inputFilter(!$valid, ['fooInput' => ['Invalid ' . $iAName]])];
        $ifAv  = [$iAName => $inputFilter($valid)];
        $iAriBri   = array_merge($iAri,  $iBri);
        $iArvXBrvX = array_merge($iArvX, $iBrvX);
        $iAriBrvX  = array_merge($iAri,  $iBrvX);
        $iArvXBir  = array_merge($iArvX, $iBri);
        $iAriXBrvX = array_merge($iAriX, $iBrvX);
        $iArvXBriX = array_merge($iArvX, $iBriX);
        $iAriXBriX = array_merge($iAriX, $iBriX);
        $ifAiBri   = array_merge($ifAi, $iBri);
        $ifAiBrvX  = array_merge($ifAi, $iBrvX);
        $ifAvBri   = array_merge($ifAv, $iBri);
        $ifAvBrv   = array_merge($ifAv, $iBrvX);

        $msgAInv = [$iAName => ['Invalid InputA']];
        $msgBInv = [$iBName => ['Invalid InputB']];
        $msgAfInv = [$iAName => ['fooInput' => ['Invalid InputA']]];
        $msg2Inv = array_merge($msgAInv, $msgBInv);
        $msgAfBInv = array_merge($msgAfInv, $msgBInv);

        $dataSets = [
            // Description => [$inputs, $data argument, $expectedRawValues, $expectedValues, $expectedIsValid,
            //                 $expectedInvalidInputs, $expectedValidInputs, $expectedMessages]
            'invalid Break invalid'     => [$iAriXBriX, $d2Raw, $d2Raw, $d2Filtered, false, $iAri    , []        , $msgAInv],
            'invalid Break valid'       => [$iAriXBrvX, $d2Raw, $d2Raw, $d2Filtered, false, $iAri    , []        , $msgAInv],
            'valid   Break invalid'     => [$iArvXBriX, $d2Raw, $d2Raw, $d2Filtered, false, $iBri    , $iAri     , $msgBInv],
            'valid   Break valid'       => [$iArvXBrvX, $d2Raw, $d2Raw, $d2Filtered, true , []       , $iArvXBrvX, []],
            'valid   invalid'           => [$iArvXBir , $d2Raw, $d2Raw, $d2Filtered, false, $iBri    , $iArvX    , $msgBInv],
            'IInvalid IValid'           => [$iAriBrvX , $d2Raw, $d2Raw, $d2Filtered, false, $iAri    , $iBrvX    , $msgAInv],
            'IInvalid IInvalid'         => [$iAriBri  , $d2Raw, $d2Raw, $d2Filtered, false, $iAriBri , []        , $msg2Inv],
            'IInvalid IValid / Partial' => [$iAriBri  , $dARaw, $d2Raw, $d2Filtered, false, $iAriBrvX, []        , $msg2Inv],
            'IFInvalid IValid'          => [$ifAiBrvX , $dAfBRaw, $dAfBRaw, $dAfBFiltered, false, $ifAi   , $iBrvX  , $msgAfInv],
            'IFInvalid IInvalid'        => [$ifAiBri  , $dAfBRaw, $dAfBRaw, $dAfBFiltered, false, $ifAiBri, []      , $msgAfBInv],
            'IFValid IInvalid'          => [$ifAvBri  , $dAfBRaw, $dAfBRaw, $dAfBFiltered, false, $iBri   , $ifAv   , $msgBInv],
            'IFValid IValid'            => [$ifAvBrv  , $dAfBRaw, $dAfBRaw, $dAfBFiltered, true , []      , $ifAvBrv, []],
        ];
        // @codingStandardsIgnoreEnd

        array_walk(
            $dataSets,
            function (&$set) {
                // Create unique mock input instances for each set
                foreach ($set[0] as $name => $createMock) {
                    $input = $createMock($set[2]);

                    $set[0][$name] = $input;
                    if (in_array($name, array_keys($set[5]))) {
                        $set[5][$name] = $input;
                    }
                    if (in_array($name, array_keys($set[6]))) {
                        $set[6][$name] = $input;
                    }
                }
            }
        );

        return $dataSets;
    }

    public function inputProvider()
    {
        $input = $this->createInputInterfaceMock('fooInput', null);
        $inputFilter = $this->createInputFilterInterfaceMock();

        // @codingStandardsIgnoreStart
        return [
            // Description => [input, expected name, $expectedReturnInput]
            'InputInterface' =>       [$input      , 'fooInput', $input],
            'InputFilterInterface' => [$inputFilter, null      , $inputFilter],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param null|bool $isValid
     * @param mixed $context
     * @param mixed[] $getRawValues
     * @param mixed[] $getValues
     * @param string[] $getMessages
     *
     * @return MockObject|InputFilterInterface
     */
    protected function createInputFilterInterfaceMock(
        $isValid = null,
        $context = null,
        $getRawValues = [],
        $getValues = [],
        $getMessages = []
    ) {
        /** @var InputFilterInterface|MockObject $inputFilter */
        $inputFilter = $this->getMock(InputFilterInterface::class);
        $inputFilter->method('getRawValues')
            ->willReturn($getRawValues)
        ;
        $inputFilter->method('getValues')
            ->willReturn($getValues)
        ;
        if (($isValid === false) || ($isValid === true)) {
            $inputFilter->expects($this->once())
                ->method('isValid')
                ->willReturn($isValid)
            ;
        } else {
            $inputFilter->expects($this->never())
                ->method('isValid')
            ;
        }
        $inputFilter->method('getMessages')
            ->willReturn($getMessages)
        ;

        return $inputFilter;
    }

    /**
     * @param string $name
     * @param bool $isRequired
     * @param null|bool $isValid
     * @param mixed $context
     * @param mixed $getRawValue
     * @param mixed $getValue
     * @param string[] $getMessages
     * @param bool $breakOnFailure
     *
     * @return MockObject|InputInterface
     */
    protected function createInputInterfaceMock(
        $name,
        $isRequired,
        $isValid = null,
        $context = null,
        $getRawValue = null,
        $getValue = null,
        $getMessages = [],
        $breakOnFailure = false
    ) {
        /** @var InputInterface|MockObject $input */
        $input = $this->getMock(InputInterface::class);
        $input->method('getName')
            ->willReturn($name)
        ;
        $input->method('isRequired')
            ->willReturn($isRequired)
        ;
        $input->method('getRawValue')
            ->willReturn($getRawValue)
        ;
        $input->method('getValue')
            ->willReturn($getValue)
        ;
        $input->method('breakOnFailure')
            ->willReturn($breakOnFailure)
        ;
        if (($isValid === false) || ($isValid === true)) {
            $input->expects($this->once())
                ->method('isValid')
                ->with($context)
                ->willReturn($isValid)
            ;
        } else {
            $input->expects($this->never())
                ->method('isValid')
                ->with($context)
            ;
        }
        $input->method('getMessages')
            ->willReturn($getMessages)
        ;

        return $input;
    }

    /**
     * @return callable[]
     */
    protected function dataTypes()
    {
        return [
            // Description => callable
            'array' => function ($data) {
                return $data;
            },
            'Traversable' => function ($data) {
                return $this->getMockBuilder(FilterIterator::class)
                    ->setConstructorArgs([new ArrayIterator($data)])
                    ->getMock()
                ;
            },
        ];
    }
}

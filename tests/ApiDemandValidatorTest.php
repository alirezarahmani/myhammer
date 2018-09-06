<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApiDemandValidatorTest extends TestCase
{
    private $request;
    private $validator;
    private $customValidationDouble;
    private $payLoad = [
        'title' => 'this is dummy',
        'category_id' =>  '804040',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'execute_time' => 'immediately',
        'description' => 'dummy text'

    ];

    public function setUp()
    {
        parent::setUp();
        putenv('VENDOR_DIR=../vendor/');
        \Loader\MyHammer::create();
        $this->validator = new \MyHammer\Infrastructure\Validator\ApiDemandValidator();
        $this->request = Mockery::mock(Request::class);
        $this->customValidationDouble = Mockery::mock(\MyHammer\Infrastructure\Validator\CustomValidations::class);
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyTitle()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Title: must not be empty');
        unset($this->payLoad['title']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return false;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithLessThanFiveTitle()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Title: allows between 5 to 50 characters');
        $this->payLoad['title'] = 'all';
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithMoreThanFiftyFiveTitle()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Title: allows between 5 to 50 characters');
        $this->payLoad['title'] = 'this title is too big for us please make it shorter, that is all we knew';
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyCategoryId()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('category Id: should not be empty');
        unset($this->payLoad['category_id']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithWrongCategoryId()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Category ID: is not valid');
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return false;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyZipCode()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Zip: must not be empty');
        unset($this->payLoad['zip_code']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithWrongZipCode()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Zip: german zip code only');
        $this->payLoad['zip_code'] = '123333';
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithWrongCity()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('City: german city only');
        $this->payLoad['city'] = 'tehran';
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyCity()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('City: should not be empty');
        unset($this->payLoad['city']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithWrongExecutionTime()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Execution Time: sorry please make sure you select correct execution time from list');
        $this->payLoad['execute_time'] = 'next year';
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyExecutionTime()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        $this->expectExceptionMessage('Execution Time: sorry please make sure you select correct execution time from list');
        unset($this->payLoad['execute_time']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(
            function () {
                return true;
            }
        );
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }
}

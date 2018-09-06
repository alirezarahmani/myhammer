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

    /** @test */
    public function shouldThrowExceptionWithEmptyTitle()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
        unset($this->payLoad['title']);
        $this->request->query = (new ParameterBag($this->payLoad));
        $this->customValidationDouble->allows()->isValidCategoryId()->andReturns(true);
        $this->validator->validate(
            new \MyHammer\Infrastructure\Request\ApiWebRequest($this->request),
            $this->customValidationDouble
        );
    }

    /** @test */
    public function shouldThrowExceptionWithEmptyCategoryId()
    {
        $this->expectException(\MyHammer\Library\Assert\ValidateException::class);
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
        unset($this->payLoad['category_id']);
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
}

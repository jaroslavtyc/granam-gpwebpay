<?php
namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\CardPayResponse;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\GpWebPay\Codes\ResponseDigestKeys;
use Granam\GpWebPay\Codes\ResponsePayloadKeys;
use Granam\GpWebPay\DigestSignerInterface;
use Granam\GpWebPay\Exceptions\BrokenResponse;
use Granam\GpWebPay\SettingsInterface;

class CardPayResponseTest extends PayResponseTest
{
    /**
     * @test
     * @dataProvider provideValuesForResponse
     * @param string $operation
     * @param int $orderNumber
     * @param int $prCode
     * @param int $srCode
     * @param string $digest
     * @param string $digest1
     * @param int|null $merOrderNum
     * @param string|null $md
     * @param string|null $resultText
     * @param string|null $userParam1
     * @param string|null $addInfo
     */
    public function I_can_use_it(
        string $operation,
        int $orderNumber,
        int $prCode,
        int $srCode,
        string $digest,
        string $digest1,
        int $merOrderNum = null,
        string $md = null,
        string $resultText = null,
        string $userParam1 = null,
        string $addInfo = null
    )
    {
        $settings = $this->createSettings($merchantNumber = 'foo');
        $parametersForDigest = array_filter(
            [
                ResponseDigestKeys::OPERATION => $operation,
                ResponseDigestKeys::ORDERNUMBER => $orderNumber,
                ResponseDigestKeys::MERORDERNUM => $merOrderNum,
                ResponseDigestKeys::MD => $md,
                ResponseDigestKeys::PRCODE => $prCode,
                ResponseDigestKeys::SRCODE => $srCode,
                ResponseDigestKeys::RESULTTEXT => $resultText,
                ResponseDigestKeys::USERPARAM1 => $userParam1,
                ResponseDigestKeys::ADDINFO => $addInfo,
            ],
            function ($value) {
                return $value !== null;
            }
        );
        $digestSigner = $this->createDigestSigner($digest, $parametersForDigest, true, $digest1, $merchantNumber, true);
        $cardPayResponse = new CardPayResponse(
            $settings,
            $digestSigner,
            $operation,
            $orderNumber,
            $prCode,
            $srCode,
            $digest,
            $digest1,
            $merOrderNum,
            $md,
            $resultText,
            $userParam1,
            $addInfo
        );
        self::assertFalse($cardPayResponse->hasError());
        self::assertSame($digest, $cardPayResponse->getDigest());
        self::assertSame($digest1, $cardPayResponse->getDigest1());
        self::assertSame($prCode, $cardPayResponse->getPrCode());
        self::assertSame($srCode, $cardPayResponse->getSrCode());
        self::assertSame($operation, $cardPayResponse->getOperation());
        self::assertSame($orderNumber, $cardPayResponse->getOrderNumber());
        self::assertSame($merOrderNum, $cardPayResponse->getMerchantOrderNumber());
        self::assertSame($md, $cardPayResponse->getMerchantNote());
        self::assertSame($userParam1, $cardPayResponse->getUserParam1());
        self::assertSame($addInfo, $cardPayResponse->getAdditionalInfo());
        self::assertSame($resultText, $cardPayResponse->getResultText());
        $parameters = [
            ResponseDigestKeys::OPERATION => $operation,
            ResponseDigestKeys::ORDERNUMBER => $orderNumber,
            ResponseDigestKeys::MERORDERNUM => $merOrderNum,
            ResponseDigestKeys::MD => $md,
            ResponseDigestKeys::PRCODE => $prCode,
            ResponseDigestKeys::SRCODE => $srCode,
            ResponseDigestKeys::RESULTTEXT => $resultText,
            ResponseDigestKeys::USERPARAM1 => $userParam1,
            ResponseDigestKeys::ADDINFO => $addInfo,
        ];
        self::assertSame(
            array_filter(
                $parameters,
                function ($value) {
                    return $value !== null;
                }
            ),
            $cardPayResponse->getParametersForDigest()
        );
        $parameters[ResponsePayloadKeys::DIGEST] = $digest;
        $parameters[ResponsePayloadKeys::DIGEST1] = $digest1;
        self::assertEquals(
            $cardPayResponse,
            CardPayResponse::createFromArray($parameters, $settings, $digestSigner)
        );
    }

    public function provideValuesForResponse()
    {
        return [
            ['foo', 1357, 0, 0, 'baz', 'qux', 987654, 'BAR', 'BAZ', 'QUX', 'FooBar'],
            ['foo', 1357, 0, 123, 'baz', 'qux', 987654, null, 'BAR', null, 'BAZ'],
            ['foo', 1357, 200, 456, 'baz', 'qux', null, null, null, null, null],
        ];
    }

    /**
     * @param string $merchantNumber
     * @return \Mockery\MockInterface|SettingsInterface
     */
    private function createSettings(string $merchantNumber = null)
    {
        $settings = $this->mockery(SettingsInterface::class);
        $settings->shouldReceive('getMerchantNumber')
            ->andReturn($merchantNumber);

        return $settings;
    }

    /**
     * @param string $digest
     * @param array $parameters
     * @param bool $digestMatches
     * @param string $digest1
     * @param string $merchantNumber
     * @param bool $digest1Matches
     * @return \Mockery\MockInterface|DigestSignerInterface
     */
    private function createDigestSigner(
        string $digest,
        array $parameters,
        bool $digestMatches,
        string $digest1,
        string $merchantNumber,
        bool $digest1Matches
    )
    {
        $digestSigner = $this->mockery(DigestSignerInterface::class);
        $digestSigner->shouldReceive('verifySignedDigest')
            ->with($digest, $parameters)
            ->andReturn($digestMatches);
        $parametersForDigest1 = $parameters;
        $parametersForDigest1[RequestPayloadKeys::MERCHANTNUMBER] = $merchantNumber;
        $digestSigner->shouldReceive('verifySignedDigest')
            ->with($digest1, $parametersForDigest1)
            ->andReturn($digest1Matches);

        return $digestSigner;
    }

    /**
     * @test
     * @expectedException \Granam\GpWebPay\Exceptions\GpWebPayErrorResponse
     * @expectedExceptionMessageRegExp ~error code 4\(6\)~
     */
    public function I_am_stopped_by_exception_if_error_occurred()
    {
        new CardPayResponse($this->createSomeSettings(), $this->createSomeDigestSigner(), 'foo', 123, 4, 6, 'bar', 'baz');
    }

    /**
     * @test
     * @expectedException \Granam\GpWebPay\Exceptions\GpWebPayErrorByCustomerResponse
     * @expectedExceptionMessageRegExp ~error code 1000\(1005\)~
     */
    public function I_am_stopped_by_customer_exception_if_error_caused_by_him()
    {
        new CardPayResponse($this->createSomeSettings(), $this->createSomeDigestSigner(), 'foo', 123, 1000, 1005, 'bar', 'baz');
    }


    /**
     * @test
     * @expectedException \Granam\GpWebPay\Exceptions\BrokenResponse
     * @expectedExceptionMessageRegExp ~has invalid format~
     * @dataProvider provideValuesWithInvalidTypes
     * @param $operation
     * @param $orderNumber
     * @param $prCode
     * @param $srCode
     * @param null $merOrderNum
     * @param null $md
     * @param null $resultText
     * @param null $userParam1
     * @param null $addInfo
     */
    public function I_can_not_create_it_from_array_with_invalid_value_types(
        $operation,
        $orderNumber,
        $prCode,
        $srCode,
        $merOrderNum = null,
        $md = null,
        $resultText = null,
        $userParam1 = null,
        $addInfo = null
    )
    {
        $parameters = [
            ResponseDigestKeys::OPERATION => $operation,
            ResponseDigestKeys::ORDERNUMBER => $orderNumber,
            ResponseDigestKeys::MERORDERNUM => $merOrderNum,
            ResponseDigestKeys::MD => $md,
            ResponseDigestKeys::PRCODE => $prCode,
            ResponseDigestKeys::SRCODE => $srCode,
            ResponseDigestKeys::RESULTTEXT => $resultText,
            ResponseDigestKeys::USERPARAM1 => $userParam1,
            ResponseDigestKeys::ADDINFO => $addInfo,
        ];
        CardPayResponse::createFromArray($parameters, $this->createSomeSettings(), $this->createSomeDigestSigner());
    }

    public function provideValuesWithInvalidTypes()
    {
        // string, int, int, int, string, string, int, string, string, string, string, string
        return [
            [new \stdClass(), 'number', 0, 0, 'baz', 'qux', 987654, 'BAR', 'BAZ', 'QUX', 'FooBar'],
            ['foo', 'number', 0, 0, 'baz', 'qux', 987654, 'BAR', 'BAZ', 'QUX', 'FooBar'],
        ];
    }

    /**
     * @test
     * @dataProvider provideValuesForResponse
     * @param string $operation
     * @param string $orderNumber
     * @param int $prCode
     * @param int $srCode
     * @param string $digest
     * @param string $digest1
     * @param string|null $merOrderNum
     * @param string|null $md
     * @param string|null $resultText
     * @param string|null $userParam1
     * @param string|null $addInfo
     */
    public function I_can_not_create_it_from_array_with_missing_required_value(
        string $operation,
        string $orderNumber,
        int $prCode,
        int $srCode,
        string $digest,
        string $digest1,
        string $merOrderNum = null,
        string $md = null,
        string $resultText = null,
        string $userParam1 = null,
        string $addInfo = null
    )
    {
        $parameters = [
            ResponseDigestKeys::OPERATION => $operation,
            ResponseDigestKeys::ORDERNUMBER => $orderNumber,
            ResponseDigestKeys::MERORDERNUM => $merOrderNum,
            ResponseDigestKeys::MD => $md,
            ResponseDigestKeys::PRCODE => $prCode,
            ResponseDigestKeys::SRCODE => $srCode,
            ResponseDigestKeys::RESULTTEXT => $resultText,
            ResponseDigestKeys::USERPARAM1 => $userParam1,
            ResponseDigestKeys::ADDINFO => $addInfo,
            ResponsePayloadKeys::DIGEST => $digest,
            ResponsePayloadKeys::DIGEST1 => $digest1,
        ];
        foreach ([ResponseDigestKeys::OPERATION, ResponseDigestKeys::ORDERNUMBER, ResponseDigestKeys::PRCODE, ResponseDigestKeys::SRCODE, ResponsePayloadKeys::DIGEST, ResponsePayloadKeys::DIGEST1] as $requiredParameter) {
            $invalidParameters = $parameters;
            $invalidParameters[$requiredParameter] = null;
            try {
                CardPayResponse::createFromArray($invalidParameters, $this->createSomeSettings(), $this->createSomeDigestSigner());
                self::fail('Expected ' . BrokenResponse::class . ' has not been thrown');
            } catch (BrokenResponse $brokenResponse) {
                self::assertRegExp('~' . preg_quote($requiredParameter, '~') . '~', $brokenResponse->getMessage());
            }
        }
    }

    /**
     * @return \Mockery\MockInterface|SettingsInterface
     */
    private function createSomeSettings()
    {
        return $this->mockery(SettingsInterface::class);
    }

    /**
     * @return \Mockery\MockInterface|DigestSignerInterface
     */
    private function createSomeDigestSigner()
    {
        return $this->mockery(DigestSignerInterface::class);
    }

    /**
     * @test
     * @expectedException \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @expectedExceptionMessageRegExp ~'DIGEST' does not match~
     * @dataProvider provideValuesForResponse
     * @param string $operation
     * @param int $orderNumber
     * @param int $prCode
     * @param int $srCode
     * @param string $digest
     * @param string $digest1
     * @param int|null $merOrderNum
     * @param string|null $md
     * @param string|null $resultText
     * @param string|null $userParam1
     * @param string|null $addInfo
     */
    public function I_am_stopped_when_digest_does_not_match(
        string $operation,
        int $orderNumber,
        int $prCode,
        int $srCode,
        string $digest,
        string $digest1,
        int $merOrderNum = null,
        string $md = null,
        string $resultText = null,
        string $userParam1 = null,
        string $addInfo = null
    )
    {
        $settings = $this->createSettings($merchantNumber = 'foo');
        $parametersForDigest = array_filter(
            [
                ResponseDigestKeys::OPERATION => $operation,
                ResponseDigestKeys::ORDERNUMBER => $orderNumber,
                ResponseDigestKeys::MERORDERNUM => $merOrderNum,
                ResponseDigestKeys::MD => $md,
                ResponseDigestKeys::PRCODE => $prCode,
                ResponseDigestKeys::SRCODE => $srCode,
                ResponseDigestKeys::RESULTTEXT => $resultText,
                ResponseDigestKeys::USERPARAM1 => $userParam1,
                ResponseDigestKeys::ADDINFO => $addInfo,
            ],
            function ($value) {
                return $value !== null;
            }
        );
        $digestSigner = $this->createDigestSigner($digest, $parametersForDigest, false, $digest1, $merchantNumber, true);
        new CardPayResponse(
            $settings,
            $digestSigner,
            $operation,
            $orderNumber,
            $prCode,
            $srCode,
            $digest,
            $digest1,
            $merOrderNum,
            $md,
            $resultText,
            $userParam1,
            $addInfo
        );
    }

    /**
     * @test
     * @expectedException \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @expectedExceptionMessageRegExp ~'DIGEST1' has been modified~
     * @dataProvider provideValuesForResponse
     * @param string $operation
     * @param int $orderNumber
     * @param int $prCode
     * @param int $srCode
     * @param string $digest
     * @param string $digest1
     * @param int|null $merOrderNum
     * @param string|null $md
     * @param string|null $resultText
     * @param string|null $userParam1
     * @param string|null $addInfo
     */
    public function I_am_stopped_when_digest1_does_not_match(
        string $operation,
        int $orderNumber,
        int $prCode,
        int $srCode,
        string $digest,
        string $digest1,
        int $merOrderNum = null,
        string $md = null,
        string $resultText = null,
        string $userParam1 = null,
        string $addInfo = null
    )
    {
        $settings = $this->createSettings($merchantNumber = 'foo');
        $parametersForDigest = array_filter(
            [
                ResponseDigestKeys::OPERATION => $operation,
                ResponseDigestKeys::ORDERNUMBER => $orderNumber,
                ResponseDigestKeys::MERORDERNUM => $merOrderNum,
                ResponseDigestKeys::MD => $md,
                ResponseDigestKeys::PRCODE => $prCode,
                ResponseDigestKeys::SRCODE => $srCode,
                ResponseDigestKeys::RESULTTEXT => $resultText,
                ResponseDigestKeys::USERPARAM1 => $userParam1,
                ResponseDigestKeys::ADDINFO => $addInfo,
            ],
            function ($value) {
                return $value !== null;
            }
        );
        $digestSigner = $this->createDigestSigner($digest, $parametersForDigest, true, $digest1, $merchantNumber, false);
        new CardPayResponse(
            $settings,
            $digestSigner,
            $operation,
            $orderNumber,
            $prCode,
            $srCode,
            $digest,
            $digest1,
            $merOrderNum,
            $md,
            $resultText,
            $userParam1,
            $addInfo
        );
    }
}
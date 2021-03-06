<?php namespace Elozoya\MediaFromWeb\Unit;

use PHPUnit_Framework_TestCase;
use Mockery as m;
use Elozoya\MediaFromWeb\MediaFromWeb;

/*
 * MediaFromWebTest
 * ================
 *
 * TODO:
 *  - Photo is too big
 *  - Photo timeout
 */
class MediaFromWebTest extends PHPUnit_Framework_TestCase
{
    private $httpClientMock;
    private $reponseMock;
    private $mediaFromWeb;

    public function setUp()
    {
        $this->httpClientMock = m::mock('\GuzzleHttp\Client');
        $this->responseMock = m::mock('StdClass');
        $this->mediaFromWeb = new MediaFromWeb($this->httpClientMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testGetPhotosReturnsAnErrorResultDueToAnInvalidUrlFormat()
    {
        $url = "foo/url-not-well-formated";
        $this->httpClientMock->shouldNotReceive("request");
        $result = $this->mediaFromWeb->getPhotosFromUrl($url);
        $this->assertEquals($result, (object)[
          "error" => true,
          'message' => "Invalid URL format",
        ]);
    }

    public function testGetPhotosReturnsAnErrorResultDueToAnUnsuccessfulRequest()
    {
        $url = "http://foo.com/bar-does-not-exist.png";
        $this->httpClientMock->shouldReceive("request")->once()->with('HEAD', $url)->andReturn($this->responseMock);
        $this->responseMock->shouldReceive('getStatusCode')->once()->andReturn(404);
        $result = $this->mediaFromWeb->getPhotosFromUrl($url);
        $this->assertEquals($result, (object)[
          "error" => true,
          'message' => "Photos not found or you are not allowed to get them",
        ]);
    }

    public function testGetPhotosReturnsAnErrorResultDueToAnUnsupportedRequest()
    {
        $url = "http://foo.com/bar.xml";
        $this->httpClientMock->shouldReceive("request")->once()->with('HEAD', $url)->andReturn($this->responseMock);
        $this->responseMock->shouldReceive('getStatusCode')->once()->andReturn(200);
        $this->responseMock->shouldReceive('getHeader')->once()->with('content-type')->andReturn("application/xml");
        $result = $this->mediaFromWeb->getPhotosFromUrl($url);
        $this->assertEquals($result, (object)[
          "error" => true,
          'message' => "Photos not found due to an unsupported request",
        ]);
    }

    public function testGetPhotosReturnsAPhoto()
    {
        $url = "http://foo.com/photo.png";
        $this->httpClientMock->shouldReceive("request")->once()->with('HEAD', $url)->andReturn($this->responseMock);
        $this->responseMock->shouldReceive('getStatusCode')->once()->andReturn(200);
        $this->responseMock->shouldReceive('getHeader')->once()->with('content-type')->andReturn("image/png");
        $result = $this->mediaFromWeb->getPhotosFromUrl($url);
        $this->assertEquals($result, (object)[
            "data" => [
                (object)["photo_src" => $url],
            ],
        ]);
    }
}

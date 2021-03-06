<?php

namespace spec\Crowdin\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Crowdin\Client;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\Request;

class UploadTranslationSpec extends ObjectBehavior
{
    function let(Client $client, HttpClient $http)
    {
        $client->getHttpClient()->willReturn($http);
        $client->getProjectIdentifier()->willReturn('sylius');
        $client->getProjectApiKey()->willReturn('1234');
        $this->beConstructedWith($client);
    }

    function it_should_be_an_api()
    {
        $this->shouldBeAnInstanceOf('Crowdin\Api\AbstractApi');
    }

    function it_should_not_allow_not_existing_translation()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringAddTranslation('crowdin/path/file.yml', '/tmp/my-file.yml');
    }

    function it_has_translations()
    {
        $this->addTranslation('crowdin/path/file.csv',  'spec/fixtures/messages.en.yml');
        $this->getTranslations()->shouldHaveCount(1);
    }

    function it_does_not_import_duplicates_by_default()
    {
        $this->areDuplicatesImported()->shouldBe(false);
    }

    function it_does_not_import_equal_suggestions_by_default()
    {
        $this->areEqualSuggestionsImported()->shouldBe(false);
    }

    function it_does_not_auto_approve_imported_by_default()
    {
        $this->areImportsAutoApproved()->shouldBe(false);
    }

    function it_should_not_allow_upload_with_no_translation(HttpClient $http, Request $request, Response $response)
    {
        $this->setLocale('fr');
        $content = '<xml></xml>';
        $response->getBody(true)->willReturn($content);
        $request->send()->willReturn($response);
        $http->post('project/sylius/upload-translation?key=1234')->willReturn($request);

        $this->shouldThrow('\InvalidArgumentException')->duringExecute();
    }

    function it_should_not_allow_upload_with_no_locale(HttpClient $http, Request $request, Response $response)
    {
        $this->addTranslation('crowdin/path/file.yml',  'spec/fixtures/messages.en.yml');
        $content = '<xml></xml>';
        $response->getBody(true)->willReturn($content);
        $request->send()->willReturn($response);
        $http->post('project/sylius/upload-translation?key=1234')->willReturn($request);

        $this->shouldThrow('\InvalidArgumentException')->duringExecute();
    }

    function it_uploads_some_translations(HttpClient $http, Request $request, Response $response)
    {
        $this->addTranslation('crowdin/path/file.yml',  'spec/fixtures/messages.en.yml');
        $this->setLocale('fr');
        $content = '<xml></xml>';
        $response->getBody(true)->willReturn($content);
        $request->send()->willReturn($response);
        $http->post(
            'project/sylius/upload-translation?key=1234',
            array(),
            array(
                'files[crowdin/path/file.yml]' => '@spec/fixtures/messages.en.yml',
                'import_duplicates' => 0,
                'import_eq_suggestions' => 0,
                'auto_approve_imported' => 0,
                'language' => 'fr',
            )
        )->willReturn($request);
        $this->execute()->shouldBe($content);
    }
}

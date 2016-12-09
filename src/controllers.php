<?php

use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Seld\JsonLint\JsonParser;
use Symfony\Component\PropertyAccess\PropertyAccess;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->post('/', function (Request $request) use ($app) {
    $parser = new JsonParser();
    $accessor = PropertyAccess::createPropertyAccessor();

    $snappy = new Pdf();
    $snappy->setBinary($app['wkhtmltopdf.binary']);

    $parameters = $parser->parse($request->getContent());
    if ($accessor->isReadable($parameters, 'options')) {
        foreach ((array) $accessor->getValue($parameters, 'options') as $name => $value) {
            $snappy->setOption($name, $value);
        }
    }

    $app['tmpFile'] = sys_get_temp_dir().'/'.md5($request->getContent());
    if ($accessor->isReadable($parameters, 'source.url')) {
        $dns = new Net_DNS2_Resolver();
        $dns->query(parse_url($accessor->getValue($parameters, 'source.url'), PHP_URL_HOST));

        $snappy->generate($accessor->getValue($parameters, 'source.url'), $app['tmpFile'], [], true);
    } elseif ($accessor->isReadable($parameters, 'source.html')) {
        $snappy->generateFromHtml($accessor->getValue($parameters, 'source.html'), $app['tmpFile'], [], true);
    } elseif ($accessor->isReadable($parameters, 'source.base64')) {
        $snappy->generateFromHtml(base64_decode($accessor->getValue($parameters, 'source.base64')), $app['tmpFile'], [], true);
    }

    return new BinaryFileResponse($app['tmpFile']);
})
->bind('api');

$app->finish(function() use ($app) {
    if (file_exists($app['tmpFile'])) {
        unlink($app['tmpFile']);
    }
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    $response = [
      'status' => 'error',
      'code' => $code,
    ];

    $response['message'] = $e->getMessage();

    return new JsonResponse($response, $code);
});
